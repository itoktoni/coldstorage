@extends('frontend.layouts.public')

@section('title', $homeEntry?->title ?? 'ECM - PT. Elsyahfi Calibratech Mandiri')

@section('content')

@if ($homeData)
    @foreach ($homeData['sections'] as $template => $value)
        @includeIf('frontend.sections.' . $template, ['data' => $value])
    @endforeach

@endif

@endsection
