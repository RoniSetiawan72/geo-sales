<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class GeocodingService
{
    /**
     * Mengonversi teks alamat menjadi koordinat Latitude & Longitude menggunakan OpenStreetMap (Nominatim)
     */
    public function geocodeAddress(string $address): array
    {
        $uniqueUserAgent = 'GeoSalesProspectingApp-RoniSetiawan-v1.0 (contact: ronisetiawan1099@gmail.com)';

        $apiKey = config('services.locationiq.api_key');

        if (!$apiKey) {
            throw new Exception('LocationIQ API Key belum dikonfigurasi di file env.');
        }

        // $response = Http::withoutVerifying()
        // ->timeout(10)
        // ->withHeaders([
        //     'User-Agent' => $uniqueUserAgent
        // ])->get('https://nominatim.openstreetmap.org/search', [
        //     'q'      => $address,
        //     'format' => 'json',
        //     'limit'  => 1
        // ]);

        $response = Http::get('https://us1.locationiq.com/v1/search', [
            'key'    => $apiKey,
            'q'      => $address,
            'format' => 'json',
            'limit'  => 1
        ]);

        if ($response->failed()) {
            throw new Exception('Gagal terhubung ke layanan Geocoding OpenStreetMap. Status: ' . $response->status());
        }

        $data = $response->json();

        if (empty($data) || !isset($data[0])) {
            throw new Exception('Alamat tidak ditemukan atau kurang spesifik.');
        }

        return [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon'],
        ];
    }
}
