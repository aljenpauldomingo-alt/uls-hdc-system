<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M} .\'-]+$/u'],
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\p{M} .\'-]+$/u'],
            'middle_name' => ['nullable', 'string', 'max:100', 'regex:/^[\p{L}\p{M} .\'-]+$/u'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'last_name.regex' => 'Use letters, spaces, periods, apostrophes and hyphens only.',
            'first_name.regex' => 'Use letters, spaces, periods, apostrophes and hyphens only.',
            'middle_name.regex' => 'Use letters, spaces, periods, apostrophes and hyphens only.',
            'email.unique' => 'This email address is already in use.',
        ];
    }
}
