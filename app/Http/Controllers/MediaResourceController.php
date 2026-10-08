<?php

namespace App\Http\Controllers;

use App\Models\MediaResource;
use Illuminate\Http\Request;

class MediaResourceController extends Controller
{
    public function index()
    {
        return view('resources.index', ['items' => MediaResource::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('resources.form', ['item' => new MediaResource]);
    }

    public function store(Request $r)
    {
        MediaResource::create($this->data($r));

        return redirect()->route('resources.index')->with('success', 'Resource saved.');
    }

    public function show(string $id)
    {
        return redirect()->route('resources.edit', $id);
    }

    public function edit(string $id)
    {
        return view('resources.form', ['item' => MediaResource::findOrFail($id)]);
    }

    public function update(Request $r, string $id)
    {
        MediaResource::findOrFail($id)->update($this->data($r));

        return redirect()->route('resources.index')->with('success', 'Resource updated.');
    }

    public function destroy(string $id)
    {
        MediaResource::findOrFail($id)->delete();

        return back()->with('success', 'Resource deleted.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['name' => 'required|max:120', 'type' => 'required|in:media,link', 'instagram_media_id' => 'nullable|max:100', 'permalink' => 'nullable|url', 'destination_url' => 'nullable|url']) + ['is_active' => $r->boolean('is_active')];
    }
}
