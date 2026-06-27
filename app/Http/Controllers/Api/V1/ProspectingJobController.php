<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProspectingJobRequest;
use App\Jobs\ProcessProspectingJob;
use App\Models\ProspectingJob;
use App\Services\GeocodingService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProspectingJobController extends Controller
{
    protected  GeocodingService $geocodingService;

    public function __construct(GeocodingService $geocodingService)
    {
        $this->geocodingService = $geocodingService;
    }

    public function store(StoreProspectingJobRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message'  => 'Unauthenticated.'], 401);
        }

        $lat = $request->center_lat;
        $lng = $request->center_lng;
        $addressText = $request->address_text;

        if (empty($lat) || empty($lng)) {
            if (empty($addressText)) {
                return response()->json([
                    'message'   => 'Validasi gagal. Anda harus menyertakan koordinat Map Picker atau teks alamat.'
                ], 422);
            }

            try {
                $coordinates = $this->geocodingService->geocodeAddress($addressText);
                $lat = $coordinates['lat'];
                $lng = $coordinates['lng'];
            } catch (Exception $e) {
                return response()->json([
                    'message'   => $e->getMessage()
                ], 422);
            }
        }

        $job = ProspectingJob::create([
            'tenant_id'           => $user->tenant_id,
            'user_id'             => $user->id,
            'product_name'        => $request->product_name,
            'product_description' => $request->product_description,
            'product_usp'         => $request->product_usp,
            'center_lat'          => $lat,
            'center_lng'          => $lng,
            'address_text'        => $request->address_text,
            'radius_meters'       => $request->radius_meters,
            'target_keywords'     => $request->target_keywords,
            'status'              => 'pending',
            'credits_used'        => 0,
        ]);

        $job->results()->create([
            'business_name' => 'Kopi Bento Kaliurang',
            'address_text'  => $request->address_text,
            'phone_number'  => '0274-123456',
            'website_url'   => 'https://kopibentoexample.com',
            'rating'        => 4.6,
            'review_count'  => 120,
            'lat'           => $lat,
            'lng'           => $lng,
            'geom'          => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 4326)")
        ]);

        ProcessProspectingJob::dispatch($job->id);

        return response()->json([
            'message'   => 'Prospecting job berhasil dibuat',
            'data'      => $job
        ], 201);
    }

    public function show(int $id)
    {
        $job = ProspectingJob::with('results')->find($id);

        if (!$job) {
            return response()->json([
                'message'   => 'Prospecting job tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'message' => 'Data prospecting job berhasil diambil.',
            'data'    => $job
        ], 200);
    }
}
