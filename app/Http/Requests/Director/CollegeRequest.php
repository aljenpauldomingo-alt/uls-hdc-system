<?php

namespace App\Http\Requests\Director;

use App\Models\College;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollegeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isDirector();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => trim((string) $this->input('code'))]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9-]+$/', Rule::unique(College::class)->ignore($this->route('college'))],
            'name' => ['required', 'string', 'max:255', Rule::unique(College::class)->ignore($this->route('college'))],
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
            'code.unique' => 'Another college already uses this code.',
            'name.unique' => 'Another college already uses this name.',
            'code.regex' => 'Use letters, numbers and dashes only (e.g. CIT).',
        ];
    }
}
