<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TokenIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'abilities'   => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ];
    }
}
