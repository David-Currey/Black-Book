<?php

use App\Models\UserList;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public UserList $list;

    /**
     * Load the list for this page and make sure it belongs to the logged-in user
     */
    public function mount(UserList $list): void
    {
        abort_unless($list->user_id === Auth::id(), 403);

        $this->list = $list;
    }
};

?>

<div class="p-4">
    <h1 class="text-2xl font-bold mb-2">{{ $this->list->name }}</h1>

    @if ($this->list->description)
        <p class="mb-6 text-gray-300">{{ $this->list->description }}</p>
    @endif

    <div class="border rounded p-4">
        <h2 class="text-lg font-semibold mb-2">People in this list</h2>
        <p class="text-gray-400">No people added yet.</p>
    </div>
</div>