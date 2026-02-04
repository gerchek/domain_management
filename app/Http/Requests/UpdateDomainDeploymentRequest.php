<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDomainDeploymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $deployment = $this->route('deployment');
        return auth()->check() && $deployment->domain->buyer_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'khost' => ['required', 'url'],
            'kapitoken' => ['required', 'string', 'min:8'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'khost.required' => 'Keitaro Host (khost) обязателен',
            'khost.url' => 'Keitaro Host должен быть валидным URL',
            'kapitoken.required' => 'Keitaro Token (kapitoken) обязателен',
            'kapitoken.min' => 'Keitaro Token минимум 8 символов',
        ];
    }
}
