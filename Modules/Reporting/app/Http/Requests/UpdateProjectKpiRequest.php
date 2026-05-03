<?php

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectKpiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metric_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'target_value' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'actual_value' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'unit' => [
                'nullable',
                'string',
                'max:50',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'metric_name.max' => 'Názov metriky nesmie presiahnuť 255 znakov.',
            'target_value.numeric' => 'Cieľová hodnota musí byť číslo.',
            'target_value.min' => 'Cieľová hodnota nesmie byť záporná.',
            'actual_value.numeric' => 'Skutočná hodnota musí byť číslo.',
            'actual_value.min' => 'Skutočná hodnota nesmie byť záporná.',
            'unit.max' => 'Jednotka nesmie presiahnuť 50 znakov.',
            'description.max' => 'Opis nesmie presiahnuť 1000 znakov.',
        ];
    }
}
