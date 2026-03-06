<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class ChangeTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isMozo();
    }

    public function rules(): array
    {
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);

        return [
            'tables'   => 'required|array|min:1',
            'tables.*' => 'integer|min:1|max:'.max($tableCount, 1),
        ];
    }

    public function messages(): array
    {
        return [
            'tables.required' => 'Debes seleccionar al menos una mesa.',
        ];
    }
}
