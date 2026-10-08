<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use App\Models\InstagramAccount;
use App\Models\MediaResource;
use App\Models\MessageTemplate;
use App\Support\StarterExamples;
use Illuminate\Http\Request;

class AutomationRuleController extends Controller
{
    public function index(StarterExamples $examples)
    {
        return view('rules.index', [
            'rules' => AutomationRule::with(['media', 'template', 'resource', 'account'])->latest()->paginate(15),
            'examples' => $examples->rules(),
        ]);
    }

    public function create()
    {
        return view('rules.form', $this->form(new AutomationRule));
    }

    public function store(Request $request)
    {
        AutomationRule::create($this->data($request));

        return redirect()->route('rules.index')->with('success', 'Rule saved.');
    }

    public function show(string $id)
    {
        return redirect()->route('rules.edit', $id);
    }

    public function edit(string $id)
    {
        return view('rules.form', $this->form(AutomationRule::findOrFail($id)));
    }

    public function update(Request $request, string $id)
    {
        AutomationRule::findOrFail($id)->update($this->data($request));

        return redirect()->route('rules.index')->with('success', 'Rule updated.');
    }

    public function destroy(string $id)
    {
        AutomationRule::findOrFail($id)->delete();

        return back()->with('success', 'Rule deleted.');
    }

    public function duplicateExample(string $key, StarterExamples $examples)
    {
        $example = $examples->rule($key);
        abort_unless($example, 404);

        $templateId = null;
        if ($example['template']) {
            $templateId = MessageTemplate::create([
                'name' => $example['template']['name'].' (draft)',
                'body' => $example['template']['body'],
                'is_active' => false,
            ])->id;
        }

        $rule = AutomationRule::create([
            'name' => $example['name'].' (draft)',
            'trigger_type' => 'comment_keyword',
            'keyword' => $example['keyword'],
            'public_reply' => $example['public_reply'],
            'message_template_id' => $templateId,
            'is_active' => false,
        ]);

        return redirect()->route('rules.edit', $rule)->with('success', 'Starter automation copied as a paused draft. Choose its account and media before activating it.');
    }

    private function form(AutomationRule $rule): array
    {
        return ['rule' => $rule, 'media' => MediaResource::where('is_active', true)->get(), 'templates' => MessageTemplate::where('is_active', true)->get(), 'accounts' => InstagramAccount::where('is_active', true)->get()];
    }

    private function data(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:120', 'instagram_account_id' => 'nullable|exists:instagram_accounts,id', 'media_resource_id' => 'nullable|exists:media_resources,id', 'message_template_id' => 'nullable|exists:message_templates,id', 'resource_id' => 'nullable|exists:media_resources,id', 'trigger_type' => 'required|in:comment_keyword,inbound_message', 'keyword' => 'nullable|string|max:120', 'public_reply' => 'nullable|string|max:1000']) + ['is_active' => $request->boolean('is_active')];
    }
}
