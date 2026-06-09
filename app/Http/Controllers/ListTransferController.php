<?php

namespace App\Http\Controllers;

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
        abort_unless($list->canBeViewedBy(Auth::user()), 403);

        $list->load(['customFields', 'people.tags', 'people.reminders', 'people.customFieldValues.field']);

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
                    'notes' => $person->notes,
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
     * Export a list as a CSV file
     */
    public function exportCsv(UserList $list)
    {
        abort_unless($list->canBeViewedBy(Auth::user()), 403);

        $list->load(['customFields', 'people.tags', 'people.customFieldValues.field']);

        $customFields = $list->customFields
            ->sortBy('sort_order')
            ->values();

        $headers = collect(['name', 'category', 'notes', 'tags'])
            ->merge($customFields->map(fn ($field) => 'custom:' . $field->name))
            ->all();

        $fileName = Str::slug($list->name ?: 'list') . '.csv';

        return response()->streamDownload(function () use ($headers, $list, $customFields) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($list->people as $person) {
                $customValues = $person->customFieldValues->pluck('value', 'list_custom_field_id');

                $row = [
                    $person->name,
                    $person->game,
                    $person->notes,
                    $person->tags->pluck('name')->implode('; '),
                ];

                foreach ($customFields as $field) {
                    $row[] = $customValues->get($field->id);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Import a list from a JSON file
     */
    public function import(Request $request)
    {
        $request->validate([
            'list_file' => ['required', 'file', 'mimes:json,csv,txt', 'max:2048'],
        ], [
            'list_file.required' => 'Please choose a JSON file to import.',
            'list_file.mimes' => 'The uploaded file must be a JSON or CSV file.',
        ]);

        $extension = strtolower($request->file('list_file')->getClientOriginalExtension());

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->importCsv($request);
        }

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

            $person = $list->people()->create([
                'name' => $entry['name'],
                'game' => $entry['category'] ?? null,
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

    private function importCsv(Request $request)
    {
        $file = $request->file('list_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (! $handle) {
            return back()->withErrors([
                'list_file' => 'The uploaded CSV file could not be read.',
            ]);
        }

        $headers = fgetcsv($handle);

        if (! is_array($headers) || ! in_array('name', $headers, true)) {
            fclose($handle);

            return back()->withErrors([
                'list_file' => 'The CSV file must include a name column.',
            ]);
        }

        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $list = Auth::user()->lists()->create([
            'name' => Str::headline($baseName ?: 'Imported List'),
            'description' => 'Imported from CSV.',
        ]);

        $customFieldColumns = collect($headers)
            ->filter(fn ($header) => str_starts_with($header, 'custom:'))
            ->mapWithKeys(function ($header, $index) use ($list) {
                $name = trim(Str::after($header, 'custom:'));

                if ($name === '') {
                    return [];
                }

                $field = $list->customFields()->create([
                    'name' => $name,
                    'sort_order' => $list->customFields()->count(),
                ]);

                return [$index => $field];
            });

        while (($row = fgetcsv($handle)) !== false) {
            $normalizedRow = array_slice(array_pad($row, count($headers), null), 0, count($headers));
            $entry = array_combine($headers, $normalizedRow);

            if (! is_array($entry) || empty($entry['name'])) {
                continue;
            }

            $person = $list->people()->create([
                'name' => $entry['name'],
                'game' => $entry['category'] ?? null,
                'notes' => $entry['notes'] ?? null,
            ]);

            foreach ($customFieldColumns as $index => $field) {
                $value = trim((string) ($row[$index] ?? ''));

                if ($value === '') {
                    continue;
                }

                $person->customFieldValues()->create([
                    'list_custom_field_id' => $field->id,
                    'value' => $value,
                ]);
            }

            $tags = collect(explode(';', (string) ($entry['tags'] ?? '')))
                ->map(fn ($tag) => trim($tag))
                ->filter();

            if ($tags->isNotEmpty()) {
                $tagIds = $tags->map(function ($tagName) {
                    return Auth::user()->tags()->firstOrCreate(
                        ['name' => $tagName],
                        ['color' => '#d6a84f']
                    )->id;
                });

                $person->tags()->sync($tagIds);
            }
        }

        fclose($handle);

        return redirect()
            ->route('lists.show', $list)
            ->with('success', 'CSV imported successfully.');
    }
}
