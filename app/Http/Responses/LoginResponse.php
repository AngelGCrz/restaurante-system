<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        if ($user->role->name === 'admin') {
            return redirect()->intended(route('admin.products.index'));
        }

        if ($user->role->name === 'cajero') {
            return redirect()->intended(route('orders.index'));
        }

        if ($user->role->name === 'cocina') {
            return redirect()->intended(route('kitchen.index'));
        }

        if ($user->role->name === 'mozo') {
            return redirect()->intended(route('mozo.orders.create'));
        }

        return redirect()->intended(config('fortify.home'));
    }
}
