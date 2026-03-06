<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AddItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isMozo();
    }

    public function rules(): array
    {
        return [
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
            'items.required' => 'Debes agregar al menos un producto.',
            'items.min'      => 'Debes agregar al menos un producto.',
        ];
    }
}
