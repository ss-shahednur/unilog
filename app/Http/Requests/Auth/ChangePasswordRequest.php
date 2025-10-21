<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

      /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'confirmed', Password::min(8)],
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
            'new_password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Additional validation logic.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Ensure email matches authenticated user
            if ($this->user() && $this->email !== $this->user()->email) {
                $validator->errors()->add('email', 'Email does not match your account.');
            }
        });
    }
}
