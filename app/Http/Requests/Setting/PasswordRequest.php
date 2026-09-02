<?php

declare(strict_types=1);

namespace App\Http\Requests\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PasswordRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '現在のパスワードを入力してください',
            'current_password.current_password' => '現在のパスワードを入力してください',
            'password.required' => 'パスワードを入力してください',
            'password.min' => '8字以上で入力してください',
            'password.confirmed' => '確認用パスワードが一致しません',
            'password.required' => '確認用の新しいパスワードを入力してください',
        ];
    }
}
