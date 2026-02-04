<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainDeploymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'domain_ids' => ['required', 'array', 'min:1'],
            'domain_ids.*' => ['exists:domains,id'],
            // Palladium config is ALWAYS required now
            'palladium_config_id' => ['required', 'exists:palladium_configs,id'],
            // Tracking type: keitaro or offer
            'tracking_type' => ['required', 'in:keitaro,offer'],
        ];

        // Keitaro credentials only required when tracking_type = keitaro
        if ($this->tracking_type === 'keitaro') {
            $rules['khost'] = ['required', 'url'];
            $rules['kapitoken'] = ['required', 'string', 'min:8'];
        }

        // Offer only required when tracking_type = offer
        if ($this->tracking_type === 'offer') {
            $rules['offer_id'] = ['required', 'exists:offers,id'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'domain_ids.required' => 'Выберите хотя бы один домен',
            'domain_ids.min' => 'Выберите хотя бы один домен',
            'palladium_config_id.required' => 'Выберите Palladium конфиг',
            'palladium_config_id.exists' => 'Выбранный Palladium конфиг не существует',
            'tracking_type.required' => 'Выберите тип трекинга',
            'tracking_type.in' => 'Недопустимый тип трекинга',
            'khost.required' => 'Keitaro Host (khost) обязателен для типа Keitaro',
            'khost.url' => 'Keitaro Host должен быть валидным URL',
            'kapitoken.required' => 'Keitaro Token (kapitoken) обязателен для типа Keitaro',
            'kapitoken.min' => 'Keitaro Token минимум 8 символов',
            'offer_id.required' => 'Выберите Оффер',
            'offer_id.exists' => 'Выбранный оффер не существует',
        ];
    }
}
