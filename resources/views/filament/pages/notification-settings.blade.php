<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex flex-wrap gap-3">
            <x-filament::button type="submit">
                Save
            </x-filament::button>

            {{-- Sends a real message rather than only checking the format: a
                 token that looks right but points at a deleted bot fails
                 silently at the worst moment, the first real order. --}}
            <x-filament::button type="button" color="gray" icon="heroicon-o-paper-airplane"
                                wire:click="sendTest" wire:loading.attr="disabled">
                Send a test message
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">Where else orders show up</x-slot>

        <ul class="space-y-2 text-sm">
            <li>
                <strong>The bell in this panel</strong> — always on, nothing to configure.
            </li>
            <li>
                <strong>Email</strong> — sent to every staff account. Needs mail credentials in
                <code>.env</code>; until those are set, messages are written to
                <code>storage/logs</code> rather than pretending to have sent.
            </li>
        </ul>
    </x-filament::section>
</x-filament-panels::page>
