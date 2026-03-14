<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.tags.index', compact('tags'));
    }

    public function create()
    {
        return view('admin.tags.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
        ]);

        $maxOrder = Tag::max('sort_order') ?? 0;
        $data['sort_order'] = $maxOrder + 1;

        Tag::create($data);

        return redirect()->route('admin.tags.index')->with('success', 'Tag creado.');
    }

    public function edit(Tag $tag)
    {
        return view('admin.tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name,' . $tag->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $data['is_active'] = $request->has('is_active');
        $tag->update($data);

        return redirect()->route('admin.tags.index')->with('success', 'Tag actualizado.');
    }

    public function destroy(Tag $tag)
    {
        $tag->delete();
        return redirect()->route('admin.tags.index')->with('success', 'Tag eliminado.');
    }
}
