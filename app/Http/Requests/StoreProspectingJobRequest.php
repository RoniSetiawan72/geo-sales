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

        $maxRadius = $plan?->max_radius_km ? $plan->max_radius_km * 1000 : 5000;

        return [
            'product_name'        => ['required', 'string', 'max:255'],
            'product_description' => ['nullable', 'string'],
            'product_usp'         => ['nullable', 'string'],
            'center_lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'address_text'        => ['nullable', 'string'],
            'radius_meters'       => ['required', 'integer', 'min:1', "max:{$maxRadius}"],
            'target_keywords'     => ['required', 'array', 'min:1'],
            'target_keywords.*'   => ['string', 'max:50'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'radius_meters.max' => 'Radius maksimal untuk paket Anda adalah :max meter.',
        ];
    }
}
