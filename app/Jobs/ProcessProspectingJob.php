<?php

namespace App\Jobs;

use App\Models\ProspectingJob;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessProspectingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $prospectingJob = ProspectingJob::find($this->jobId);

        if (!$prospectingJob) {
            return;
        }

        $prospectingJob->update(['status'   => 'processing']);

        try {
            $mockedPlaces = [
                [
                    'name' => 'Kopi Bento Kaliurang',
                    'phone' => '0274-123456',
                    'web' => 'https://kopibento.com',
                    'rating' => 4.6,
                    'reviews' => 120,
                    'lat' => $prospectingJob->center_lat ?? -7.7829,
                    'lng' => $prospectingJob->center_lng ?? 110.3671
                ],
                [
                    'name' => 'Merapi Coffee Space',
                    'phone' => '0274-654321',
                    'web' => 'https://merapicoffee.com',
                    'rating' => 4.8,
                    'reviews' => 95,
                    'lat' => ($prospectingJob->center_lat ?? -7.7829) + 0.001,
                    'lng' => ($prospectingJob->center_lng ?? 110.3671) + 0.001
                ]
            ];

            foreach ($mockedPlaces as $place) {
                $prospectingJob->results()->create([
                    'business_name' => $place['name'],
                    'address_text'  => $prospectingJob->address_text ?? 'Alamat Terdekat',
                    'phone_number'  => $place['phone'],
                    'website_url'   => $place['web'],
                    'rating'        => $place['rating'],
                    'review_count'  => $place['reviews'],
                    'lat'           => $place['lat'],
                    'lng'           => $place['lng'],
                    'geom'          => DB::raw("ST_GeomFromText('POINT({$place['lng']} {$place['lat']})', 4326)")
                ]);
            }

            $prospectingJob->update([
                'status'    => 'completed',
                'credit_used'   => count($mockedPlaces)
            ]);
        } catch (Exception $e) {
            $prospectingJob->update(['status'    => 'failed']);
            throw $e;
        }
    }
}
