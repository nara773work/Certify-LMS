<?php

namespace App\Http\Requests\Enrollment;

use Illuminate\Foundation\Http\FormRequest;

class EnrollmentNoteRequest extends FormRequest
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
            'body'=>['required','max:2000','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required'=>'メモの内容を入力してください',
            'body.max'=>'2000字以内で入力してください',
            'body.string'=>'文字形式で入力してください',
        ];
    }
}
