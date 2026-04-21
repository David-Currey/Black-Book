<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Illuminate\Support\Str;

new class extends Component
{
    public string $name = '';
    public string $description = '';

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
};

?>

<div class="app-shell">
    <div class="flex items-center justify-between mb-6">
        <div class="page-header !mb-0">
            <h1 class="page-title">Your Lists</h1>
            <p class="page-subtitle">Create and manage your lists.</p>
        </div>

        <button
            wire:click="openCreateListModal"
            class="btn-primary"
        >
            Create List
        </button>
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
                            <div class="flex items-start justify-between gap-4">
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
</div>