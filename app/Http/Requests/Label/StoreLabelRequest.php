<?php

namespace App\Http\Requests\Label;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<Unique|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('labels', 'name')],
        ];
    }
}
