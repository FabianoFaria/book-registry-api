<?php

namespace App\Http\Requests\Book;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        // Returning the policy's Response keeps its 404 status.
        return Gate::inspect('update', $this->route('book'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'isbn' => [
                'sometimes', 'nullable', 'string', 'regex:/^(\d{10}|\d{13})$/',
                Rule::unique('books')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('book')),
            ],
            'value' => ['sometimes', 'nullable', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ];
    }
}
