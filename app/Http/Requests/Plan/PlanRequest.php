<?php

declare(strict_types=1);

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class PlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'max:100'],
            'description' => ['nullable', 'max:2000'],
            'duration_days' => ['required', 'integer', 'between:1,3650'],
            'default_meeting_quota' => ['required', 'integer', 'between:0,1000'],
            'sort_order' => ['nullable', 'integer', 'between:0,1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'プラン名を入力してください',
            'name.max' => '100字以内で入力してください',
            'description.max' => '2000字以内で入力してください',
            'duration_days.required' => '受講期間を入力してください',
            'duration_days.between' => '1~3650の範囲で入力してください',
            'default_meeting_quota.required' => '初期付与面談回数を入力してください',
            'default_meeting_quota.between' => '0~1000の範囲で入力してください',
            'sort_order.between' => '0~1000の範囲で入力してください',
        ];
    }
}
