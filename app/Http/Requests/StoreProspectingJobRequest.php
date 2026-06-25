<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreProspectingJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $plan = $user?->tenant?->subscriptionPlan;

        $maxRadiusKm = $plan?->max_radius_km ?? 5;
        $maxRadiusMeters = $maxRadiusKm * 1000;

        return [
            'product_name'        => ['required', 'string', 'max:255'],
            'product_description' => ['nullable', 'string'],
            'product_usp'         => ['nullable', 'string'],
            'center_lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'address_text'        => ['nullable', 'string'],
            'target_keywords'     => ['required', 'array', 'min:1'],
            'target_keywords.*'   => ['string', 'max:50'],

            'radius_meters'       => [
                'required',
                'integer',
                'min:1',
                "max:{$maxRadiusMeters}"
            ],
        ];
    }

    #[Override]
    public function messages()
    {
        $user = $this->user();
        $planName = $user?->tenant?->subscriptionPlan?->name ?? 'Starter';

        return [
            'radius_meters.max' => "Radius pencarian melebihi batas maksimum untuk paket {$planName} Anda.",
            'radius_meters.min' => "Radius pencarian minimal adalah 1 meter.",
        ];
    }
}
