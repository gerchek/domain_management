<?php

namespace App\Services;

use App\Models\Server;
use App\Models\SiteDeployment;
use App\Models\SiteProject;
use App\Models\DomainDeployment;
use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

class DeployService
{
    protected ?SFTP $sftp = null;
    protected ?Server $currentServer = null;

    /**
     * Safely escape a value for use in shell commands
     */
    protected function escapeShellArg(string $value): string
    {
        return escapeshellarg($value);
    }

    /**
     * Validate domain name format to prevent injection
     */
    protected function validateDomainName(string $domain): string
    {
        // Only allow valid domain characters: a-z, 0-9, dots, hyphens
        if (!preg_match('/^[a-z0-9]([a-z0-9\-\.]*[a-z0-9])?$/i', $domain)) {
            throw new \Exception('Некорректный формат имени домена');
        }
        return $domain;
    }

    /**
     * Build safe path from validated components
     */
    protected function buildSafePath(string ...$parts): string
    {
        $path = implode('/', $parts);
        // Prevent path traversal
        if (str_contains($path, '..')) {
            throw new \Exception('Обнаружена попытка обхода пути');
        }
        return $path;
    }

    /**
     * Deploy project to domain
     */
    public function deployProject(SiteProject $project, SiteDeployment $deployment): array
    {
        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            return [
                'success' => false,
                'message' => 'Сервер для домена не найден',
            ];
        }

        $this->currentServer = $server;

