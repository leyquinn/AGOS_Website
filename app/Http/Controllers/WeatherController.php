<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherController extends Controller
{
    public function show(): JsonResponse
    {
        try {
            $apiKey = config('services.weatherapi.key');
            if (! is_string($apiKey) || trim($apiKey) === '') {
                throw new \RuntimeException('WEATHERAPI_KEY is not configured.');
            }

            $weather = Http::acceptJson()
                ->timeout(8)
                ->get(rtrim(config('services.weatherapi.base_url'), '/').'/forecast.json', [
                    'key' => $apiKey,
                    'q' => 'Davao City, Philippines',
                    'days' => 1,
                    'aqi' => 'no',
                    'alerts' => 'no',
                ])
                ->throw()
                ->json();

            $current = $weather['current'] ?? [];
            $day = $weather['forecast']['forecastday'][0]['day'] ?? [];
            $condition = $current['condition'] ?? [];
            $maximumRainChance = (int) ($day['daily_chance_of_rain'] ?? 0);

            return response()->json([
                'location' => 'Davao City',
                'temperature' => round((float) ($current['temp_c'] ?? 0), 1),
                'condition' => (string) ($condition['text'] ?? 'Weather unavailable'),
                'icon_url' => (string) ($condition['icon'] ?? ''),
                'high' => round((float) ($day['maxtemp_c'] ?? 0), 1),
                'low' => round((float) ($day['mintemp_c'] ?? 0), 1),
                'rain_chance' => $maximumRainChance,
                'rainfall_signal' => $maximumRainChance >= 70 ? 'Monitor rainfall' : 'No warning',
                'updated_at' => $current['last_updated'] ?? now('Asia/Manila')->toIso8601String(),
                'fetched_at' => now('Asia/Manila')->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Weather data is temporarily unavailable.',
            ], 503);
        }
    }
}
