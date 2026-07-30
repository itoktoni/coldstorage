<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="shadcn">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-base-200 antialiased">
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6">
        <div class="flex w-full max-w-md flex-col">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium mb-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-primary">
                    <x-app-logo-icon class="size-6 fill-current text-primary-content" />
                </span>
                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>
            <div class="flex flex-col gap-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>
