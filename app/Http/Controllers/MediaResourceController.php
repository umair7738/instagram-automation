<?php

namespace App\Http\Controllers;

use App\Models\InstagramAccount;
use App\Models\MediaResource;
use App\Services\Meta\MetaGraphClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MediaResourceController extends Controller
{
    public function index()
    {
        return view('resources.index', ['items' => MediaResource::latest()->paginate(15)]);
    }

    public function create()
    {
        return view('resources.form', [
            'item' => new MediaResource,
            'accounts' => InstagramAccount::where('is_active', true)->get(),
        ]);
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
        return view('resources.form', [
            'item' => MediaResource::findOrFail($id),
            'accounts' => InstagramAccount::where('is_active', true)->get(),
        ]);
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

    public function instagramMedia(Request $request, MetaGraphClient $client)
    {
        $account = InstagramAccount::query()
            ->whereKey($request->integer('account_id'))
            ->where('is_active', true)
            ->first();

        abort_unless($account, 404, 'The selected Instagram account is not available.');

        if (! filled($account->access_token)) {
            return response()->json(['message' => 'Reconnect this Instagram account before importing media.'], 422);
        }

        try {
            $items = collect($client->listMedia($account))
                ->map(fn (array $item) => [
                    'id' => (string) ($item['id'] ?? ''),
                    'permalink' => $item['permalink'] ?? null,
                    'caption' => $item['caption'] ?? null,
                    'media_type' => $item['media_type'] ?? null,
                    'media_url' => $item['media_url'] ?? null,
                    'thumbnail_url' => $item['thumbnail_url'] ?? null,
                    'timestamp' => $item['timestamp'] ?? null,
                ])
                ->filter(fn (array $item) => $item['id'] !== '')
                ->values();

            return response()->json(['data' => $items]);
        } catch (\Throwable $exception) {
            Log::warning('Instagram media import failed', [
                'instagram_account_id' => $account->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Instagram could not return recent media. Reconnect the account or check its API permissions, then try again.',
            ], 422);
        }
    }

    private function data(Request $r): array
    {
        $rules = [
            'name' => 'required|max:120',
            'type' => 'required|in:media,link',
            'permalink' => 'nullable|url',
        ];

        if ($r->input('type') === 'media') {
            $rules['instagram_media_id'] = ['required', 'max:100', 'regex:/^\d+$/'];
            $rules['destination_url'] = 'nullable|url';
        } else {
            $rules['instagram_media_id'] = 'nullable|max:100';
            $rules['destination_url'] = 'required|url';
        }

        return $r->validate($rules) + ['is_active' => $r->boolean('is_active')];
    }
}
