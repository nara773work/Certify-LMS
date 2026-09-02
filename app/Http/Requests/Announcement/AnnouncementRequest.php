<?php

declare(strict_types=1);

namespace App\Http\Requests\Announcement;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnnouncementRequest extends FormRequest
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
            'title' => ['required', 'max:200', 'string'],
            'body' => ['required', 'max:5000', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => '200字以下で入力してください',
            'title.string' => '文字形式で入力してください',
            'body.required' => '本文を入力してください',
            'body.max' => '5000字以内で入力してください',
            'body.string' => '文字形式で入力してください',
        ];
    }
}
