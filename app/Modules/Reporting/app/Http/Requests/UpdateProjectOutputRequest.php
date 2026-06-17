<?php

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'output_name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'output_type' => [
                'nullable',
                'string',
                'max:100',
            ],
            'status' => [
                'sometimes',
                Rule::in(['pending', 'completed', 'delivered']),
            ],
            'planned_delivery' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
            ],
            'actual_delivery' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'before_or_equal:now',
            ],
            'document_ids' => [
                'nullable',
                'array',
            ],
            'document_ids.*' => [
                'integer',
                'exists:document,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'output_name.max' => 'Názov výstupu nesmie presiahnuť 255 znakov.',
            'description.max' => 'Opis nesmie presiahnuť 2000 znakov.',
            'output_type.max' => 'Typ výstupu nesmie presiahnuť 100 znakov.',
            'status.in' => 'Status musí byť jeden z: pending, completed, delivered.',
            'planned_delivery.date_format' => 'Plánovaný dátum doručenia musí byť v tvare Y-m-d H:i:s.',
            'actual_delivery.date_format' => 'Skutočný dátum doručenia musí byť v tvare Y-m-d H:i:s.',
            'actual_delivery.before_or_equal' => 'Skutočný dátum doručenia nesmie byť v budúcnosti.',
            'document_ids.array' => 'Document IDs musia byť pole.',
            'document_ids.*.exists' => 'Niektorý z dokumentov neexistuje.',
        ];
    }
}
