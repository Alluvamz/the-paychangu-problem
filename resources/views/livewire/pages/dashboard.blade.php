<div x-data="{ showSuccess: false }"
    @request-created.window="showSuccess = true; setTimeout(() => showSuccess = false, 3000)">
    <div class="p-4" x-show="showSuccess" x-transition>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Success!</strong>
            <span class="block sm:inline">Purchase request created.</span>
        </div>
    </div>

    <div class="my-4">

        <flux:input wire:model="apiKey" label="Paychangu private Api Key"
            description="dont worry we will not be storing this" />
    </div>

    <div class="grid grid-cols-2 p-4 mt-4 gap-6">
        <div class="border p-4 rounded-md">
            @if ($requests->isNotEmpty())
            <ul class="space-y-2 divide-y">
                @foreach ($requests as $request)
                <li class="pb-4">
                    <h1 class="font-bold">{{$request->title}}</h1>
                    <p class="text-sm text-gray-500">status : {{$request->status}}</p>
                    <p class="text-sm text-gray-500">charge id :{{$request->charge_id}}</p>
                    <p class="text-sm text-gray-500">price :{{$request->price}}</p>

                    <div class="flex mt-2 items-center gap-2">
                        <flux:button wire:click="verifyPurchase({{$request->id}})">verify</flux:button>
                        <flux:button wire:click="refreshPurchaseStatus({{$request->id}})">refresh status</flux:button>
                        <flux:button wire:click="deletePurchase({{$request->id}})">delete</flux:button>
                    </div>
                </li>
                @endforeach
            </ul>
            @else
            <div class="h-16 flex items-center justify-center">
                <h1 class="text-gray-500">No Request Found</h1>
            </div>
            @endif
        </div>
        <div class="border p-4 rounded-md">
            <form wire:submit="handleSubmit" class="space-y-4">

                <flux:input wire:model="chargeId" label="Transaction Charge id" />
                <flux:button wire:click="generateChargeId">generate</flux:button>

                <flux:input wire:model="title" label="Title Of The Purchase" />
                <flux:input wire:model="price" label="Price Of The Purchase" type="number" />
                <flux:input wire:model="phoneNumber" label="Phone Number Of The Purchase" type="tel" />

                <flux:button type="submit">Submit</flux:button>
            </form>
        </div>
    </div>
</div>
