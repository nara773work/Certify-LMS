<?php

declare(strict_types=1);

namespace App\Http\Requests\Meeting;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MeetingPackRequest extends FormRequest
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
            'name' => ['required', 'max:100'],
            'description' => ['nullable', 'max:2000'],
            'meeting_count' => ['required', 'integer', 'between:1,100'],
            'price' => ['required', 'integer', 'between:0,1000000'],
            'stripe_price_id' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'SKU名を入力してください',
            'name.max' => '100字以内で入力してください',
            'description.max' => '2000字以内で入力してください',
            'meeting_count.required' => '面談回数を入力してください',
            'meeting_count.between' => '1~100の範囲で入力してください',
            'price.required' => '価格を入力してください',
            'price.between' => '0~1000000の範囲で入力してください',
            'stripe_price_id.max' => '255字以内で入力してください',
            'sort_order.between' => '0~1000の範囲で入力してください',
        ];
    }
}
