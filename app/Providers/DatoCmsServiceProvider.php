<?php

namespace App\Providers;

use App\Services\DatoCms\DatoCmsClient;
use Illuminate\Support\ServiceProvider;

class DatoCmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatoCmsClient::class, function ($app) {
            $apiToken = config('datocms.api_token');
            if (empty($apiToken)) {
                throw new \RuntimeException('DatoCMS API token is not configured. Please check your datocms.php config file or .env file.');
            }
            return new DatoCmsClient($apiToken);
        });
    }
}
