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

                    {{-- Camera button (mobile) --}}
                    <button type="button" id="btn-camera-capture"
                        class="md:hidden w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary/5 px-4 py-5 text-primary font-bold hover:bg-primary/10 transition-colors mb-2">
                        <span class="material-symbols-outlined text-2xl">photo_camera</span>
                        <span class="font-body-sm">Ambil Foto</span>
                    </button>

                    {{-- Gallery button (mobile) --}}
                    <button type="button" id="btn-gallery"
                        class="md:hidden w-full flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-primary bg-primary/5 px-4 py-5 text-primary font-bold hover:bg-primary/10 transition-colors mb-2">
                        <span class="material-symbols-outlined text-2xl">photo_library</span>
                        <span class="font-body-sm">Pilih dari Galeri</span>
                    </button>

                    {{-- File browse button (desktop) --}}
                    <button type="button" id="btn-browse-file"
                        class="hidden md:flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-outline-variant px-4 py-6 text-on-surface-variant hover:border-primary hover:bg-primary/5 transition-colors">
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

                    {{-- Hidden file input (no capture — showImageChooser dialog will appear) --}}
                    <input type="file" id="input-product-image" name="product_image"
                        accept="image/jpeg,image/png,image/webp" class="hidden">
                    <input type="hidden" id="input-remove" name="remove_image" value="0">
                </div>
            @endbind
        </x-card>

        <x-action :model="$model" :action="['save']"/>
    </x-form>

    @push('scripts')
    <script>
    (function() {
        const btnCamera    = document.getElementById('btn-camera-capture');
        const btnGallery   = document.getElementById('btn-gallery');
        const btnBrowse    = document.getElementById('btn-browse-file');
        const inputImage   = document.getElementById('input-product-image');
        const preview      = document.getElementById('image-preview');
        const previewImg   = document.getElementById('preview-img');
        const btnRemove    = document.getElementById('btn-remove-image');
        const btnRemoveExisting = document.getElementById('btn-remove-existing');
        const inputRemove  = document.getElementById('input-remove');
        const existingImage = document.getElementById('existing-image');

        var hasNativeBridge = typeof NativeBridge !== 'undefined'
            && typeof NativeBridge.openCamera === 'function';

        // ── 1. Convert a data-URL (base64) to a File object ──
        function dataURLtoFile(dataurl, filename) {
            var arr  = dataurl.split(',');
            var mime = (arr[0].match(/:(.*?);/) || [])[1] || 'image/jpeg';
            var bstr = atob(arr[1]);
            var n    = bstr.length;
            var u8   = new Uint8Array(n);
            while (n--) u8[n] = bstr.charCodeAt(n);
            return new File([u8], filename, { type: mime });
        }

        // ── 2. Set a File on the hidden <input> so the form submission works ──
        function setFileOnInput(file) {
            var dt = new DataTransfer();
            dt.items.add(file);
            inputImage.files = dt.files;
        }

        // ── 3. Show preview and hide the pick buttons ──
        function showPreview(src) {
            previewImg.src = src;
            preview.classList.remove('hidden');
            btnCamera.classList.add('hidden');
            btnGallery.classList.add('hidden');
            btnBrowse.classList.add('hidden');
        }

        function hidePreview() {
            inputImage.value = '';
            preview.classList.add('hidden');
            previewImg.src = '';
            btnCamera.classList.remove('hidden');
            btnGallery.classList.remove('hidden');
            btnBrowse.classList.remove('hidden');
        }

        // ── 4. NativeBridge callbacks (approach 3) ──
        window.onImageCaptured = function(base64) {
            if (!base64 || base64.indexOf('error') !== -1 && base64.indexOf('data:') === -1) return;
            var file = dataURLtoFile(base64, 'camera_' + Date.now() + '.jpg');
            setFileOnInput(file);
            showPreview(base64);
        };
        window.onImagePicked = function(base64) {
            if (!base64 || base64.indexOf('error') !== -1 && base64.indexOf('data:') === -1) return;
            var ext  = (base64.match(/^data:image\/(\w+)/) || [])[1] || 'jpeg';
            var file = dataURLtoFile(base64, 'gallery_' + Date.now() + '.' + ext);
            setFileOnInput(file);
            showPreview(base64);
        };

        // ── 5. Camera button — approach 3 (native bridge) or 2 (input+capture) ──
        btnCamera.addEventListener('click', function() {
            if (hasNativeBridge) {
                NativeBridge.openCamera();           // result via onImageCaptured
            } else {
                inputImage.setAttribute('capture', 'environment');
                inputImage.click();                  // fallback: opens camera via onShowFileChooser
                // Remove capture after a short delay so it doesn't affect other buttons
                setTimeout(function() { inputImage.removeAttribute('capture'); }, 2000);
            }
        });

        // ── 6. Gallery button — approach 3 (native bridge) or 1 (input no capture) ──
        btnGallery.addEventListener('click', function() {
            if (hasNativeBridge) {
                NativeBridge.openGallery();          // result via onImagePicked
            } else {
                inputImage.removeAttribute('capture');
                inputImage.click();                  // fallback: shows Camera/Gallery dialog
            }
        });

        // ── 7. Browse button (desktop) — approach 1: input click, no capture ──
        btnBrowse.addEventListener('click', function() {
            inputImage.removeAttribute('capture');
            inputImage.click();                      // opens file chooser
        });

        // ── 8. File input change (approach 1/2) — when file is picked via <input> ──
        inputImage.addEventListener('change', function() {
            if (this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) { showPreview(e.target.result); };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // ── 9. Remove buttons ──
        btnRemove.addEventListener('click', hidePreview);
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
