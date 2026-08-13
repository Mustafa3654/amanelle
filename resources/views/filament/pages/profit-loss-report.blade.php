<x-filament-panels::page>
    <form wire:submit="getReport" class="grid gap-4 md:grid-cols-3">
        {{ $this->form }}
    </form>

    @php($report = $this->getReport())
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([['Revenue', $report['revenue']], ['Cost of goods sold', $report['cost']], ['Gross profit', $report['profit']], ['Margin', number_format($report['margin'], 2).'%']] as [$label, $value])
            <div class="rounded-xl bg-gray-900 p-5 ring-1 ring-white/10">
                <div class="text-sm text-gray-400">{{ $label }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ is_numeric($value) && $label !== 'Margin' ? '$'.number_format($value, 2) : $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto rounded-xl bg-gray-900 ring-1 ring-white/10">
        <table class="w-full text-sm">
            <thead><tr class="border-b border-white/10 text-left"><th class="p-4">Product</th><th class="p-4">Variant</th><th class="p-4">Qty</th><th class="p-4">Revenue</th><th class="p-4">Cost</th><th class="p-4">Profit</th></tr></thead>
            <tbody>
                @forelse ($report['items'] as $item)
                    <tr class="border-b border-white/5"><td class="p-4">{{ $item['product_name'] }}</td><td class="p-4">{{ $item['variant_label'] }}</td><td class="p-4">{{ $item['quantity'] }}</td><td class="p-4">${{ number_format($item['revenue'], 2) }}</td><td class="p-4">${{ number_format($item['cost'], 2) }}</td><td class="p-4">${{ number_format($item['profit'], 2) }}</td></tr>
                @empty
                    <tr><td colspan="6" class="p-8 text-center text-gray-400">No delivered sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
