<?php

namespace App\Http\Requests\Localization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ExportLocalizationRequest extends FormRequest
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
            'language' => ['required', 'string', 'max:10', Rule::exists('languages', 'code')],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'approved', 'archived'])],
            'include_metadata' => ['sometimes', 'boolean'],
            'labels' => ['sometimes', 'array'],
            'labels.*' => ['string', 'max:50'],
        ];
    }
}
