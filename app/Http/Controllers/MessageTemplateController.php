<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index()
    {
        return view('templates.index', ['items' => MessageTemplate::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('templates.form', ['item' => new MessageTemplate]);
    }

    public function store(Request $r)
    {
        MessageTemplate::create($this->data($r));

        return redirect()->route('templates.index')->with('success', 'Template saved.');
    }

    public function show(string $id)
    {
        return redirect()->route('templates.edit', $id);
    }

    public function edit(string $id)
    {
        return view('templates.form', ['item' => MessageTemplate::findOrFail($id)]);
    }

    public function update(Request $r, string $id)
    {
        MessageTemplate::findOrFail($id)->update($this->data($r));

        return redirect()->route('templates.index')->with('success', 'Template updated.');
    }

    public function destroy(string $id)
    {
        MessageTemplate::findOrFail($id)->delete();

        return back()->with('success', 'Template deleted.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['name' => 'required|max:120', 'body' => 'required|max:2000']) + ['is_active' => $r->boolean('is_active')];
    }
}
