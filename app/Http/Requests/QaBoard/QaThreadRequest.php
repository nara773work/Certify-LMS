<?php

namespace App\Http\Requests\QaBoard;

use Illuminate\Foundation\Http\FormRequest;

class QaThreadRequest extends FormRequest
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
            'certification_id' =>['required_if:_method,POST','exists:certifications,id',],
            'title' =>['required','max:200','string'],
            'body' =>['required','max:5000','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'certification_id.required' =>'資格を選択してください',
            'certification_id.exists' =>'表示されている資格の中から選択してください',
            'title.required' =>'タイトルを入力してください',
            'title.max' =>'200字以内で入力してください',
            'title.string' =>'文字形式で入力してください',
            'body.required' =>'質問内容を入力してください',
            'body.max' =>'5000字以内で入力してください',
            'body.string' =>'文字形式で入力してください',
        ];
    }
}
