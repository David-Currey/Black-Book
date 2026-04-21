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
     * Update this person's details and return to the list
     */
    public function updatePerson()
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

        return $this->redirect(
            route('lists.show', $this->person->list),
            navigate: true
        );
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
};

?>

<div class="app-shell">
    <div class="mb-6">
        <a
            href="{{ route('lists.show', $this->person->list) }}"
            class="text-sm text-[var(--app-text-muted)] hover:text-[var(--app-text)] transition"
        >
            ← Back to {{ $this->person->list->name }}
        </a>

        <div class="page-header mt-3 !mb-0">
            <h1 class="page-title">{{ $this->person->name }}</h1>
            <p class="page-subtitle">
                View and update this entry's details.
            </p>
        </div>
    </div>

    <div class="panel max-w-3xl">
        <div class="panel-inner">
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

                <!-- Tag selector for attaching tags to this person -->
                <livewire:tags.selector wire:model="selectedTags" />

                <div>
                    <label for="person-notes" class="app-label">Notes</label>
                    <textarea
                        id="person-notes"
                        wire:model.live="notes"
                        class="app-textarea"
                        rows="6"
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
                            Delete Person
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