<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Support\StarterExamples;
use Illuminate\Http\Request;

class MessageTemplateController extends Controller
{
    public function index(StarterExamples $examples)
    {
        return view('templates.index', [
            'items' => MessageTemplate::latest()->paginate(15),
            'examples' => $examples->templates(),
        ]);
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

    public function duplicateExample(string $key, StarterExamples $examples)
    {
        $example = $examples->template($key);
        abort_unless($example, 404);

        $template = MessageTemplate::create([
            'name' => $example['name'].' (draft)',
            'body' => $example['body'],
            'is_active' => false,
        ]);

        return redirect()->route('templates.edit', $template)->with('success', 'Starter template copied as a paused draft.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['name' => 'required|max:120', 'body' => 'required|max:2000']) + ['is_active' => $r->boolean('is_active')];
    }
}
