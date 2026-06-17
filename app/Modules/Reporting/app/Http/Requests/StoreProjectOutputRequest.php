<?php

namespace Modules\Reporting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectOutputRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_id' => [
                'required',
                'integer',
                'exists:application,id',
            ],
            'output_name' => [
                'required',
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
                'nullable',
                Rule::in(['pending', 'completed', 'delivered']),
            ],
            'planned_delivery' => [
                'nullable',
                'date_format:Y-m-d H:i:s',
                'after_or_equal:now',
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
            'application_id.required' => 'Aplikácia je povinná.',
            'application_id.exists' => 'Vybraná aplikácia neexistuje.',
            'output_name.required' => 'Názov výstupu je povinný.',
            'output_name.max' => 'Názov výstupu nesmie presiahnuť 255 znakov.',
            'description.max' => 'Opis nesmie presiahnuť 2000 znakov.',
            'output_type.max' => 'Typ výstupu nesmie presiahnuť 100 znakov.',
            'status.in' => 'Status musí byť jeden z: pending, completed, delivered.',
            'planned_delivery.date_format' => 'Plánovaný dátum doručenia musí byť v tvare Y-m-d H:i:s.',
            'planned_delivery.after_or_equal' => 'Plánovaný dátum doručenia musí byť v budúcnosti.',
            'document_ids.array' => 'Document IDs musia byť pole.',
            'document_ids.*.exists' => 'Niektorý z dokumentov neexistuje.',
        ];
    }
}
