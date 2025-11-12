<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TokenLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'      => ['required', 'email'],
            'password'   => ['required', 'string'],
            'name'       => ['sometimes', 'string', 'max:100'], // tên token, mặc định 'api-token'
            'abilities'  => ['sometimes', 'array'],
            'abilities.*' => ['string'],
        ];
    }
}