        try {
            // Connect to server
            $this->connect($server);

            // Ensure nginx sites include exists
            $this->ensureNginxSitesInclude();

            // Create directory for domain
            $domainPath = "/var/www/{$domain->domain_name}";
            $this->createDomainDirectory($domainPath);

            // Reconnect before file upload to ensure clean SFTP state
            $this->disconnect();
            $this->connect($server);

            // Upload site files
            $this->uploadSiteFiles($project, $domainPath);

            // Reconnect before nginx config to ensure clean state
            $this->disconnect();
            $this->connect($server);

            // Configure Nginx in Docker
            $this->configureNginx($domain->domain_name, $domainPath);

            // Disconnect before SSL (to avoid channel issues)
            $this->disconnect();

            // Setup SSL (reconnects internally)
            $sslInstalled = $this->setupSsl($domain->domain_name, $server);

            return [
                'success' => true,
                'message' => 'Деплой завершён',
                'ssl_installed' => $sslInstalled,
            ];

        } catch (\Exception $e) {
            Log::error('DeployService error', [
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            $this->disconnect();

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Connect to server via SSH/SFTP
     */
    protected function connect(Server $server): void
    {
        $port = $server->ssh_port ?: 22;
        $this->sftp = new SFTP($server->ip_address, $port);

        // Try SSH key first
        if ($server->ssh_private_key) {
            $key = PublicKeyLoader::load($server->ssh_private_key);
            if (!$this->sftp->login($server->ssh_username, $key)) {
                throw new \Exception('SSH аутентификация по ключу не удалась');
            }
        }
        // Fall back to password
        elseif ($server->ssh_password) {
            $password = $server->ssh_password_decrypted;
            if (!$this->sftp->login($server->ssh_username, $password)) {
                throw new \Exception('SSH аутентификация по паролю не удалась');
            }
        }
        else {
            throw new \Exception('SSH учётные данные не настроены');
        }

        Log::info('Connected to server', ['ip' => $server->ip_address]);
    }

    /**
     * Disconnect from server
     */
    protected function disconnect(): void
    {
        if ($this->sftp) {
            $this->sftp->disconnect();
            $this->sftp = null;
        }
    }

    /**
     * Ensure nginx sites include file exists
     */
    protected function ensureNginxSitesInclude(): void
    {
        $includeContent = 'include /etc/nginx/conf.d/sites/*.conf;';
        $checkCmd = "docker exec sites_web cat /etc/nginx/conf.d/sites.conf 2>/dev/null || echo 'NOT_FOUND'";
        $result = trim($this->sftp->exec($checkCmd));
        $this->sftp->reset(); // Reset channel after exec

        if ($result === 'NOT_FOUND' || strpos($result, 'include') === false) {
            $this->sftp->exec("docker exec sites_web sh -c 'echo \"$includeContent\" > /etc/nginx/conf.d/sites.conf'");
            $this->sftp->reset(); // Reset channel after exec
            Log::info('Created nginx sites include file');
        }
    }

    /**
     * Create domain directory structure
     */
    protected function createDomainDirectory(string $path): void
    {
        $this->sftp->mkdir($path, 0755, true);
        $this->sftp->mkdir($path . '/public_html', 0755, true);
        $this->sftp->mkdir($path . '/logs', 0755, true);

        Log::info('Created domain directory', ['path' => $path]);
    }

    /**
     * Upload site files to server
     */
    protected function uploadSiteFiles(SiteProject $project, string $domainPath): void
    {
        $storagePath = $project->storage_path;

        // Laravel 11 saves to storage/app/private/ by default
        $localBasePath = storage_path('app/private/' . $storagePath);

        // Fallback to old path for backwards compatibility
        if (!is_dir($localBasePath)) {
            $localBasePath = storage_path('app/' . $storagePath);
        }

        if (!is_dir($localBasePath)) {
            throw new \Exception('Файлы проекта не найдены в хранилище');
        }

        $targetPath = $domainPath . '/public_html';
        $this->uploadDirectory($localBasePath, $targetPath);

        Log::info('Site files uploaded', [
            'from' => $localBasePath,
            'to' => $targetPath,
        ]);
    }

    /**
     * Recursively upload directory
     */
    protected function uploadDirectory(string $localPath, string $remotePath): void
    {
        $files = scandir($localPath);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $localFilePath = $localPath . '/' . $file;
            $remoteFilePath = $remotePath . '/' . $file;

            if (is_dir($localFilePath)) {
                $this->sftp->mkdir($remoteFilePath, 0755, true);
                $this->uploadDirectory($localFilePath, $remoteFilePath);
            } else {
                $content = file_get_contents($localFilePath);
                $this->sftp->put($remoteFilePath, $content);
            }
        }
    }

    /**
     * Configure Nginx for domain (Docker version)
     */
    protected function configureNginx(string $domainName, string $domainPath): void
    {
        $nginxConfig = $this->generateNginxConfig($domainName, $domainPath);

        // Write Nginx config to sites directory (mounted in Docker)
        $configPath = "/var/www/nginx-sites/{$domainName}.conf";
        $this->sftp->put($configPath, $nginxConfig);

        // Reload Nginx inside Docker container
        $reloadResult = $this->sftp->exec('docker exec sites_web nginx -s reload 2>&1');
        $this->sftp->reset(); // Reset channel after exec

        Log::info('Nginx configured (Docker)', [
            'domain' => $domainName,
            'reload_result' => $reloadResult,
        ]);
    }

    /**
     * Generate Nginx configuration for domain
     */
    protected function generateNginxConfig(string $domainName, string $domainPath): string
    {
        return <<<NGINX
server {
    listen 80;
    listen [::]:80;

    server_name {$domainName} www.{$domainName};
    root {$domainPath}/public_html;

    index index.php index.html index.htm;

    access_log /var/log/nginx/{$domainName}_access.log;
    error_log /var/log/nginx/{$domainName}_error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    location = /robots.txt {
        log_not_found off;
        access_log off;
    }
}
NGINX;
    }

    /**
     * Setup SSL certificate via Let's Encrypt
     */
    protected function setupSsl(string $domainName, Server $server): bool
    {
        try {
            // Fresh connection for SSL setup
            $this->connect($server);

            // Check if certificate already exists
            $certPath = $this->findCertificatePath($domainName);

            if ($certPath) {
                // Certificate exists, just update nginx config
                Log::info('Certificate already exists, using existing', [
                    'domain' => $domainName,
                    'path' => $certPath,
                ]);

                $this->updateNginxWithSsl($domainName, $certPath);
                $this->disconnect();
                return true;
            }

            // No certificate found, need to request one
            // Stop container temporarily to free port 80 for certbot
            $stopResult = $this->sftp->exec('docker stop sites_web 2>&1');
            $this->sftp->reset(); // Reset channel after exec
            Log::info('Docker stop result', ['output' => $stopResult]);

            // Small delay to ensure port is freed
            sleep(2);

            // Disconnect and reconnect to avoid channel issues
            $this->disconnect();
            $this->connect($server);

            // Run certbot standalone
            $safeDomain = $this->validateDomainName($domainName);
            $command = "certbot certonly --standalone -d " . $this->escapeShellArg($safeDomain) . " --non-interactive --agree-tos --email admin@" . $this->escapeShellArg($safeDomain) . " 2>&1";
            $result = $this->sftp->exec($command);
            $this->sftp->reset(); // Reset channel after exec

            Log::info('Certbot output', [
                'domain' => $domainName,
                'output' => $result,
            ]);

            // Start container again
            $this->disconnect();
            $this->connect($server);
            $startResult = $this->sftp->exec('docker start sites_web 2>&1');
            $this->sftp->reset(); // Reset channel after exec
            Log::info('Docker start result', ['output' => $startResult]);

            // Check if SSL was installed successfully
            $sslSuccess = strpos($result, 'Successfully') !== false
                || strpos($result, 'Congratulations') !== false;

            if ($sslSuccess) {
                // Find the correct certificate path
                $certPath = $this->findCertificatePath($domainName);

                if ($certPath) {
                    $this->updateNginxWithSsl($domainName, $certPath);
                    $this->disconnect();
                    return true;
                }
            }

            $this->disconnect();

            // If certbot failed, log it but don't fail the deployment
            Log::warning('SSL installation failed', [
                'domain' => $domainName,
                'output' => $result,
            ]);

            return false;

        } catch (\Exception $e) {
            // Make sure container is started even if SSL fails
            try {
                $this->disconnect();
                $this->connect($server);
                $this->sftp->exec('docker start sites_web 2>&1');
                $this->sftp->reset(); // Reset channel after exec
                $this->disconnect();
            } catch (\Exception $innerE) {
                Log::error('Failed to restart container after SSL error', ['error' => $innerE->getMessage()]);
            }

            Log::error('SSL setup error', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Find the correct certificate path for domain
     */
    protected function findCertificatePath(string $domainName): ?string
    {
        $safeDomain = $this->validateDomainName($domainName);

        // Check for exact match first
        $exactPath = "/etc/letsencrypt/live/{$safeDomain}";
        $checkExact = $this->sftp->exec("test -d " . $this->escapeShellArg($exactPath) . " && echo 'EXISTS' || echo 'NOT_FOUND'");
        $this->sftp->reset(); // Reset channel after exec

        if (strpos($checkExact, 'EXISTS') !== false) {
            return $exactPath;
        }

        // Check for numbered versions (e.g., domain.com-0001)
        $escapedDomain = $this->escapeShellArg("/etc/letsencrypt/live/{$safeDomain}");
        // Remove trailing quote and add wildcard, then re-close quote
        $findCmd = "ls -d " . substr($escapedDomain, 0, -1) . "*' 2>/dev/null | sort -r | head -1";
        $foundPath = trim($this->sftp->exec($findCmd));
        $this->sftp->reset(); // Reset channel after exec

        if (!empty($foundPath) && strpos($foundPath, $safeDomain) !== false) {
            Log::info('Found certificate path', ['domain' => $domainName, 'path' => $foundPath]);
            return $foundPath;
        }

        Log::warning('Certificate path not found', ['domain' => $domainName]);
        return null;
    }

    /**
     * Update Nginx config with SSL
     */
    protected function updateNginxWithSsl(string $domainName, string $certPath = null): void
    {
        $domainPath = "/var/www/{$domainName}";
        $certPath = $certPath ?: "/etc/letsencrypt/live/{$domainName}";

        $nginxConfig = <<<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name {$domainName} www.{$domainName};
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name {$domainName} www.{$domainName};
    root {$domainPath}/public_html;

    ssl_certificate {$certPath}/fullchain.pem;
    ssl_certificate_key {$certPath}/privkey.pem;

    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    index index.php index.html index.htm;

    access_log /var/log/nginx/{$domainName}_access.log;
    error_log /var/log/nginx/{$domainName}_error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }

    location = /robots.txt {
        log_not_found off;
        access_log off;
    }
}
NGINX;

        $configPath = "/var/www/nginx-sites/{$domainName}.conf";
        $this->sftp->put($configPath, $nginxConfig);

        // Reload Nginx inside Docker container
        $this->sftp->exec('docker exec sites_web nginx -s reload 2>&1');
        $this->sftp->reset(); // Reset channel after exec

        Log::info('Nginx SSL config updated', ['domain' => $domainName]);
    }

    /**
     * Remove site from server
     */
    public function removeSite(string $domainName, Server $server): array
    {
        try {
            $this->connect($server);
            $safeDomain = $this->validateDomainName($domainName);

            // Remove site directory
            $domainPath = "/var/www/{$safeDomain}";
            $rmResult = $this->sftp->exec("rm -rf " . $this->escapeShellArg($domainPath) . " 2>&1");
            $this->sftp->reset(); // Reset channel after exec

            // Remove nginx config
            $configPath = "/var/www/nginx-sites/{$safeDomain}.conf";
            $this->sftp->exec("rm -f " . $this->escapeShellArg($configPath) . " 2>&1");
            $this->sftp->reset(); // Reset channel after exec

            // Reload nginx
            $this->sftp->exec('docker exec sites_web nginx -s reload 2>&1');
            $this->sftp->reset(); // Reset channel after exec

            $this->disconnect();

            Log::info('Site removed from server', [
                'domain' => $domainName,
                'server' => $server->ip_address,
            ]);

            return [
                'success' => true,
                'message' => 'Сайт удалён с сервера',
            ];

        } catch (\Exception $e) {
            $this->disconnect();

            Log::error('Failed to remove site', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deploy black site - create newspage/index.php with Keitaro tracking code
     */
    protected function deployBlackSite(DomainDeployment $deployment, string $domainPath): void
    {
        $newsPagePath = $domainPath . '/public_html/newspage';

        // Create newspage directory
        $this->sftp->mkdir($newsPagePath, 0755, true);

        // Create index.php with Keitaro tracking code
        $indexContent = $this->createTrackingIndexFile($deployment);
        $this->sftp->put($newsPagePath . '/index.php', $indexContent);

        Log::info('Black site deployed', [
            'domain' => $deployment->domain->domain_name,
            'path' => $newsPagePath,
        ]);
    }

    /**
     * Create index.php file with Keitaro tracking code
     * Falls back to mainpage.php if redirect doesn't happen
     */
    protected function createTrackingIndexFile(DomainDeployment $deployment): string
    {
        $config = $deployment->tracking_config ?? [];

        if (empty($config['khost']) || empty($config['kapitoken'])) {
            throw new \Exception('Конфиг трекинга неполный: khost и kapitoken обязательны');
        }

        $khost = $config['khost'];
        $kapitoken = $config['kapitoken'];

        $content = <<<'PHP'
<?php
require_once '../../Xn4z.php';
$client = new KClient('__KHOST__', '__KAPITOKEN__');
$client->sendAllParams();
$client->forceRedirectOffer();
$client->param('gclid', $_GET['gclid'] ?? "");
$client->param('sub_id_9', $_SERVER['SERVER_NAME']);
$client->executeAndBreak();

// Fallback: if redirect didn't happen, show mainpage with fixed paths
ob_start();
include __DIR__ . '/../mainpage.php';
$html = ob_get_clean();

// Add <base href="/"> to fix relative paths (css, js, images)
$html = preg_replace('/<head([^>]*)>/i', '<head$1><base href="/">', $html, 1);
echo $html;
?>
PHP;

        $content = str_replace('__KHOST__', $khost, $content);
        $content = str_replace('__KAPITOKEN__', $kapitoken, $content);

        return $content;
    }

    /**
     * Upload tracking files (Xn4z.php) to server
     * Uploaded to domain root (outside public_html) for security
     */
    protected function uploadTrackingFiles(string $domainPath): void
    {
        $xn4zPath = resource_path('tracking/Xn4z.php');
        if (!file_exists($xn4zPath)) {
            throw new \Exception('Файл трекинга Xn4z.php не найден: resources/tracking/Xn4z.php');
        }

        // Upload Xn4z.php to domain root (outside public_html)
        $content = file_get_contents($xn4zPath);
        $this->sftp->put($domainPath . '/Xn4z.php', $content);

        Log::info('Tracking files uploaded', ['path' => $domainPath . '/Xn4z.php', 'source' => $xn4zPath]);
    }

    /**
     * Install Palladium filter as main index.php
     * Renames existing index.php to mainpage.php (only if mainpage.php doesn't exist)
     */
    protected function installPalladiumFilter(DomainDeployment $deployment, string $domainPath): void
    {
        $publicHtmlPath = $domainPath . '/public_html';
        $config = $deployment->palladium_config ?? [];

        // Validate Palladium config
        if (empty($config['client_id']) || empty($config['client_company']) || empty($config['client_secret'])) {
            throw new \Exception('Конфиг Palladium неполный: client_id, client_company и client_secret обязательны');
        }

        $safePublicPath = $this->escapeShellArg($publicHtmlPath);

        // Step 1: Check if mainpage.php already exists (white site already renamed)
        // Only rename if mainpage.php doesn't exist - otherwise we'd overwrite the white site
        $checkResult = trim($this->sftp->exec("cd {$safePublicPath} && test -f mainpage.php && echo 'EXISTS' || echo 'NOT_EXISTS'"));
        $this->sftp->reset();

        if (strpos($checkResult, 'NOT_EXISTS') !== false) {
            // mainpage.php doesn't exist - rename original index.php to mainpage.php
            $renameCmd = "cd {$safePublicPath} && if [ -f index.php ]; then mv index.php mainpage.php; fi";
            $this->sftp->exec($renameCmd);
            $this->sftp->reset();

            // Also handle index.html
            $renameHtmlCmd = "cd {$safePublicPath} && if [ -f index.html ]; then mv index.html mainpage.html; fi";
            $this->sftp->exec($renameHtmlCmd);
            $this->sftp->reset();

            Log::info('Renamed original index to mainpage', ['path' => $publicHtmlPath]);
        } else {
            Log::info('mainpage.php already exists, skipping rename (white site preserved)', ['path' => $publicHtmlPath]);
        }

        // Step 2: Load Palladium filter template
        $filterPath = resource_path('tracking/palladium_filter.php');
        if (!file_exists($filterPath)) {
            throw new \Exception('Шаблон фильтра Palladium не найден: resources/tracking/palladium_filter.php');
        }

        $filterContent = file_get_contents($filterPath);

        // Step 3: Replace placeholders with actual config values
        $replacements = [
            '__PALLADIUM_CLIENT_ID__' => $config['client_id'],
            '__PALLADIUM_CLIENT_COMPANY__' => $config['client_company'],
            '__PALLADIUM_CLIENT_SECRET__' => $config['client_secret'],
            '__PALLADIUM_BANNER_SOURCE__' => $config['banner_source'] ?? 'adwords',
        ];

        foreach ($replacements as $placeholder => $value) {
            $filterContent = str_replace($placeholder, $value, $filterContent);
        }

        // Step 4: Upload filter as new index.php
        $this->sftp->put($publicHtmlPath . '/index.php', $filterContent);

        Log::info('Palladium filter installed', [
            'path' => $publicHtmlPath,
            'client_id' => $config['client_id'],
        ]);
    }

    /**
     * Add black site to existing deployment
     * Palladium filter is ALWAYS installed
     * tracking_type determines: keitaro (with Xn4z.php) or offer (ZIP without Xn4z.php)
     */
    public function attachBlackSite(DomainDeployment $deployment): array
    {
        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            return ['success' => false, 'message' => 'Сервер не найден'];
        }

        try {
            $this->connect($server);

            $domainPath = $deployment->server_path ?: "/var/www/{$domain->domain_name}";

            // Install Palladium filter FIRST (ALWAYS required now)
            // This renames index.php -> mainpage.php and uploads filter as index.php
            $this->installPalladiumFilter($deployment, $domainPath);
            $deployment->addLog('info', 'Фильтр Palladium установлен');

            // Check tracking type
            if ($deployment->isOfferType()) {
                // Offer type: deploy offer ZIP to newspage/, NO Xn4z.php
                return $this->deployOfferBlackSite($deployment, $domainPath);
            }

            // Keitaro type: deploy newspage with Keitaro tracking + Xn4z.php
            $this->deployBlackSite($deployment, $domainPath);
            $deployment->addLog('info', 'Black сайт создан в newspage/');

            // Upload tracking files (Xn4z.php) to domain root - ONLY for Keitaro type
            $this->uploadTrackingFiles($domainPath);
            $deployment->addLog('info', 'Файл трекинга Xn4z.php загружен');

            $this->disconnect();

            $deployment->addLog('success', 'Black сайт успешно подключён');

            return [
                'success' => true,
                'message' => 'Black сайт успешно подключён',
            ];

        } catch (\Exception $e) {
            $this->disconnect();

            Log::error('Attach black site error', [
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deploy offer black site (ZIP extraction to newspage/)
     * Palladium filter is already installed by attachBlackSite()
     * NO Xn4z.php for offer type - just extract ZIP to newspage/
     */
    protected function deployOfferBlackSite(DomainDeployment $deployment, string $domainPath): array
    {
        $domain = $deployment->domain;
        $offer = $deployment->offer;

        if (!$offer) {
            throw new \Exception('Оффер для этого деплоя не найден');
        }

        $deployment->addLog('info', 'Деплой оффера: ' . $offer->name);

        $publicHtmlPath = $domainPath . '/public_html';
        $newsPagePath = $publicHtmlPath . '/newspage';

        // Create newspage directory
        $this->sftp->mkdir($newsPagePath, 0755, true);
        $deployment->addLog('info', 'Создана директория newspage');

        // Get the ZIP file path
        $zipPath = $offer->full_zip_path;
        if (!$zipPath || !file_exists($zipPath)) {
            throw new \Exception('ZIP файл оффера не найден: ' . $offer->zip_file_path);
        }

        // Create temp directory on server for ZIP extraction
        $tempDir = "/tmp/offer_" . intval($deployment->id) . "_" . time();
        $safeTempDir = $this->escapeShellArg($tempDir);
        $this->sftp->exec("mkdir -p {$safeTempDir}");
        $this->sftp->reset();

        // Upload ZIP file to server
        $remoteZipPath = $tempDir . '/offer.zip';
        $zipContent = file_get_contents($zipPath);
        $this->sftp->put($remoteZipPath, $zipContent);
        $deployment->addLog('info', 'ZIP файл загружен на сервер');

        // Extract ZIP to newspage/
        $safeNewsPagePath = $this->escapeShellArg($newsPagePath);
        $extractCmd = "cd {$safeTempDir} && unzip -o offer.zip && cp -r ./* {$safeNewsPagePath}/ 2>/dev/null; rm -f {$safeNewsPagePath}/offer.zip";
        $this->sftp->exec($extractCmd);
        $this->sftp->reset();
        $deployment->addLog('info', 'ZIP распакован в newspage/');

        // Clean up temp directory
        $this->sftp->exec("rm -rf {$safeTempDir}");
        $this->sftp->reset();

        // NO Xn4z.php for offer type - tracking is not needed

        $this->disconnect();

        $deployment->addLog('success', 'Оффер успешно задеплоен');

        Log::info('Offer black site deployed', [
            'domain' => $domain->domain_name,
            'offer' => $offer->name,
        ]);

        return [
            'success' => true,
            'message' => 'Оффер black сайта успешно задеплоен',
        ];
    }

    /**
     * Remove only black site components (keep white site intact)
     * - Remove newspage/ folder
     * - Remove Palladium filter (index.php)
     * - Restore original index (mainpage.php -> index.php)
     * - Remove Xn4z.php tracking file
     */
    public function removeBlackSite(string $domainName, Server $server): array
    {
        try {
            $this->connect($server);
            $safeDomain = $this->validateDomainName($domainName);

            $domainPath = "/var/www/{$safeDomain}";
            $publicHtmlPath = $domainPath . '/public_html';
            $safePublicPath = $this->escapeShellArg($publicHtmlPath);

            // 1. Remove newspage/ folder
            $this->sftp->exec("rm -rf " . $this->escapeShellArg($publicHtmlPath . '/newspage'));
            $this->sftp->reset();

            // 2. Remove Xn4z.php tracking file (from domain root)
            $this->sftp->exec("rm -f " . $this->escapeShellArg($domainPath . '/Xn4z.php'));
            $this->sftp->reset();

            // 3. Remove Palladium filter and restore original index files
            $this->sftp->exec("rm -f " . $this->escapeShellArg($publicHtmlPath . '/index.php'));
            $this->sftp->reset();

            // Restore original white site index
            $this->sftp->exec("cd {$safePublicPath} && if [ -f mainpage.php ]; then mv mainpage.php index.php; fi");
            $this->sftp->reset();
            $this->sftp->exec("cd {$safePublicPath} && if [ -f mainpage.html ]; then mv mainpage.html index.html; fi");
            $this->sftp->reset();

            $this->disconnect();

            Log::info('Black site removed', ['domain' => $domainName]);

            return [
                'success' => true,
                'message' => 'Black сайт удалён, white сайт восстановлен',
            ];

        } catch (\Exception $e) {
            $this->disconnect();

            Log::error('Remove black site error', [
                'domain' => $domainName,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Retry SSL installation for a deployment
     * Checks if certificate exists on server, if not - installs it
     */
    public function retrySsl(SiteDeployment $deployment): array
    {
        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            return [
                'success' => false,
                'message' => 'Сервер для домена не найден',
            ];
        }

        try {
            $domainName = $domain->domain_name;

            Log::info('Retrying SSL installation', ['domain' => $domainName]);

            // Use existing setupSsl method
            $sslInstalled = $this->setupSsl($domainName, $server);

            if ($sslInstalled) {
                return [
                    'success' => true,
                    'message' => 'SSL сертификат успешно установлен',
                    'ssl_installed' => true,
                ];
            }

            return [
                'success' => false,
                'message' => 'Не удалось установить SSL. Проверьте что DNS домена указывает на сервер.',
                'ssl_installed' => false,
            ];

        } catch (\Exception $e) {
            $this->disconnect();

            Log::error('SSL retry error', [
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'ssl_installed' => false,
            ];
        }
    }

    /**
     * Update only black site config files (without reinstalling)
     * - Updates newspage/index.php with new Keitaro config
     * - Does NOT update Palladium config (read-only after deployment)
     * - Only works for keitaro tracking type
     */
    public function updateBlackSiteConfig(DomainDeployment $deployment): array
    {
        $domain = $deployment->domain;
        $server = $domain->server;

        if (!$server) {
            return ['success' => false, 'message' => 'Сервер не найден'];
        }

        // Only keitaro type can be updated (they have Keitaro config)
        if (!$deployment->isKeitaroType()) {
            return ['success' => false, 'message' => 'Только деплои с Keitaro можно обновлять'];
        }

        try {
            $this->connect($server);

            $domainPath = $deployment->server_path ?: "/var/www/{$domain->domain_name}";
            $publicHtmlPath = $domainPath . '/public_html';

            // Update only newspage/index.php with new Keitaro config
            // Palladium config is NOT updated (read-only after deployment)
            $trackingIndexContent = $this->createTrackingIndexFile($deployment);
            $this->sftp->put($publicHtmlPath . '/newspage/index.php', $trackingIndexContent);
            $deployment->addLog('info', 'Обновлён newspage/index.php с новым конфигом Keitaro');

            $this->disconnect();

            $deployment->addLog('success', 'Конфиг Keitaro обновлён на сервере');

            return [
                'success' => true,
                'message' => 'Конфиг Keitaro успешно обновлён',
            ];

        } catch (\Exception $e) {
            $this->disconnect();

            Log::error('Update black site config error', [
                'domain' => $domain->domain_name,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

}
