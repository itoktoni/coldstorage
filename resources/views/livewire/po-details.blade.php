<?php

use Livewire\Component;

new class extends Component {
    public array $rows = [];
    public array $options = [];

    public function mount(array $rows = [], array $options = []): void
    {
        $this->options = $options;
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
        if (! preg_match('/^rows\.(\d+)\.po_detail_id_product$/', $property, $m)) {
            return;
        }

        $i = (int) $m[1];
        $product = $this->rows[$i]['po_detail_id_product'] ?? '';
        if ($product === '' || $product === null) {
            return;
        }

        foreach ($this->rows as $j => $row) {
            if ($j === $i || (string) $row['po_detail_id_product'] !== (string) $product) {
                continue;
            }

            $this->rows[$i]['po_detail_id_product'] = '';
            $this->addError($property, 'Product ' . ($this->options[$product] ?? $product) . ' sudah ada di data.');

            return;
        }
    }

    /** Product ids already used by rows other than $i. */
    public function takenBy(int $i): array
    {
        return collect($this->rows)
            ->except($i)
            ->pluck('po_detail_id_product')
            ->filter(fn ($v) => $v !== '' && $v !== null)
            ->map(fn ($v) => (string) $v)
            ->values()
            ->all();
    }

    // ponytail: no validation here — PoController validates via Po::rules() on submit.
    private function blank(): array
    {
        return ['po_detail_id' => null, 'po_detail_id_product' => '', 'po_detail_qty' => 1];
    }
}; ?>

<div>
    <div class="space-y-3">
        @foreach($rows as $i => $row)
            <div wire:key="po-row-{{ $i }}"
                class="grid grid-cols-12 gap-3 items-end border border-outline-variant rounded-lg p-3 bg-surface-container-low">
                <input type="hidden" name="details[{{ $i }}][po_detail_id]" value="{{ $row['po_detail_id'] }}" />
                <div class="col-span-12 md:col-span-7">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Product</label>
                    <div class="relative">
                        <select name="details[{{ $i }}][po_detail_id_product]"
                            wire:model.live="rows.{{ $i }}.po_detail_id_product"
                            class="w-full h-12 pl-4 pr-10 bg-white border border-outline-variant rounded-lg font-body-sm appearance-none cursor-pointer focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none"
                            required>
                            <option value="">-- Silahkan Pilih --</option>
                            @foreach($options as $id => $nama)
                                <option value="{{ $id }}"
                                    @selected((string) $row['po_detail_id_product'] === (string) $id)
                                    @disabled(in_array((string) $id, $this->takenBy($i), true))>{{ $nama }}</option>
                            @endforeach
                        </select>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-on-surface-variant text-xl">expand_more</span>
                    </div>
                    @error("rows.$i.po_detail_id_product")
                        <p class="font-label-caps text-label-caps text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="col-span-8 md:col-span-3">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Qty</label>
                    <input type="number" min="1" name="details[{{ $i }}][po_detail_qty]"
                        wire:model="rows.{{ $i }}.po_detail_qty"
                        class="w-full h-12 px-4 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary-container focus:ring-1 focus:ring-primary-container outline-none"
                        required />
                </div>
                <div class="col-span-4 md:col-span-2 flex justify-end">
                    <button type="button" wire:click="removeRow({{ $i }})" title="Hapus"
                        class="inline-flex items-center justify-center h-12 w-12 rounded-lg border border-error text-error hover:bg-error hover:text-on-error transition-colors">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end mt-4">
        <button type="button" wire:click="addRow"
            class="inline-flex items-center gap-1.5 h-10 px-4 rounded-lg bg-primary text-on-primary font-body-sm hover:opacity-90 transition-opacity">
            <span class="material-symbols-outlined text-lg">add</span>
            Tambah Product
        </button>
    </div>
</div>
