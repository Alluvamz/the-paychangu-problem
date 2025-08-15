<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new
    #[On('payment-request-created')]
    class extends Component
{

    #[Computed]
    public function requests()
    {
        $userId = Auth::id();
        return PurchaseRequest::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
?>

<div class="border p-4 rounded-md">

    @if ($this->requests->isNotEmpty())
        <ul class="space-y-2 divide-y">
            @foreach ($this->requests as $request)
                <livewire:payment-request-list-item :key="$request->id" :request="$request" />
            @endforeach
        </ul>
    @else
        <div class="h-16 flex items-center justify-center">
            <h1 class="text-gray-500">No Request Found</h1>
        </div>
    @endif

</div>