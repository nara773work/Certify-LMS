<?php

declare(strict_types=1);

namespace App\Http\Requests\Setting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AvatarRequest extends FormRequest
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
            'avatar' => ['image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages()
    {

        return [

            'avatar.image' => '画像形式で登録してください',
            'avatar.mimes' => 'jepg,png,webpで登録してください',
            'avatar.max' => '2MB以内で登録してください',

        ];

    }
}
