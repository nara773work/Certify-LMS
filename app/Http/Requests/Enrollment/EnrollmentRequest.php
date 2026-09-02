<?php

declare(strict_types=1);

namespace App\Http\Requests\Enrollment;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EnrollmentRequest extends FormRequest
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
            'title' => ['required', 'max:100'],
            'description' => ['nullable', 'max:1000'],
            'target_date' => ['nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => '100字以内で入力してください',
            'description.max' => '1000字以内で入力してください',
            'target_date.date' => '有効な日付を入力してください',
            'target_date.after' => '本日以降の日付を入力してください',
        ];
    }
}
