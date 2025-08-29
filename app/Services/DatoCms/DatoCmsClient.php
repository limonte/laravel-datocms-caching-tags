<?php

namespace App\Services\DatoCms;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DatoCmsClient
{
    private const API_BASE_URL = 'https://graphql.datocms.com';
    private Client $client;

    public function __construct(
        private readonly string $apiToken,
    ) {
        $this->client = new Client([
            'base_uri' => self::API_BASE_URL,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function query(string $query, array $variables = []): array
    {
        if (empty($this->apiToken)) {
            throw new RuntimeException('DatoCMS API token is not configured.');
        }

        // 1. check if the query is cached
        $cacheKey = md5($query . json_encode($variables));
        $cachedResult = Cache::get($cacheKey);
        if ($cachedResult) {
            Log::info('🔽 Returning cached result for query', ['cacheKey' => $cacheKey]);
            return $cachedResult;
        }

        // 2. not cached, execute the query
        Log::info('⚡️ Executing DatoCMS query', ['query' => $query, 'cacheKey' => $cacheKey]);
        $result = $this->executeQuery($query, $variables);

        // 3. cache tag1 -> cacheKey, tag2 -> cacheKey, ...
        // in webhook we'll be invalidating cache by tags
        $cacheTags = $result['cacheTags'];
        foreach ($cacheTags as $tag) {
            Log::info('🏷️ Caching tag', ['tag' => $tag, 'cacheKey' => $cacheKey]);
            Cache::put($tag, $cacheKey);
        }
        unset($result['cacheTags']);

        // 4. cache cacheKey -> result
        Log::info('🔼 Caching result for query', ['cacheKey' => $cacheKey]);
        Cache::put($cacheKey, $result);

        // 4. return the result
        return $result;
    }

    private function executeQuery(string $query, array $variables): array
    {
        $headers = [
          'X-Cache-Tags' => 'true',
        ];

        $response = $this->client->post('', [
            'headers' => $headers,
            'json' => [
                'query' => $query,
                // 'variables' => $variables,
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (isset($data['errors'])) {
            throw new RuntimeException(
                'DatoCMS GraphQL Error: ' . json_encode($data['errors'])
            );
        }

        $result = $data['data'] ?? [];

        $result['cacheTags'] = array_filter(explode(' ', implode($response->getHeader('X-Cache-Tags'))), 'strlen');

        return $result;
    }
}
