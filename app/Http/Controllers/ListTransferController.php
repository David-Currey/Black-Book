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

        $list->load(['customFields', 'people.tags', 'people.timelineNotes', 'people.reminders', 'people.customFieldValues.field']);

        $data = [
            'name' => $list->name,
            'description' => $list->description,
            'custom_fields' => $list->customFields
                ->sortBy('sort_order')
                ->map(fn ($field) => ['name' => $field->name])
                ->values(),
            'entries' => $list->people->map(function ($person) {
                return [
                    'name' => $person->name,
                    'category' => $person->game,
                    'status' => $person->status,
                    'rating' => $person->rating,
                    'notes' => $person->notes,
                    'timeline' => $person->timelineNotes
                        ->sortByDesc(fn ($note) => $note->occurred_on ?? $note->created_at)
                        ->map(function ($note) {
                            return [
                                'occurred_on' => $note->occurred_on?->toDateString(),
                                'note' => $note->note,
                            ];
                        })
                        ->values(),
                    'reminders' => $person->reminders
                        ->whereNull('completed_at')
                        ->sortBy('remind_on')
                        ->map(function ($reminder) {
                            return [
                                'remind_on' => $reminder->remind_on->toDateString(),
                                'note' => $reminder->note,
                            ];
                        })
                        ->values(),
                    'custom_fields' => $person->customFieldValues
                        ->mapWithKeys(fn ($fieldValue) => [$fieldValue->field->name => $fieldValue->value]),
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

        $customFieldsByName = collect();

        if (!empty($data['custom_fields']) && is_array($data['custom_fields'])) {
            foreach ($data['custom_fields'] as $index => $fieldData) {
                $fieldName = is_array($fieldData) ? ($fieldData['name'] ?? null) : $fieldData;

                if (empty($fieldName) || !is_string($fieldName)) {
                    continue;
                }

                $field = $list->customFields()->firstOrCreate(
                    ['name' => trim($fieldName)],
                    ['sort_order' => $index]
                );

                $customFieldsByName->put($field->name, $field);
            }
        }

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

            if (!empty($entry['custom_fields']) && is_array($entry['custom_fields'])) {
                foreach ($entry['custom_fields'] as $fieldName => $fieldValue) {
                    if (!is_string($fieldName) || trim((string) $fieldValue) === '') {
                        continue;
                    }

                    $field = $customFieldsByName->get($fieldName);

                    if (!$field) {
                        $field = $list->customFields()->firstOrCreate(
                            ['name' => trim($fieldName)],
                            ['sort_order' => $list->customFields()->count()]
                        );

                        $customFieldsByName->put($field->name, $field);
                    }

                    $person->customFieldValues()->create([
                        'list_custom_field_id' => $field->id,
                        'value' => (string) $fieldValue,
                    ]);
                }
            }

            if (!empty($entry['timeline']) && is_array($entry['timeline'])) {
                foreach ($entry['timeline'] as $timelineNote) {
                    if (empty($timelineNote['note']) || !is_string($timelineNote['note'])) {
                        continue;
                    }

                    $person->timelineNotes()->create([
                        'occurred_on' => $timelineNote['occurred_on'] ?? null,
                        'note' => $timelineNote['note'],
                    ]);
                }
            }

            if (!empty($entry['reminders']) && is_array($entry['reminders'])) {
                foreach ($entry['reminders'] as $reminder) {
                    if (
                        empty($reminder['remind_on'])
                        || empty($reminder['note'])
                        || ! is_string($reminder['note'])
                    ) {
                        continue;
                    }

                    $person->reminders()->create([
                        'remind_on' => $reminder['remind_on'],
                        'note' => $reminder['note'],
                    ]);
                }
            }
        }

        return redirect()
            ->route('lists.show', $list)
            ->with('success', 'List imported successfully.');
    }
}
