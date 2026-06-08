<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\UserList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ListTransferController extends Controller
{
    /**
     * Export a list as a JSON file
     */
    public function export(UserList $list)
    {
        abort_unless($list->user_id === Auth::id(), 403);

        $list->load('people.tags');

        $data = [
            'name' => $list->name,
            'description' => $list->description,
            'entries' => $list->people->map(function ($person) {
                return [
                    'name' => $person->name,
                    'category' => $person->game,
                    'status' => $person->status,
                    'rating' => $person->rating,
                    'notes' => $person->notes,
                    'tags' => $person->tags->map(function ($tag) {
                        return [
                            'name' => $tag->name,
                            'color' => $tag->color,
                        ];
                    })->values(),
                ];
            })->values(),
        ];

        $fileName = Str::slug($list->name ?: 'list') . '.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $fileName, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import a list from a JSON file
     */
    public function import(Request $request)
    {
        $request->validate([
            'list_file' => ['required', 'file', 'mimes:json', 'max:2048'],
        ], [
            'list_file.required' => 'Please choose a JSON file to import.',
            'list_file.mimes' => 'The uploaded file must be a JSON file.',
        ]);

        $contents = file_get_contents($request->file('list_file')->getRealPath());
        $data = json_decode($contents, true);

        if (!is_array($data)) {
            return back()->withErrors([
                'list_file' => 'The uploaded file is not valid JSON.',
            ]);
        }

        if (empty($data['name']) || !is_string($data['name'])) {
            return back()->withErrors([
                'list_file' => 'The JSON file must contain a valid list name.',
            ]);
        }

        if (!isset($data['entries']) || !is_array($data['entries'])) {
            return back()->withErrors([
                'list_file' => 'The JSON file must contain an entries array.',
            ]);
        }

        $list = Auth::user()->lists()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);

        foreach ($data['entries'] as $entry) {
            if (empty($entry['name']) || !is_string($entry['name'])) {
                continue;
            }

            $status = $entry['status'] ?? 'neutral';
            $status = array_key_exists($status, Person::STATUSES) ? $status : 'neutral';

            $rating = $entry['rating'] ?? null;
            $rating = is_numeric($rating) && $rating >= 1 && $rating <= 5 ? (int) $rating : null;

            $person = $list->people()->create([
                'name' => $entry['name'],
                'game' => $entry['category'] ?? null,
                'status' => $status,
                'rating' => $rating,
                'notes' => $entry['notes'] ?? null,
            ]);

            $tagIds = [];

            if (!empty($entry['tags']) && is_array($entry['tags'])) {
                foreach ($entry['tags'] as $tagData) {
                    if (empty($tagData['name']) || !is_string($tagData['name'])) {
                        continue;
                    }

                    $tag = Auth::user()->tags()->firstOrCreate(
                        ['name' => trim($tagData['name'])],
                        ['color' => $tagData['color'] ?? '#0e639c']
                    );

                    $tagIds[] = $tag->id;
                }
            }

            if (!empty($tagIds)) {
                $person->tags()->sync($tagIds);
            }
        }

        return redirect()
            ->route('lists.show', $list)
            ->with('success', 'List imported successfully.');
    }
}
