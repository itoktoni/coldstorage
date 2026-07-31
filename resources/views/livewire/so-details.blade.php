<?php

use Livewire\Component;

new class extends Component {
    public array $rows = [];
    public array $options = [];
    public array $prices = [];

    public function mount(array $rows = [], array $options = [], array $prices = []): void
    {
        $this->options = $options;
        $this->prices = $prices;
        $this->rows = $rows ?: [$this->blank()];
    }

    public function addRow(): void
    {
        $this->rows[] = $this->blank();
    }

    public function removeRow(int $i): void
    {
        unset($this->rows[$i]);
        $this->rows = array_values($this->rows) ?: [$this->blank()];
    }

    /** Reject duplicate product: clear the field and flag the row. */
    public function updated(string $property): void
    {
        if (! preg_match('/^rows\.(\d+)\.so_detail_id_product$/', $property, $m)) {
            return;
        }

        $i = (int) $m[1];
        $product = $this->rows[$i]['so_detail_id_product'] ?? '';
        if ($product === '' || $product === null) {
            return;
        }

        foreach ($this->rows as $j => $row) {
            if ($j === $i || (string) $row['so_detail_id_product'] !== (string) $product) {
                continue;
            }

            $this->rows[$i]['so_detail_id_product'] = '';
            $this->addError($property, 'Product '.($this->options[$product] ?? $product).' sudah ada di data.');

            return;
        }
    }

    /** Product ids already used by rows other than $i. */
    public function takenBy(int $i): array
    {
        return collect($this->rows)
            ->except($i)
            ->pluck('so_detail_id_product')
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    public function priceOf(int $i): float
    {
        $product = $this->rows[$i]['so_detail_id_product'] ?? '';

        return (float) ($this->prices[$product] ?? 0);
    }

    public function getTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->rows as $i => $row) {
            $total += $this->priceOf($i) * (int) ($row['so_detail_qty'] ?: 0);
        }

        return $total;
    }

    // ponytail: no validation here — SoController validates via So::rules() on submit.
    private function blank(): array
    {
        return ['so_detail_id' => null, 'so_detail_id_product' => '', 'so_detail_qty' => 1];
    }
}; ?>

<div>
    <div class="space-y-3">
        @foreach($rows as $i => $row)
            <div wire:key="so-row-{{ $i }}"
                class="grid grid-cols-12 gap-3 items-end border border-outline-variant rounded-lg p-3 bg-surface-container-low">
                <input type="hidden" name="details[{{ $i }}][so_detail_id]" value="{{ $row['so_detail_id'] }}" />
                <div class="col-span-12 md:col-span-5">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Product</label>
                    <select name="details[{{ $i }}][so_detail_id_product]"
                        wire:model.live="rows.{{ $i }}.so_detail_id_product"
                        class="w-full h-12 pl-4 pr-10 bg-white border border-outline-variant rounded-lg font-body-sm appearance-none cursor-pointer focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none"
                        required>
                        <option value="">-- Silahkan Pilih --</option>
                        @foreach($options as $id => $nama)
                            <option value="{{ $id }}"
                                @selected((string) $row['so_detail_id_product'] === (string) $id)
                                @disabled(in_array((string) $id, $this->takenBy($i), true))>{{ $nama }}</option>
                        @endforeach
                    </select>
                    @error("rows.$i.so_detail_id_product")
                        <p class="font-label-caps text-label-caps text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-6 md:col-span-2">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Qty</label>
                    <input type="number" min="1" name="details[{{ $i }}][so_detail_qty]"
                        wire:model.live="rows.{{ $i }}.so_detail_qty"
                        class="w-full h-12 px-4 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none"
                        required />
                </div>
                <div class="col-span-6 md:col-span-2">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Harga</label>
                    <div class="h-12 px-4 flex items-center rounded-lg bg-surface-container font-body-sm text-on-surface-variant">
                        {{ formatAngka((int) $this->priceOf($i), 'Rp ') }}
                    </div>
                </div>
                <div class="col-span-8 md:col-span-2">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Subtotal</label>
                    <div class="h-12 px-4 flex items-center rounded-lg bg-surface-container font-body-sm text-on-surface">
                        {{ formatAngka((int) ($this->priceOf($i) * (int) ($row['so_detail_qty'] ?: 0)), 'Rp ') }}
                    </div>
                </div>
                <div class="col-span-4 md:col-span-1 flex justify-end">
                    <button type="button" wire:click="removeRow({{ $i }})" title="Hapus"
                        class="inline-flex items-center justify-center h-12 w-12 rounded-lg border border-error text-error hover:bg-error hover:text-on-error transition-colors">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between mt-4">
        <div class="font-headline-md text-headline-md text-on-surface">
            Total: {{ formatAngka((int) $this->total, 'Rp ') }}
        </div>
        <button type="button" wire:click="addRow"
            class="inline-flex items-center gap-1.5 h-10 px-4 rounded-lg bg-primary text-on-primary font-body-sm hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-lg">add</span>
            Tambah Product
        </button>
    </div>
</div>
