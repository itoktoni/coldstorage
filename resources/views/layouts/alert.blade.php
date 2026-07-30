@if ($errors->any())
    <link href="/vendor/flasher/flasher.min.css" rel="stylesheet">
    <script src="/vendor/flasher/flasher.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            @foreach ($errors->all() as $error)
                flasher.error("{{ $error }}");
            @endforeach
        });
    </script>
@endif
