<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ICA Church — sermons, cells, events and more">

    <title inertia>{{ config('app.name', 'ICA APP') }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">

    {{-- Deliberately omits app.blade.php's legacy theme (public/css/style.css) and
         Flowbite: their .card/.btn/.badge rules collide with the UI design system.
         Fonts are loaded by resources/css/ui.css. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
