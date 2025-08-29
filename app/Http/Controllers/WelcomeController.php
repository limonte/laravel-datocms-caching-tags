<?php

namespace App\Http\Controllers;

use App\Services\DatoCms\DatoCmsClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class WelcomeController
{
    public function __construct(private DatoCmsClient $datoCms) {}

    public function index($locale = 'en')
    {
        $datoCmsResponse = $this->datoCms->query("
            query {
                allLabels(locale: $locale) {
                    code
                    translation
                }
            }
        ");

        $labels = Arr::mapWithKeys($datoCmsResponse['allLabels'], function ($label) {
            return [$label['code'] => $label['translation']];
        });

        return view('welcome', compact('labels'));
    }
}
