<x-layouts.app.header :title="$title ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>


    <x-toaster-hub />
</x-layouts.app.header>