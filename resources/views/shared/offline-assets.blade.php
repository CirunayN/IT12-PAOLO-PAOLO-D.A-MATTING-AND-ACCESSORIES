{{-- Prebuilt, project-local assets also work when no Vite server is running. --}}
@php
    $cssPath = public_path('assets/build/styles.css');
    $jsPath = public_path('assets/build/app.js');
    $cssVer = file_exists($cssPath) ? filemtime($cssPath) : '1';
    $jsVer = file_exists($jsPath) ? filemtime($jsPath) : '1';
@endphp
<link rel="stylesheet" href="{{ asset('assets/build/styles.css') }}?v={{ $cssVer }}">
<script type="module" src="{{ asset('assets/build/app.js') }}?v={{ $jsVer }}"></script>
