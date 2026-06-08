<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Str;

new class extends Component
{
    public string $name = '';
    public string $description = '';

    public bool $showImportListModal = false;
    public bool $showCreateListModal = false;

    /**
     * Create a new list
     */
    public function createList(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'List name is required.',
        ]);

        UserList::create([
            'user_id' => Auth::id(),
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->reset('name', 'description');

        $this->closeCreateListModal();
    }

    /**
     * Get all lists for the logged-in user
     */
    public function getListsProperty()
    {
        return Auth::user()->lists()->with('people')->get();
    }

    /**
     * Get lists shared with the logged-in user.
     */
    public function getSharedListsProperty()
    {
        return Auth::user()
            ->listShares()
            ->with(['list.people', 'list.user'])
            ->get()
            ->pluck('list')
            ->filter();
    }

    /**
     * Open create list modal
     */
    public function openCreateListModal(): void
    {
        $this->reset('name', 'description');
        $this->showCreateListModal = true;
    }

    /**
     * Close create list modal
     */
    public function closeCreateListModal(): void
    {
        $this->showCreateListModal = false;
    }

    /**
     * Open import list modal
     */
    public function openImportListModal(): void
    {
        $this->showImportListModal = true;
    }

    /**
     * Close import list modal
     */
    public function closeImportListModal(): void
    {
        $this->showImportListModal = false;
    }
};

?>

<div class="app-shell">
    <div class="flex items-center justify-between mb-6">
        <div class="page-header !mb-0">
            <h1 class="page-title">Your Lists</h1>
            <p class="page-subtitle">Create and manage your lists.</p>
        </div>

        <div class="flex gap-3">
            <button
                wire:click="openImportListModal"
                class="btn-secondary"
            >
                Import File
            </button>

            <button
                wire:click="openCreateListModal"
                class="btn-primary"
            >
                Create List
            </button>
        </div>
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="panel-title !mb-0">Your Existing Lists</h2>
        </div>

        @if ($this->lists->isEmpty())
            <div class="panel">
                <div class="panel-inner">
                    <p class="text-muted">You have not created any lists yet.</p>
                </div>
            </div>
        @else
            <ul class="grid gap-4">
                @foreach($this->lists as $list)
                    <li>
                        <a
                            href="{{ route('lists.show', $list) }}"
                            class="card-link"
                        >
                            <div class="flex items-center justify-between gap-4 mt-3">
                                <div class="min-w-0">
                                    <h3 class="card-title">{{ $list->name }}</h3>

                                    @if ($list->description)
                                        <p class="card-text">{{ $list->description }}</p>
                                    @else
                                        <p class="card-text">No description provided.</p>
                                    @endif
                                </div>

                                <span class="card-meta">
                                    {{ $list->people->count() }} {{ Str::plural('entry', $list->people->count()) }}
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if ($this->sharedLists->isNotEmpty())
        <div class="space-y-4 mt-8">
            <div class="flex items-center justify-between">
                <h2 class="panel-title !mb-0">Shared With You</h2>
            </div>

            <ul class="grid gap-4">
                @foreach($this->sharedLists as $list)
                    <li>
                        <a
                            href="{{ route('lists.show', $list) }}"
                            class="card-link"
                        >
                            <div class="flex items-center justify-between gap-4 mt-3">
                                <div class="min-w-0">
                                    <h3 class="card-title">{{ $list->name }}</h3>
                                    <p class="card-text">
                                        Shared by {{ $list->user->name }}
                                    </p>
                                </div>

                                <span class="card-meta">
                                    {{ $list->people->count() }} {{ Str::plural('entry', $list->people->count()) }}
                                </span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Modals -->
    @if ($showCreateListModal)
        <div
            class="modal-backdrop"
            wire:click="closeCreateListModal"
        ></div>

        <div class="modal-wrap">
            <div class="modal-panel">
                <div class="modal-header">
                    <h2 class="modal-title">Create New List</h2>

                    <button
                        wire:click="closeCreateListModal"
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
                                wire:keydown.enter="createList"
                                placeholder="Ex. Blacklist"
                                class="app-input"
                                autofocus
                            >

                            @error('name')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="list-description" class="app-label">Description</label>
                            <input
                                id="list-description"
                                type="text"
                                wire:model.live="description"
                                placeholder="Optional description"
                                class="app-input"
                            >

                            @error('description')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <button
                            wire:click="createList"
                            wire:loading.attr="disabled"
                            class="btn-primary w-full"
                        >
                            Create List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($showImportListModal)
        <div
            class="modal-backdrop"
            wire:click="closeImportListModal"
        ></div>

        <div class="modal-wrap">
            <div class="modal-panel">
                <div class="modal-header">
                    <h2 class="modal-title">Import File</h2>

                    <button
                        wire:click="closeImportListModal"
                        class="icon-button"
                    >
                        Close
                    </button>
                </div>

                <div class="modal-body">
                    <form
                        action="{{ route('lists.import') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="space-y-4"
                    >
                        @csrf

                        <div>
                            <label for="list_file" class="app-label">JSON or CSV File</label>
                            <input
                                id="list_file"
                                name="list_file"
                                type="file"
                                accept=".json,.csv,application/json,text/csv"
                                class="app-input"
                            >

                            @error('list_file')
                                <p class="validation-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-sm text-[var(--app-text-muted)]">
                            Upload JSON for a complete backup, or CSV for entry and custom field data.
                        </p>

                        <details class="mt-3 text-sm text-[var(--app-text-muted)]">
                            <summary class="cursor-pointer hover:text-[var(--app-text)]">
                                View sample format
                            </summary>

                            <pre class="mt-2 p-3 rounded bg-[var(--app-surface-2)] overflow-x-auto text-xs">
                        {
                        "name": "My List",
                        "description": "Optional description",
                        "entries": [
                            {
                            "name": "Example Entry",
                            "category": "Optional category",
                            "notes": "## Notes\n- Item one\n- Item two",
                            "tags": [
                                { "name": "Important", "color": "#0e639c" },
                                { "name": "Example", "color": "#c74e39" }
                            ]
                            }
                        ]
                        }
                            </pre>
                        </details>

                        <button
                            type="submit"
                            class="btn-primary w-full"
                        >
                            Import List
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
