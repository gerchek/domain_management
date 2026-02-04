<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DynadotService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.dynadot.com/api3.json';

    public function __construct()
    {
        $this->apiKey = Setting::getDynadotApiKey() ?? '';
    }

    public function bulkRegister(array $domains): array
    {
        $params = [
            'key' => $this->apiKey,
            'currency' => 'USD',
            'command' => 'bulk_register',
        ];

        foreach ($domains as $i => $domain) {
            $params["domain{$i}"] = $domain;
        }

        $url = $this->baseUrl . '?' . http_build_query($params);

        // Log request without API key
        Log::info('Dynadot bulk_register request', ['domains' => $domains, 'count' => count($domains)]);

        try {
            $response = Http::timeout(60)
                ->get($url);

            $data = $response->json();

            // Log response without sensitive data
            Log::info('Dynadot bulk_register response', ['status' => $response->status()]);

            return $this->parseBulkRegisterResponse($data, $domains);
        } catch (\Exception $e) {
            Log::error('Dynadot bulk_register error', ['error' => $e->getMessage()]);

            return array_map(function ($domain) use ($e) {
                return [
                    'domain' => $domain,
                    'success' => false,
                    'message' => 'Ошибка API: ' . $e->getMessage(),
                ];
            }, $domains);
        }
    }

    protected function parseBulkRegisterResponse(array $data, array $requestedDomains): array
    {
        $results = [];

        if (!isset($data['BulkRegisterResponse'])) {
            foreach ($requestedDomains as $domain) {
                $results[] = [
                    'domain' => $domain,
                    'success' => false,
                    'message' => 'Некорректный ответ API',
                ];
            }
            return $results;
        }

        $apiResp = $data['BulkRegisterResponse'];
        $status = $apiResp['Status'] ?? null;
        $code = $apiResp['ResponseCode'] ?? null;

        if (strtolower((string)$status) !== 'success' || (int)$code !== 0) {
            $errorMsg = $apiResp['Error'] ?? $apiResp['Message'] ?? 'Unknown API error';
            foreach ($requestedDomains as $domain) {
                $results[] = [
                    'domain' => $domain,
                    'success' => false,
                    'message' => $errorMsg,
                ];
            }
            return $results;
        }

        $domainResults = [];
        if (!empty($apiResp['BulkRegister']) && is_array($apiResp['BulkRegister'])) {
            foreach ($apiResp['BulkRegister'] as $item) {
                $domainName = $item['DomainName'] ?? '';
                $result = $item['Result'] ?? '';
                $message = $item['Message'] ?? '';

                $domainResults[strtolower($domainName)] = [
                    'domain' => $domainName,
                    'success' => strtolower($result) === 'success',
                    'message' => $message,
                ];
            }
        }

        foreach ($requestedDomains as $domain) {
            $key = strtolower($domain);
            if (isset($domainResults[$key])) {
                $results[] = $domainResults[$key];
            } else {
                $results[] = [
                    'domain' => $domain,
                    'success' => false,
                    'message' => 'Домен отсутствует в ответе',
                ];
            }
        }

        return $results;
    }

    public function setDns(array $domains, string $ipAddress): array
    {
        $domainList = implode(',', $domains);

        $params = [
            'key' => $this->apiKey,
            'command' => 'set_dns2',
            'domain' => $domainList,
            'main_record_type0' => 'a',
            'main_record0' => $ipAddress,
            'subdomain0' => 'www',
            'sub_record_type0' => 'a',
            'sub_record0' => $ipAddress,
        ];

        $query = http_build_query($params);
        $query = str_replace('%2C', ',', $query);

        $url = $this->baseUrl . '?' . $query;

        Log::info('Dynadot set_dns2 request', ['domains' => $domains, 'ip' => $ipAddress]);

        try {
            $response = Http::timeout(60)
                ->get($url);

            $data = $response->json();

            // Log response without sensitive data
            Log::info('Dynadot set_dns2 response', ['status' => $response->status()]);

            return $this->parseSetDnsResponse($data, $domains);
        } catch (\Exception $e) {
            Log::error('Dynadot set_dns2 error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Ошибка API: ' . $e->getMessage(),
                'domains' => $domains,
            ];
        }
    }

    protected function parseSetDnsResponse(array $data, array $domains): array
    {
        $apiResp = $data['SetDns2Response'] ?? $data['SetDnsResponse'] ?? null;

        if (!$apiResp) {
            return [
                'success' => false,
                'message' => 'Некорректный ответ API',
                'domains' => $domains,
            ];
        }

        $status = $apiResp['Status'] ?? null;
        $code = $apiResp['ResponseCode'] ?? null;

        if (strtolower((string)$status) === 'success' && (int)$code === 0) {
            return [
                'success' => true,
                'message' => 'DNS успешно настроен',
                'domains' => $domains,
            ];
        }

        $errorMsg = $apiResp['Error'] ?? $apiResp['Message'] ?? 'Unknown error';
        return [
            'success' => false,
            'message' => $errorMsg,
            'domains' => $domains,
        ];
    }
}
