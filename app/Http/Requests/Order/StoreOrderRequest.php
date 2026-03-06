<?php

namespace App\Http\Requests\Order;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isMozo();
    }

    public function rules(): array
    {
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $isMesa     = $this->input('type') === 'mesa';

        return [
            'customer_name'        => $isMesa ? 'nullable|string|max:100' : 'required|string|max:100',
            'comment'              => 'nullable|string|max:500',
            'type'                 => 'required|in:mesa,llevar',
            'tables'               => $isMesa ? 'required|array|min:1' : 'nullable|array',
            'tables.*'             => 'integer|min:1|max:'.max($tableCount, 1),
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.price'        => 'nullable|numeric|min:0',
            'items.*.comment'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'El nombre del cliente es obligatorio para pedidos para llevar.',
            'tables.required'        => 'Debes seleccionar al menos una mesa.',
            'items.required'         => 'El pedido debe tener al menos un producto.',
            'items.min'              => 'El pedido debe tener al menos un producto.',
        ];
    }
}
