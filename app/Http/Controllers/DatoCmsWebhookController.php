<?php

namespace App\Http\Controllers;

use App\Services\DatoCms\DatoCmsClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DatoCmsWebhookController
{
    public function __construct(private DatoCmsClient $datoCms) {}

    public function invalidateCache(Request $request): Response
    {
        Log::info('DatoCMS cache invalidation webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $payload = $request->json()->all();

            // Validate the payload structure
            if (!$this->isValidPayload($payload)) {
                Log::warning('Invalid DatoCMS webhook payload structure', [
                    'payload' => $payload,
                ]);

                return response('Invalid payload structure', 400);
            }

            // Extract cache tags from the payload
            $tags = $payload['entity']['attributes']['tags'] ?? [];

            if (empty($tags)) {
                Log::warning('No cache tags found in DatoCMS webhook payload', [
                    'payload' => $payload,
                ]);

                return response('No cache tags found', 400);
            }

            // Invalidate cache for each tag
            $this->invalidateCacheByTags($tags);

            Log::info('DatoCMS cache invalidated successfully', [
                'tags' => $tags,
                'count' => count($tags),
            ]);

            return response('Cache invalidated successfully', 200);

        } catch (\Exception $e) {
            Log::error('Error processing DatoCMS cache invalidation webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    private function isValidPayload(array $payload): bool
    {
        return isset($payload['entity_type']) &&
               $payload['entity_type'] === 'cda_cache_tags' &&
               isset($payload['event_type']) &&
               $payload['event_type'] === 'invalidate' &&
               isset($payload['entity']['type']) &&
               $payload['entity']['type'] === 'cda_cache_tags' &&
               isset($payload['entity']['attributes']);
    }

    private function invalidateCacheByTags(array $tags): void
    {
        $invalidatedQueries = [];

        foreach ($tags as $tag) {
            // Get the cache key(s) associated with this tag
            $cacheKey = Cache::get($tag);

            if ($cacheKey) {
                // Remove the actual query cache
                Cache::forget($cacheKey);
                $invalidatedQueries[] = $cacheKey;

                Log::debug('Invalidated cache for tag', [
                    'tag' => $tag,
                    'cache_key' => $cacheKey,
                ]);
            }

            // Remove the tag mapping itself
            Cache::forget($tag);
        }

        Log::info('Cache invalidation completed', [
            'tags_processed' => count($tags),
            'queries_invalidated' => count($invalidatedQueries),
            'invalidated_queries' => $invalidatedQueries,
        ]);
    }
}
