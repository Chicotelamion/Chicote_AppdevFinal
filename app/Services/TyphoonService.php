<?php

namespace App\Services;

class TyphoonService
{
    /**
     * Fetch active global typhoons.
     * In a production environment, this would parse the GDACS RSS feed or NOAA API.
     */
    public function getActiveTyphoons(): array
    {
        // We inject mock typhoons so the feature can be tested immediately, 
        // as real typhoons are seasonal.
        return [
            [
                'id' => 'mock_typhoon_1',
                'name' => 'Super Typhoon AMBO (Mock)',
                'severity' => 'Category 4',
                'lat' => 14.2, // Near Manila
                'lon' => 124.5, // Off the eastern coast
                'wind_speed' => 210, // km/h
                'description' => 'A severe mock typhoon approaching the eastern seaboard of the Philippines.',
            ],
            [
                'id' => 'mock_typhoon_2',
                'name' => 'Tropical Storm HAGIBIS (Mock)',
                'severity' => 'Tropical Storm',
                'lat' => 34.0, // Near Tokyo
                'lon' => 141.0, 
                'wind_speed' => 95, // km/h
                'description' => 'A strong tropical storm moving north.',
            ]
        ];
    }
}
