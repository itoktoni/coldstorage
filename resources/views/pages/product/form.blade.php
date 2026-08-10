<?php /** @var App\Models\Product $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => moduleRoute('getTable'), 'label' => moduleLabel()], ['url' => '', 'label' => isset($model) && $model->exists ? 'Update' : 'Create']]" />

    <x-form :model="$model" enctype="multipart/form-data">
        <x-card :label="moduleLabel()">
            @bind($model ?? null)
                <x-select col="4" name="product_category" :options="$categoryOptions ?? []" placeholder="Pilih kategori..." />
                <x-input col="4" name="product_nama" />
                <x-input col="4" name="product_harga" type="number" />
                <x-select col="4" name="product_status" :options="['active' => 'Active', 'inactive' => 'Inactive']" placeholder="Pilih status..." />

                <div class="col-span-12 md:col-span-4">
                    <label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">Foto Produk</label>

                    {{-- Camera capture button (mobile) --}}
                    <button type="button" id="btn-camera-capture"
                        class="hidden w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary/5 px-4 py-6 text-primary font-bold hover:bg-primary/10 transition-colors mb-2">
                        <span class="material-symbols-outlined text-2xl">photo_camera</span>
                        <span class="font-body-sm">Foto Produk</span>
                    </button>

                    {{-- File browse button (desktop) --}}
                    <button type="button" id="btn-browse-file"
                        class="hidden w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-outline-variant px-4 py-6 text-on-surface-variant hover:border-primary hover:bg-primary/5 transition-colors">
                        <span class="material-symbols-outlined text-2xl">upload_file</span>
                        <span class="font-body-sm">Pilih File</span>
                    </button>

                    {{-- Preview --}}
                    <div id="image-preview" class="relative hidden mt-2">
                        <img id="preview-img" src="" alt="Preview" class="w-full max-h-48 object-contain rounded-lg border border-outline-variant">
                        <button type="button" id="btn-remove-image"
                            class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm shadow-md hover:bg-red-600">
                            ✕
                        </button>
                    </div>

                    {{-- Existing image (edit mode) --}}
                    @if(isset($model) && $model->exists && $model->product_image)
                        <div id="existing-image" class="relative mt-2">
                            <img src="{{ Storage::disk('public')->url($model->product_image) }}" alt="{{ $model->product_nama }}" class="w-full max-h-48 object-contain rounded-lg border border-outline-variant">
                            <button type="button" id="btn-remove-existing"
                                class="absolute top-2 right-2 w-7 h-7 rounded-full bg-red-500 text-white flex items-center justify-center text-sm shadow-md hover:bg-red-600">
                                ✕
                            </button>
                        </div>
                    @endif

                    {{-- Hidden inputs --}}
                    <input type="file" id="input-camera" name="product_image" accept="image/jpg,image/jpeg,image/png,image/webp" capture="environment" class="hidden" disabled>
                    <input type="file" id="input-file" name="product_image" accept="image/jpg,image/jpeg,image/png,image/webp" class="hidden">
                    <input type="hidden" id="input-remove" name="remove_image" value="0">
                </div>
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    @push('scripts')
    <script>
    (function() {
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent) || (navigator.maxTouchPoints > 0 && window.innerWidth < 768);
        const btnCamera = document.getElementById('btn-camera-capture');
        const btnBrowse = document.getElementById('btn-browse-file');
        const inputCamera = document.getElementById('input-camera');
        const inputFile = document.getElementById('input-file');
        const preview = document.getElementById('image-preview');
        const previewImg = document.getElementById('preview-img');
        const btnRemove = document.getElementById('btn-remove-image');
        const btnRemoveExisting = document.getElementById('btn-remove-existing');
        const inputRemove = document.getElementById('input-remove');
        const existingImage = document.getElementById('existing-image');

        if (isMobile) {
            btnCamera.classList.remove('hidden');
            btnCamera.classList.add('flex');
        } else {
            btnBrowse.classList.remove('hidden');
            btnBrowse.classList.add('flex');
        }

        function showPreview(file) {
            if (!file || !file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.remove('hidden');
                btnCamera.classList.add('hidden');
                btnBrowse.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }

        btnCamera.addEventListener('click', () => inputCamera.click());

        btnBrowse.addEventListener('click', () => inputFile.click());

        inputCamera.addEventListener('change', function() {
            if (this.files[0]) {
                inputFile.value = '';
                inputFile.disabled = true;
                inputCamera.disabled = false;
                showPreview(this.files[0]);
            }
        });

        inputFile.addEventListener('change', function() {
            if (this.files[0]) {
                inputCamera.value = '';
                inputCamera.disabled = true;
                inputFile.disabled = false;
                showPreview(this.files[0]);
            }
        });

        btnRemove.addEventListener('click', function() {
            inputFile.value = '';
            inputCamera.value = '';
            inputFile.disabled = false;
            inputCamera.disabled = true;
            preview.classList.add('hidden');
            previewImg.src = '';
            btnCamera.classList.remove('hidden');
            btnBrowse.classList.remove('hidden');
        });

        if (btnRemoveExisting) {
            btnRemoveExisting.addEventListener('click', function() {
                existingImage.classList.add('hidden');
                inputRemove.value = '1';
            });
        }
    })();
    </script>
    @endpush
</x-layouts::app>
