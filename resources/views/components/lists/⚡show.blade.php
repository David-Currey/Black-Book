<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public UserList $list;

    public string $name = '';
    public string $game = '';
    public string $notes = '';

    /**
     * Load the list for this page and make sure it belongs to the logged-in user
     */
    public function mount(UserList $list): void
    {
        abort_unless($list->user_id === Auth::id(), 403);

        $this->list = $list;
    }

    /**
     * Create a new person inside this list
     */
    public function createPerson(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'game' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->list->people()->create([
            'name' => $this->name,
            'game' => $this->game,
            'notes' => $this->notes,
        ]);

        $this->reset('name', 'game', 'notes');

        $this->list->refresh();
    }
};

?>

<div class="p-4">
    <h1 class="text-2xl font-bold mb-2">{{ $this->list->name }}</h1>

    @if ($this->list->description)
        <p class="mb-6 text-gray-300">{{ $this->list->description }}</p>
    @endif

        <div class="border rounded p-4 mb-6">
        <h2 class="text-lg font-semibold mb-4">Add a person</h2>

        <div class="space-y-4">
            <div>
                <input
                    type="text"
                    wire:model.live="name"
                    placeholder="Name"
                    class="border p-2 w-full rounded"
                >

                @error('name')
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
                    <li class="border rounded p-3">
                        <p class="font-semibold">{{ $person->name }}</p>

                        @if ($person->game)
                            <p class="text-sm text-gray-500 mt-1">
                                Game: {{ $person->game }}
                            </p>
                        @endif

                        @if ($person->notes)
                            <p class="text-sm mt-2">{{ $person->notes }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>