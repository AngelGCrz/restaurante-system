<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class PayTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCajero();
    }

    public function rules(): array
    {
        return [
            'table_key'      => 'required|string',
            'payment_method' => 'required|in:efectivo,yape,tarjeta',
            'receipt_type'   => 'required|in:ticket,boleta,factura',
        ];
    }
}
