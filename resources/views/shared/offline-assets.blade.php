{{-- Prebuilt, project-local assets also work when no Vite server is running. --}}
<link rel="stylesheet" href="{{ asset('assets/build/styles.css') }}?v={{ filemtime(public_path('assets/build/styles.css')) }}">
<script type="module" src="{{ asset('assets/build/app.js') }}?v={{ filemtime(public_path('assets/build/app.js')) }}"></script>
