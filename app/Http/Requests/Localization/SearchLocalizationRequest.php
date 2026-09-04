<?php

namespace App\Http\Requests\Localization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class SearchLocalizationRequest extends FormRequest
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
            'q' => ['sometimes', 'string', 'max:255'],
            'key' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'language' => ['sometimes', 'string', 'max:10', Rule::exists('languages', 'code')],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'approved', 'archived'])],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
