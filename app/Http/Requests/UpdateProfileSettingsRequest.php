<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSettingsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'background_audio' => ['required', 'boolean'],
            'sound_effects' => ['required', 'boolean'],
            'accessibility_mode' => ['required', 'boolean'],
            'public_profile' => ['required', 'boolean'],
        ];
    }
}
