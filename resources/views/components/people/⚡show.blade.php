<?php

use App\Models\EntryReminder;
use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    public string $name = '';
    public string $game = '';
    public string $notes = '';
    public string $reminderDate = '';
    public string $reminderNote = '';
    public string $tagName = '';
    public string $tagColor = '#0e639c';

    public array $selectedTags = [];
    public array $customFieldValues = [];

    public bool $showEditModal = false;
    public bool $confirmingDelete = false;

    /**
     * Load the person and make sure the logged-in user owns the parent list
     */
    public function mount(Person $person): void
    {
        abort_unless($person->list->canBeViewedBy(Auth::user()), 403);

        $this->person = $person;
        $this->name = $person->name;
        $this->game = $person->game ?? '';
        $this->notes = $person->notes ?? '';

        $this->person->load(['tags', 'customFieldValues', 'reminders']);
        $this->customFieldValues = $this->person->customFieldValues
            ->pluck('value', 'list_custom_field_id')
            ->toArray();

        $this->selectedTags = $this->person->tags->map(function ($tag) {
            return [
                'tag_id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ];
        })->toArray();
    }

    /**
     * Update this entry's details
     */
    public function updatePerson(): void
    {
        abort_unless($this->canEdit, 403);

        $this->validate([
            'name' => 'required|string|max:255',
            'game' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'customFieldValues.*' => 'nullable|string|max:1000',
        ]);

        $this->person->update([
            'name' => $this->name,
            'game' => $this->game,
            'notes' => $this->notes,
        ]);

        $tagIds = [];

        foreach ($this->selectedTags as $selectedTag) {
            if (!empty($selectedTag['tag_id'])) {
                $tagIds[] = $selectedTag['tag_id'];
                continue;
            }

            $tag = Auth::user()->tags()->firstOrCreate(
                ['name' => $selectedTag['name']],
                ['color' => $selectedTag['color']]
            );

            $tagIds[] = $tag->id;
        }

        $this->person->tags()->sync($tagIds);
        $this->syncCustomFieldValues();

        $this->person->refresh();
        $this->person->load(['tags', 'customFieldValues']);

        $this->closeEditModal();
    }

    /**
     * Delete this person and return to the parent list
     */
    public function deletePerson()
    {
        abort_unless($this->canEdit, 403);

        $list = $this->person->list;

        $this->person->delete();

        return $this->redirect(route('lists.show', $list), navigate: true);
    }

    /**
     * Add a reminder for this entry.
     */
    public function addReminder(): void
    {
        abort_unless($this->canEdit, 403);

        $this->validate([
            'reminderDate' => 'required|date',
            'reminderNote' => 'required|string|max:255',
        ], [
            'reminderDate.required' => 'Reminder date is required.',
            'reminderNote.required' => 'Reminder note is required.',
        ]);

        $this->person->reminders()->create([
            'remind_on' => $this->reminderDate,
            'note' => $this->reminderNote,
        ]);

        $this->reset('reminderDate', 'reminderNote');
        $this->person->load('reminders');
    }

    /**
     * Mark a reminder complete.
     */
    public function completeReminder(int $reminderId): void
    {
        abort_unless($this->canEdit, 403);

        $this->ownedReminder($reminderId)->update(['completed_at' => now()]);

        $this->person->load('reminders');
    }

    /**
     * Delete a reminder.
     */
    public function deleteReminder(int $reminderId): void
    {
        abort_unless($this->canEdit, 403);

        $this->ownedReminder($reminderId)->delete();

        $this->person->load('reminders');
    }

    /**
     * Enable delete confirmation mode
     */
    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    /**
     * Create or attach a tag to this person
     */
    public function addTag(): void
    {
        abort_unless($this->canEdit, 403);

        $this->validate([
            'tagName' => 'required|string|max:50',
            'tagColor' => 'required|string|size:7',
        ], [
            'tagName.required' => 'Tag name is required.',
        ]);

        if ($this->person->tags()->count() >= 5) {
            $this->addError('tagName', 'A person can have a maximum of 5 tags.');
            return;
        }

        $cleanName = trim($this->tagName);

        $tag = Auth::user()->tags()->firstOrCreate(
            ['name' => $cleanName],
            ['color' => $this->tagColor]
        );

        if ($this->person->tags()->where('tags.id', $tag->id)->exists()) {
            $this->addError('tagName', 'This tag is already attached to this person.');
            return;
        }

        $this->person->tags()->syncWithoutDetaching([$tag->id]);

        $this->reset('tagName');
        $this->tagColor = '#0e639c';

        $this->person->load('tags');
    }

    /**
     * Remove a tag from this person
     */
    public function removeTag(int $tagId): void
    {
        abort_unless($this->canEdit, 403);

        $tag = $this->person->tags()->find($tagId);

        $this->person->tags()->detach($tagId);

        if ($tag && $tag->people()->count() === 0) {
            $tag->delete();
        }

        $this->person->load('tags');
    }

    /**
     * Attach an existing tag to this person
     */
    public function attachExistingTag(int $tagId): void
    {
        abort_unless($this->canEdit, 403);

        if ($this->person->tags()->count() >= 5) {
            $this->addError('tagName', 'A person can have a maximum of 5 tags.');
            return;
        }

        if ($this->person->tags()->where('tags.id', $tagId)->exists()) {
            $this->addError('tagName', 'This tag is already attached to this person.');
            return;
        }

        $this->person->tags()->syncWithoutDetaching([$tagId]);

        $this->reset('tagName');
        $this->tagColor = '#0e639c';

        $this->person->load('tags');
    }

    /**
     * Get existing user tags that match the current tag input
     */
    public function getTagSuggestionsProperty()
    {
        if (trim($this->tagName) === '') {
            return collect();
        }

        return Auth::user()
            ->tags()
            ->where('name', 'like', '%' . trim($this->tagName) . '%')
            ->whereDoesntHave('people', function ($query) {
                $query->where('people.id', $this->person->id);
            })
            ->orderBy('name')
            ->limit(6)
            ->get();
    }

    /**
     * Check if this person has reached the max tag limit
     */
    public function getHasMaxTagsProperty(): bool
    {
        return $this->person->tags->count() >= 5;
    }

    /**
     * Get open reminders sorted by due date.
     */
    public function getOpenRemindersProperty()
    {
        return $this->person
            ->reminders()
            ->whereNull('completed_at')
            ->orderBy('remind_on')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get the parent list's custom fields.
     */
    public function getCustomFieldsProperty()
    {
        return $this->person
            ->list
            ->customFields()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getCanEditProperty(): bool
    {
        return $this->person->list->canBeEditedBy(Auth::user());
    }

    private function syncCustomFieldValues(): void
    {
        foreach ($this->customFields as $field) {
            $value = trim((string) ($this->customFieldValues[$field->id] ?? ''));

            if ($value === '') {
                $this->person->customFieldValues()
                    ->where('list_custom_field_id', $field->id)
                    ->delete();
                continue;
            }

            $this->person->customFieldValues()->updateOrCreate(
                ['list_custom_field_id' => $field->id],
                ['value' => $value]
            );
        }
    }

    private function ownedReminder(int $reminderId): EntryReminder
    {
        return EntryReminder::query()
            ->whereKey($reminderId)
            ->where('person_id', $this->person->id)
            ->firstOrFail();
    }

    /**
     * Open the edit entry modal
     */
    public function openEditModal(): void
    {
        abort_unless($this->canEdit, 403);

        $this->showEditModal = true;
    }

    /**
     * Close the edit entry modal
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->confirmingDelete = false;
    }
};

?>

@php
    $converter = new \League\CommonMark\CommonMarkConverter();
@endphp

<div class="app-shell">
<div class="max-w-4xl">
    <div class="mb-4">
        <a
            href="{{ route('lists.show', $this->person->list) }}"
            class="text-sm text-[var(--app-text-muted)] hover:text-[var(--app-text)] transition"
        >
            &larr; Back to {{ $this->person->list->name }}
        </a>

        <div class="flex items-start justify-between gap-4 mt-3">
            <div class="page-header !mb-0">
                <h1 class="page-title">{{ $this->person->name }}</h1>
                <p class="page-subtitle">
                    View this entry's details.
                </p>
            </div>

            @if ($this->canEdit)
                <button
                    wire:click="openEditModal"
                    class="btn-primary"
                >
                    Edit Entry
                </button>
            @endif
        </div>
    </div>

    <div class="panel">
        <div class="panel-inner space-y-6">
            <div>
                <p class="app-label">Name</p>
                <p>{{ $this->person->name }}</p>
            </div>

            @if ($this->person->game)
                <div>
                    <p class="app-label">Category</p>
                    <p>{{ $this->person->game }}</p>
                </div>
            @endif

            <div>
                <p class="app-label">Tags</p>

                @if ($this->person->tags->isEmpty())
                    <p class="text-muted">No tags added yet.</p>
                @else
                    <div class="flex flex-wrap gap-2 mt-2">
                        @foreach ($this->person->tags as $tag)
                            <span
                                class="flex items-center gap-2 rounded-full px-3 py-1 text-sm"
                                style="
                                    background-color: {{ $tag->color }}20;
                                    border: 1px solid {{ $tag->color }};
                                    color: {{ $tag->color }};
                                "
                            >
                                {{ $tag->name }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($this->customFields->isNotEmpty())
                <div>
                    <p class="app-label">Custom Fields</p>
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($this->customFields as $field)
                            @php
                                $fieldValue = $this->person->customFieldValues->firstWhere('list_custom_field_id', $field->id)?->value;
                            @endphp

                            <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-2)] p-4">
                                <p class="app-label">{{ $field->name }}</p>
                                @if ($fieldValue)
                                    <p>{{ $fieldValue }}</p>
                                @else
                                    <p class="text-muted">No value.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <p class="app-label">Notes</p>

                @if ($this->person->notes)
                    <div class="mt-2">
                        <div class="prose prose-invert max-w-none text-sm">
                            {!! $converter->convert($this->person->notes) !!}
                        </div>
                    </div>
                @else
                    <p class="text-muted">No notes added yet.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="panel mt-6">
        <div class="panel-inner space-y-6">
            <div>
                <h2 class="panel-title !mb-1">Reminders</h2>
                <p class="text-sm text-[var(--app-text-muted)]">Schedule follow-ups or review dates for this entry.</p>
            </div>

            @if ($this->canEdit)
                <div class="grid gap-4 md:grid-cols-[180px_1fr_auto]">
                    <div>
                        <label for="reminder-date" class="app-label">Date</label>
                        <input
                            id="reminder-date"
                            type="date"
                            wire:model.live="reminderDate"
                            class="app-input"
                        >

                        @error('reminderDate')
                            <p class="validation-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reminder-note" class="app-label">Reminder</label>
                        <input
                            id="reminder-note"
                            type="text"
                            wire:model.live="reminderNote"
                            class="app-input"
                            placeholder="Ex. Follow up next week"
                        >

                        @error('reminderNote')
                            <p class="validation-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-end">
                        <button
                            wire:click="addReminder"
                            wire:loading.attr="disabled"
                            class="btn-primary w-full md:w-auto"
                        >
                            Add Reminder
                        </button>
                    </div>
                </div>
            @endif

            @if ($this->openReminders->isEmpty())
                <p class="text-muted">No open reminders.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($this->openReminders as $reminder)
                        <li class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-2)] p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="app-label">
                                        {{ $reminder->remind_on->format('M j, Y') }}
                                        @if ($reminder->is_overdue)
                                            <span class="text-[var(--app-danger)]">Overdue</span>
                                        @endif
                                    </p>
                                    <p>{{ $reminder->note }}</p>
                                </div>

                                @if ($this->canEdit)
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            wire:click="completeReminder({{ $reminder->id }})"
                                            class="btn-secondary"
                                        >
                                            Complete
                                        </button>
                                        <button
                                            wire:click="deleteReminder({{ $reminder->id }})"
                                            class="icon-button"
                                        >
                                            Remove
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @if ($showEditModal)
        <div
            class="modal-backdrop"
            wire:click="closeEditModal"
        ></div>

        <div class="modal-wrap">
            <div class="modal-panel">
                <div class="modal-header">
                    <h2 class="modal-title">Edit Entry</h2>

                    <button
                        wire:click="closeEditModal"
                        class="icon-button"
                    >
                        Close
                    </button>
                </div>

                <div class="modal-body">
                    <div class="space-y-5">
                        <div>
                            <label for="person-name" class="app-label">Name</label>
                            <input
                                id="person-name"
                                type="text"
                                wire:model.live="name"
                                class="app-input"
                            >

                            @error('name')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="person-game" class="app-label">Category</label>
                            <input
                                id="person-game"
                                type="text"
                                wire:model.live="game"
                                class="app-input"
                            >

                            @error('game')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <livewire:tags.selector wire:model="selectedTags" />

                        @if ($this->customFields->isNotEmpty())
                            <div class="space-y-4">
                                <div>
                                    <h3 class="panel-title !mb-1 text-base">Custom Fields</h3>
                                    <p class="text-sm text-[var(--app-text-muted)]">Fields inherited from {{ $this->person->list->name }}.</p>
                                </div>

                                @foreach ($this->customFields as $field)
                                    <div>
                                        <label for="custom-field-{{ $field->id }}" class="app-label">{{ $field->name }}</label>
                                        <input
                                            id="custom-field-{{ $field->id }}"
                                            type="text"
                                            wire:model.live="customFieldValues.{{ $field->id }}"
                                            class="app-input"
                                        >

                                        @error('customFieldValues.' . $field->id)
                                            <p class="validation-error">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div>
                            <label for="person-notes" class="app-label">Notes</label>

                            <p class="text-xs text-[var(--app-text-muted)] mb-2">
                                Supports markdown: - bullets, **bold**, ## headings
                            </p>

                            <textarea
                                id="person-notes"
                                wire:model.live="notes"
                                class="app-textarea font-mono"
                                rows="6"
                                placeholder="Add notes..."
                            ></textarea>

                            @error('notes')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-3 pt-2">
                            <button
                                wire:click="updatePerson"
                                wire:loading.attr="disabled"
                                class="btn-primary"
                            >
                                Save Changes
                            </button>

                            @if (!$confirmingDelete)
                                <button
                                    wire:click="confirmDelete"
                                    class="btn-danger"
                                >
                                    Delete Entry
                                </button>
                            @else
                                <button
                                    wire:click="deletePerson"
                                    wire:loading.attr="disabled"
                                    class="btn-danger"
                                >
                                    Confirm Delete
                                </button>

                                <button
                                    wire:click="$set('confirmingDelete', false)"
                                    class="btn-secondary"
                                >
                                    Cancel
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
