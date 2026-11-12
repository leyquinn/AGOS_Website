<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherController extends Controller
{
    public function show(): JsonResponse
    {
        $fallback = [
            'location' => 'Davao City',
            'temperature' => 28.5,
            'condition' => 'Partly cloudy',
            'icon_url' => '',
            'high' => 32.5,
            'low' => 26.2,
            'rain_chance' => 20,
            'rainfall_signal' => 'No warning',
            'updated_at' => now('Asia/Manila')->toIso8601String(),
            'fetched_at' => now('Asia/Manila')->toIso8601String(),
        ];

        $apiKey = config('services.weatherapi.key');
        if (! is_string($apiKey) || trim($apiKey) === '') {
            return response()->json($fallback);
        }

        try {
            $data = Cache::remember('davao_weather_forecast', 1800, function () use ($apiKey, $fallback) {
                $response = Http::acceptJson()
                    ->timeout(4)
                    ->get(rtrim(config('services.weatherapi.base_url'), '/').'/forecast.json', [
                        'key' => $apiKey,
                        'q' => 'Davao City, Philippines',
                        'days' => 1,
                        'aqi' => 'no',
                        'alerts' => 'no',
                    ]);

                if (! $response->successful()) {
                    return $fallback;
                }

                $weather = $response->json();
                $current = $weather['current'] ?? [];
                $day = $weather['forecast']['forecastday'][0]['day'] ?? [];
                $condition = $current['condition'] ?? [];
                $maximumRainChance = (int) ($day['daily_chance_of_rain'] ?? 0);

                return [
                    'location' => 'Davao City',
                    'temperature' => round((float) ($current['temp_c'] ?? 28.5), 1),
                    'condition' => (string) ($condition['text'] ?? 'Partly cloudy'),
                    'icon_url' => (string) ($condition['icon'] ?? ''),
                    'high' => round((float) ($day['maxtemp_c'] ?? 32.5), 1),
                    'low' => round((float) ($day['mintemp_c'] ?? 26.2), 1),
                    'rain_chance' => $maximumRainChance,
                    'rainfall_signal' => $maximumRainChance >= 70 ? 'Monitor rainfall' : 'No warning',
                    'updated_at' => $current['last_updated'] ?? now('Asia/Manila')->toIso8601String(),
                    'fetched_at' => now('Asia/Manila')->toIso8601String(),
                ];
            });

            return response()->json($data);
        } catch (Throwable) {
            return response()->json($fallback);
        }
    }
}
