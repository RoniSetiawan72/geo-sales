<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProspectingJobRequest;
use App\Models\ProspectingJob;
use Illuminate\Http\Request;

class ProspectingJobController extends Controller
{
    public function store(StoreProspectingJobRequest $request)
    {
        $user = $request->user();

        $job = ProspectingJob::create([
            'tenant_id'           => $user->tenant_id,
            'user_id'             => $user->id,
            'product_name'        => $request->product_name,
            'product_description' => $request->product_description,
            'product_usp'         => $request->product_usp,
            'center_lat'          => $request->center_lat,
            'center_lng'          => $request->center_lng,
            'address_text'        => $request->address_text,
            'radius_meters'       => $request->radius_meters,
            'target_keywords'     => $request->target_keywords,
            'status'              => 'pending',
            'credits_used'        => 0,
        ]);

        return response()->json([
            'message'   => 'Prospecting job berhasil dibuat',
            'data'      => $job
        ], 201);
    }
}
