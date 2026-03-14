<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{

public function index(Request $request)
{
    $products = Product::with('categories')
        ->search($request->search)
        ->categoryFilter($request->category)
        ->orderBy('name')
        ->get();

    $categories = Category::orderBy('name')->get();
    $stockEnabled = (bool) \App\Models\Setting::getValue('stock_enabled', false);

    return view('admin.products.index', compact('products', 'categories', 'stockEnabled'));
}

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function edit(Product $product)
    {
        $product->load('categories');
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                              => 'required|string|max:255',
            'description'                       => 'nullable|string',
            'stock'                             => 'nullable|integer',
            'category_prices'                   => 'required|array|min:1',
            'category_prices.*.category_id'     => 'required|exists:categories,id',
            'category_prices.*.price'           => 'required|numeric|min:0',
        ]);

        // El precio base del producto = precio de la primera categoría
        $firstPrice = (float) $validated['category_prices'][0]['price'];

        $product = Product::create([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'price'        => $firstPrice,
            'stock'        => (int) ($validated['stock'] ?? 0),
            'is_available' => true,
        ]);

        // Sincronizar categorías con su precio propio
        $syncData = [];
        foreach ($validated['category_prices'] as $cp) {
            $syncData[(int) $cp['category_id']] = ['price' => (float) $cp['price']];
        }
        $product->categories()->sync($syncData);

        return redirect()->route('admin.products.index')->with('success', 'Producto creado.');
    }

    public function update(Request $request, Product $product)
    {
        if ($request->filled('quick_toggle')) {
            $product->update([
                'is_available' => $request->boolean('is_available'),
            ]);

            return redirect()->route('admin.products.index')->with('success', 'Disponibilidad actualizada.');
        }

        $validated = $request->validate([
            'name'                              => 'required|string|max:255',
            'description'                       => 'nullable|string',
            'is_available'                      => 'sometimes|boolean',
            'stock'                             => 'nullable|integer',
            'category_prices'                   => 'required|array|min:1',
            'category_prices.*.category_id'     => 'required|exists:categories,id',
            'category_prices.*.price'           => 'required|numeric|min:0',
        ]);

        $firstPrice = (float) $validated['category_prices'][0]['price'];

        $product->update([
            'name'         => $validated['name'],
            'description'  => $validated['description'] ?? null,
            'price'        => $firstPrice,
            'is_available' => $request->boolean('is_available'),
            'stock'        => (int) ($validated['stock'] ?? $product->stock ?? 0),
        ]);

        $syncData = [];
        foreach ($validated['category_prices'] as $cp) {
            $syncData[(int) $cp['category_id']] = ['price' => (float) $cp['price']];
        }
        $product->categories()->sync($syncData);

        return redirect()->route('admin.products.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Producto eliminado.');
    }
}

