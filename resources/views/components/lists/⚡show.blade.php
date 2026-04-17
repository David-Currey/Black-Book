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

    public bool $confirmingDelete = false;

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
        ]);

        $this->list->people()->create([
            'name' => $this->personName,
            'game' => $this->game,
            'notes' => $this->notes,
        ]);

        $this->reset('personName', 'game', 'notes');

        $this->list->refresh();
    }

    /**
     * Update this list's details
     */
    public function updateList(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $this->list->update([
            'name' => $this->name,
            'description' => $this->description,
        ]);

        $this->list->refresh();
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
};

?>

<div class="p-4">
    <div class="mb-4">
        <a
            href="{{ route('lists.index') }}"
            class="text-sm text-blue-400 hover:underline"
        >
            Back to lists
        </a>
    </div>
    <div class="border rounded p-4 mb-6">
        <h1 class="text-xl font-bold mb-4">Edit List</h1>

        <div class="space-y-4">
            <div>
                <input
                    type="text"
                    wire:model.live="name"
                    class="border p-2 w-full rounded"
                >

                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <textarea
                    wire:model.live="description"
                    class="border p-2 w-full rounded"
                    rows="3"
                ></textarea>

                @error('description')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button
                    wire:click="updateList"
                    wire:loading.attr="disabled"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    Save List
                </button>

                @if (!$confirmingDelete)
                    <button
                        wire:click="confirmDelete"
                        class="bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Delete List
                    </button>
                @else
                    <div class="flex gap-2">
                        <button
                            wire:click="deleteList"
                            class="bg-red-700 text-white px-4 py-2 rounded"
                        >
                            Confirm Delete
                        </button>

                        <button
                            wire:click="$set('confirmingDelete', false)"
                            class="bg-gray-600 text-white px-4 py-2 rounded"
                        >
                            Cancel
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="border rounded p-4 mb-6">
        <h2 class="text-lg font-semibold mb-4">Add a person</h2>

        <div class="space-y-4">
            <div>
                <input
                    type="text"
                    wire:model.live="personName"
                    placeholder="Name"
                    class="border p-2 w-full rounded"
                >

                @error('personName')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <input
                    type="text"
                    wire:model.live="game"
                    placeholder="Game"
                    class="border p-2 w-full rounded"
                >

                @error('game')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <textarea
                    wire:model.live="notes"
                    placeholder="Notes"
                    class="border p-2 w-full rounded"
                    rows="4"
                ></textarea>

                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                wire:click="createPerson"
                wire:loading.attr="disabled"
                class="bg-blue-600 text-white px-4 py-2 rounded"
            >
                Add Person
            </button>
        </div>
    </div>

    <div class="border rounded p-4">
        <h2 class="text-lg font-semibold mb-4">People in this list</h2>

        @if ($this->list->people->isEmpty())
            <p class="text-gray-400">No people added yet.</p>
        @else
            <ul class="space-y-3">
                @foreach ($this->list->people as $person)
                    <li>
                        <a
                            href="{{ route('people.show', $person) }}"
                            class="block border rounded p-3 hover:bg-gray-800"
                        >
                            <p class="font-semibold">{{ $person->name }}</p>

                            @if ($person->game)
                                <p class="text-sm text-gray-500 mt-1">
                                    Game: {{ $person->game }}
                                </p>
                            @endif

                            @if ($person->notes)
                                <p class="text-sm mt-2">{{ $person->notes }}</p>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>