<?php

declare(strict_types=1);

namespace App\Http\Requests\QaBoard;

use Illuminate\Foundation\Http\FormRequest;

class QaReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'max:5000', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '回答を入力してください',
            'body.max' => '5000字以内で入力してください',
            'body.string' => '文字形式で入力してください',
        ];
    }
}
