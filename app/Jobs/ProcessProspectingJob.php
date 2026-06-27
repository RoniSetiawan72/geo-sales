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

        // Set status ke processing dan catat waktu mulai
        $prospectingJob->update([
            'status' => 'processing',
            'started_at' => now()
        ]);

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

            $prospectingJob->results()->delete();

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

            $collectedResults = $prospectingJob->results()->get();

            $payloadAI = [
                'my_product' => [
                    'name'        => $prospectingJob->product_name,
                    'description' => $prospectingJob->product_description,
                    'usp'         => $prospectingJob->product_usp
                ],
                'target_competitors' => $collectedResults->map(function ($item) {
                    return [
                        'name'         => $item->business_name,
                        'rating'       => $item->rating,
                        'review_count' => $item->review_count,
                        'address'      => $item->address_text
                    ];
                })->toArray(),
            ];

            $isTestingWithoutQuota = true;

            if ($isTestingWithoutQuota) {
                $mockedAiContent = json_encode([
                    'market_overview' => "Analisis area lokal menunjukkan tingkat kompetisi tinggi pada kata kunci " . implode(', ', $prospectingJob->target_keywords),
                    'competitor_weakness' => "Sebagian besar kompetitor belum mengoptimalkan strategi branding siap minum (Ready to Drink).",
                    'positioning_strategy' => "Gunakan USP produk: '{$prospectingJob->product_usp}' untuk penetrasi langsung ke market retail.",
                    'sales_pitch_template' => "Halo, kami menawarkan produk {$prospectingJob->product_name} yang sangat cocok untuk segmentasi bisnis Anda..."
                ]);

                $prospectingJob->update([
                    'status'          => 'completed',
                    'credits_used'    => count($mockedPlaces),
                    'ai_analysis'     => json_decode($mockedAiContent, true),
                    'completed_at'    => now()
                ]);

            } else {
                $aiResponse = \Illuminate\Support\Facades\Http::withToken(config('services.openai.api_key'))
                    ->timeout(30)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => config('services.openai.model'),
                        'response_format' => ['type' => 'json_object'],
                        'messages' => [
                            [
                                'role'    => 'system',
                                'content' => 'Anda adalah AI Sales Strategist Expert. Analisis data produk user dibandingkan dengan data bisnis kompetitor di area tersebut. Berikan rekomendasi taktik penetrasi pasar, kelemahan kompetitor yang bisa dieksploitasi, dan template pesan pitching/penjualan yang spesifik dalam format JSON terstruktur.'
                            ],
                            [
                                'role'    => 'user',
                                'content' => json_encode($payloadAI)
                            ]
                        ]
                    ]);

                if ($aiResponse->successful()) {
                    $aiContent = $aiResponse->json()['choices'][0]['message']['content'];

                    $prospectingJob->update([
                        'status'       => 'completed',
                        'credits_used' => count($mockedPlaces),
                        'ai_analysis'  => json_decode($aiContent, true),
                        'completed_at' => now()
                    ]);
                } else {
                    throw new \Exception('AI Engine gagal merespons dengan sukses: ' . $aiResponse->body());
                }
            }

        } catch (\Exception $e) {
            $prospectingJob->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
