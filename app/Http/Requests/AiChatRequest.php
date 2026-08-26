<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:2000'],
            'section_id' => ['nullable', 'string', 'exists:sections,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは200文字以内で入力してください。',
            'content.string' => '質問は文字列で入力してください。',
            'content.max' => '質問は2000文字以内で入力してください。',
            'section_id.exists' => '存在するセクションを選択してください。',
        ];
    }
}
