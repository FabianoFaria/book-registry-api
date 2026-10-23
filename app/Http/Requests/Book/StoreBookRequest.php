<?php

namespace App\Http\Requests\Book;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // any authenticated user may create; auth:sanctum already ran
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'isbn' => [
                'nullable', 'string', 'regex:/^(\d{10}|\d{13})$/',
                Rule::unique('books')->where('user_id', $this->user()->id),
            ],
            'value' => ['nullable', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }

    public function messages(): array
    {
        return ['isbn.regex' => 'The ISBN must contain only digits and be 10 or 13 characters long.'];
    }
}
