<?php

use App\Models\Person;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public Person $person;

    public string $name = '';
    public string $game = '';
    public string $notes = '';
    public string $tagName = '';
    public string $tagColor = '#0e639c';

    public array $selectedTags = [];

    public bool $showEditModal = false;
    public bool $confirmingDelete = false;

    /**
     * Load the person and make sure the logged-in user owns the parent list
     */
    public function mount(Person $person): void
    {
        abort_unless($person->list->user_id === Auth::id(), 403);

        $this->person = $person;
        $this->name = $person->name;
        $this->game = $person->game ?? '';
        $this->notes = $person->notes ?? '';

        $this->person->load('tags');

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
        $this->validate([
            'name' => 'required|string|max:255',
            'game' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
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

        $this->person->refresh();
        $this->person->load('tags');

        $this->closeEditModal();
    }

    /**
     * Delete this person and return to the parent list
     */
    public function deletePerson()
    {
        $list = $this->person->list;

        $this->person->delete();

        return $this->redirect(route('lists.show', $list), navigate: true);
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
     * Open the edit entry modal
     */
    public function openEditModal(): void
    {
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
            ← Back to {{ $this->person->list->name }}
        </a>

        <div class="flex items-start justify-between gap-4 mt-3">
            <div class="page-header !mb-0">
                <h1 class="page-title">{{ $this->person->name }}</h1>
                <p class="page-subtitle">
                    View this entry’s details.
                </p>
            </div>

            <button
                wire:click="openEditModal"
                class="btn-primary"
            >
                Edit Entry
            </button>
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
                                placeholder="Add notes... (use - for lists, **bold**, ## headings)"
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