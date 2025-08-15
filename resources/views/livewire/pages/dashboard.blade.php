<div>
    <div class="grid grid-cols-2 p-4 mt-4 gap-6">

        <livewire:payment-request-list />

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