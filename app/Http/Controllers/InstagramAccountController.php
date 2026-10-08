<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use Illuminate\Http\Request;

class InstagramAccountController extends Controller
{
    public function index()
    {
        return view('accounts.index', ['items' => InstagramAccount::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('accounts.form', ['item' => new InstagramAccount]);
    }

    public function store(Request $r)
    {
        InstagramAccount::create($this->data($r));

        return redirect()->route('accounts.index')->with('success', 'Account saved.');
    }

    public function show(string $id)
    {
        return redirect()->route('accounts.edit', $id);
    }

    public function edit(string $id)
    {
        return view('accounts.form', ['item' => InstagramAccount::findOrFail($id)]);
    }

    public function update(Request $r, string $id)
    {
        InstagramAccount::findOrFail($id)->update($this->data($r));

        return redirect()->route('accounts.index')->with('success', 'Account updated.');
    }

    public function destroy(string $id)
    {
        InstagramAccount::findOrFail($id)->delete();

        return back()->with('success', 'Account deleted.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['name' => 'required|max:120', 'instagram_user_id' => 'required|max:100', 'username' => 'nullable|max:100', 'facebook_page_id' => 'nullable|max:100']) + ['is_active' => $r->boolean('is_active')];
    }
}
