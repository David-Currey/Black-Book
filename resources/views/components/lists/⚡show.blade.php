<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public UserList $list;

    public string $name = '';
    public string $description = '';

    public string $personName = '';
    public string $game = '';
    public string $notes = '';
    public string $search = '';

    public array $selectedTags = [];

    public bool $confirmingDelete = false;

    public bool $showEditListModal = false;
    public bool $showAddPersonModal = false;

    /**
     * Load the list and initialize form fields
     */
    public function mount(UserList $list): void
    {
        abort_unless($list->user_id === Auth::id(), 403);

        $this->list = $list;
        $this->name = $list->name;
        $this->description = $list->description ?? '';
    }

    /**
     * Create a new person inside this list
     */
    public function createPerson(): void
    {
        $this->validate([
            'personName' => 'required|string|max:255',
            'game' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'personName.required' => 'Person name is required.',
        ]);

        $person = $this->list->people()->create([
            'name' => $this->personName,
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

        $person->tags()->sync($tagIds);

        $this->reset('personName', 'game', 'notes');
        $this->selectedTags = [];

        $this->list->refresh();

        $this->closeModals();
    }

    /**
     * Update this list's details
     */
    public function updateList(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'List name is required.',
        ]);

        $this->list->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->list->refresh();

        $this->closeModals();
    }

    /**
     * Delete this list and return to lists page
     */
    public function deleteList()
    {
        $this->list->delete();

        return $this->redirect(route('lists.index'), navigate: true);
    }

    /**
     * Enable delete confirmation for list
     */
    public function confirmDelete(): void
    {
        $this->confirmingDelete = true;
    }

    /**
     * Open the edit list modal
     */
    public function openEditListModal(): void
    {
        $this->showAddPersonModal = false;
        $this->showEditListModal = true;
    }

    /**
     * Open the add person modal
     */
    public function openAddPersonModal(): void
    {
        $this->showEditListModal = false;
        $this->showAddPersonModal = true;

        $this->reset('personName', 'game', 'notes');
        $this->selectedTags = [];
    }

    /**
     * Close all modals
     */
    public function closeModals(): void
    {
        $this->showEditListModal = false;
        $this->showAddPersonModal = false;
        $this->confirmingDelete = false;
    }

    /**
     * Get people in this list filtered by the current search term
     */
    public function getFilteredPeopleProperty()
    {
        return $this->list
            ->people()
            ->with('tags')
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();
    }
};

?>

<div class="app-shell">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <a
                href="{{ route('lists.index') }}"
                class="text-sm text-[var(--app-text-muted)] hover:text-[var(--app-text)] transition"
            >
                ← Back to lists
            </a>

            <div class="page-header mt-3 !mb-0">
                <h1 class="page-title">{{ $this->list->name }}</h1>

                @if ($this->list->description)
                    <p class="page-subtitle">{{ $this->list->description }}</p>
                @else
                    <p class="page-subtitle">No description provided.</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button
                wire:click="openAddPersonModal"
                class="btn-primary"
            >
                Add Entry
            </button>

            <button
                wire:click="openEditListModal"
                class="btn-secondary"
            >
                Edit List
            </button>
        </div>
    </div>

    <div class="panel">
        <div class="panel-inner">
            <div class="flex flex-col gap-4 mb-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="panel-title !mb-0">Entries in This List</h2>

                    <span class="card-meta">
                        {{ $this->filteredPeople->count() }} shown
                    </span>
                </div>

                <div class="w-full md:w-80">
                    <label for="people-search" class="sr-only">Search Entries</label>
                    <input
                        id="people-search"
                        type="text"
                        wire:model.live="search"
                        placeholder="Search by name..."
                        class="app-input"
                    >
                </div>
            </div>

            @if ($this->list->people->isEmpty())
                <div class="text-muted">
                    No entries have been added to this list yet.
                </div>
            @elseif ($this->filteredPeople->isEmpty())
                <div class="text-muted">
                    No entries matched your search.
                </div>
            @else
                <ul class="space-y-3">
                    @foreach ($this->filteredPeople as $person)
                        <li>
                            <a
                                href="{{ route('people.show', $person) }}"
                                class="card-link"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <h3 class="card-title">{{ $person->name }}</h3>

                                        @if ($person->game)
                                            <p class="card-text">{{ $person->game }}</p>
                                        @endif

                                        @if ($person->notes)
                                            <p class="card-text line-clamp-2">{{ $person->notes }}</p>
                                        @endif

                                        @if ($person->tags->isNotEmpty())
                                            <div class="flex flex-wrap gap-2 mt-3">
                                                @foreach ($person->tags as $tag)
                                                    <span
                                                        class="flex items-center rounded-full px-3 py-1 text-xs"
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

                                    <span class="text-sm text-[var(--app-text-muted)] shrink-0">
                                        Open →
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>


    <!-- Edit list modal -->
    @if ($showEditListModal || $showAddPersonModal)
        <div
            class="modal-backdrop"
            wire:click="closeModals"
        ></div>

        <div class="modal-wrap">
            <div class="modal-panel">
                @if ($showEditListModal)
                    <div class="modal-header">
                        <h2 class="modal-title">Edit List</h2>

                        <button
                            wire:click="closeModals"
                            class="icon-button"
                        >
                            Close
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="space-y-4">
                            <div>
                                <label for="list-name" class="app-label">List Name</label>
                                <input
                                    id="list-name"
                                    type="text"
                                    wire:model.live="name"
                                    class="app-input"
                                >

                                @error('name')
                                    <p class="validation-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="list-description" class="app-label">Description</label>
                                <textarea
                                    id="list-description"
                                    wire:model.live="description"
                                    class="app-textarea"
                                    rows="4"
                                ></textarea>

                                @error('description')
                                    <p class="validation-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex flex-wrap gap-3 pt-2">
                                <button
                                    wire:click="updateList"
                                    wire:loading.attr="disabled"
                                    class="btn-primary"
                                >
                                    Save List
                                </button>

                                @if (!$confirmingDelete)
                                    <button
                                        wire:click="confirmDelete"
                                        class="btn-danger"
                                    >
                                        Delete List
                                    </button>
                                @else
                                    <button
                                        wire:click="deleteList"
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
                @endif


                <!-- Add person modal -->
                @if ($showAddPersonModal)
                    <div class="modal-header">
                        <h2 class="modal-title">Add Entry</h2>

                        <button
                            wire:click="closeModals"
                            class="icon-button"
                        >
                            Close
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="space-y-4">
                            <div>
                                <label for="person-name" class="app-label">Name</label>
                                <input
                                    id="person-name"
                                    type="text"
                                    wire:model.live="personName"
                                    placeholder="Ex. Compactted"
                                    class="app-input"
                                >

                                @error('personName')
                                    <p class="validation-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="person-game" class="app-label">Game</label>
                                <input
                                    id="person-game"
                                    type="text"
                                    wire:model.live="game"
                                    placeholder="Ex. World of Warcraft"
                                    class="app-input"
                                >

                                @error('game')
                                    <p class="validation-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tag selector for filtering people by tags -->
                            <livewire:tags.selector wire:model="selectedTags" />

                            <div>
                                <label for="person-notes" class="app-label">Notes</label>
                                <textarea
                                    id="person-notes"
                                    wire:model.live="notes"
                                    placeholder="Add anything helpful to remember about this person..."
                                    class="app-textarea"
                                    rows="5"
                                ></textarea>

                                @error('notes')
                                    <p class="validation-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                wire:click="createPerson"
                                wire:loading.attr="disabled"
                                class="btn-primary w-full"
                            >
                                Add Entry
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>