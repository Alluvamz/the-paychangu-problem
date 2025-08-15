<?php

use Livewire\Volt\Component;
use App\Models\PurchaseRequest;
use App\Actions\UpdatePurchaseStatus;
use Livewire\Attributes\Computed;

new class extends Component
{
    public PurchaseRequest $request;

    public function mount():void
    {
        if($this->statusIsPending){
            defer(fn()=>(new UpdatePurchaseStatus($this->purchaseRequest))->execute());
        }
    }

    #[Computed]
    public function statusIsPending()
    {
        return $this->request->status == 'pending';
    }

    public function deletePurchase()
    {
        $this->purchaseRequest->delete();
    }

    public function refreshPurchaseStatus()
    {
        (new UpdatePurchaseStatus($this->purchaseRequest))->execute();
    }
}; ?>

<div
@if ($this->statusIsPending)
    wire:poll.2s
@endif
>
    <li class="pb-4">
        <h1 class="font-bold">{{$request->title}}</h1>
        <p class="text-sm text-gray-500">status : {{$request->status}}</p>
        <p class="text-sm text-gray-500">charge id :{{$request->charge_id}}</p>
        <p class="text-sm text-gray-500">price :{{$request->price}}</p>

        <div class="flex mt-2 items-center gap-2">
            <flux:button wire:click="refreshPurchaseStatus">refresh status</flux:button>
            <flux:button wire:click="deletePurchase">delete</flux:button>
        </div>
    </li>
</div>
