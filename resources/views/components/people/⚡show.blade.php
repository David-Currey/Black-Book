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
};

?>

<div class="p-4">
    <div class="mb-6">
        <a
            href="{{ route('lists.show', $this->person->list) }}"
            class="text-sm text-blue-400 hover:underline"
        >
            Back to list
        </a>
    </div>

    <div class="border rounded p-4">
        <h1 class="text-2xl font-bold mb-4">Edit Person</h1>

        <div class="space-y-4">
            <div>
                <label class="block text-sm mb-1">Name</label>
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
                <label class="block text-sm mb-1">Game</label>
                <input
                    type="text"
                    wire:model.live="game"
                    class="border p-2 w-full rounded"
                >

                @error('game')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm mb-1">Notes</label>
                <textarea
                    wire:model.live="notes"
                    class="border p-2 w-full rounded"
                    rows="5"
                ></textarea>

                @error('notes')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex gap-3">
                <button
                    wire:click="updatePerson"
                    wire:loading.attr="disabled"
                    class="bg-blue-600 text-white px-4 py-2 rounded"
                >
                    Save Changes
                </button>

                @if (!$confirmingDelete)
                    <button
                        wire:click="confirmDelete"
                        class="bg-red-600 text-white px-4 py-2 rounded"
                    >
                        Delete Person
                    </button>
                @else
                    <div class="flex gap-2">
                        <button
                            wire:click="deletePerson"
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
</div>