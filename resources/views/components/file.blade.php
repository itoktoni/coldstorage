@props(['name', 'label' => null, 'col' => '12', 'multiple' => false, 'accept' => 'image/*,.pdf'])
@php $label = $label ?? formatLabel($name); $uid = 'file_'.uniqid(); @endphp
<div class="col-span-12 md:col-span-{{ $col }}">
    @if($label)<label class="font-body-sm text-body-sm font-bold text-on-surface-variant block mb-1">{{ $label }}</label>@endif
    <div class="border-2 border-dashed border-outline-variant rounded-xl p-8 text-center cursor-pointer hover:border-primary transition-colors"
        onclick="document.getElementById('{{ $uid }}').click()"
        ondragover="event.preventDefault();this.classList.add('border-primary','bg-primary/5')"
        ondragleave="this.classList.remove('border-primary','bg-primary/5')"
        ondrop="event.preventDefault();this.classList.remove('border-primary','bg-primary/5');document.getElementById('{{ $uid }}').files=event.dataTransfer.files">
        <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-3 block">upload_file</span>
        <p class="font-body-sm text-body-sm text-on-surface-variant mb-1">Drag & drop files here</p>
        <p class="font-label-caps text-label-caps text-outline">or click to browse</p>
        <input type="file" id="{{ $uid }}" name="{{ $name }}" class="hidden" {{ $multiple ? 'multiple' : '' }} accept="{{ $accept }}" {{ $attributes }}>
    </div>
</div>
