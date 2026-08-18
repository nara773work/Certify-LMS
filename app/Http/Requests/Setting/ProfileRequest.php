<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
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
            'name'=>['required','max:50','string'],
            'bio'=>['nullable','max:1000','string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '氏名を入力してください',
            'name.max'=> '50字以内で入力してください',
            'name.string'=>'文字形式で入力してください',
            'bio.max'=>'1000字以内で入力してください',
            'bio.string'=>'文字形式で入力してください',
        ];
    }
}
