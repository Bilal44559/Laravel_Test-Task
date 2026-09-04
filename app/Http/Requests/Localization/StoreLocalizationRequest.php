<?php

namespace App\Http\Requests\Localization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreLocalizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<Exists|string>>
     */
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:255'],
            'language' => ['required', 'string', 'max:10', Rule::exists('languages', 'code')],
            'content' => ['required', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'approved', 'archived'])],
            'context' => ['sometimes', 'nullable', 'string'],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string', 'max:50'],
        ];
    }
}
