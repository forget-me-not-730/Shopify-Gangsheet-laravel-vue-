<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/favicon.png') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}"/>

    <link rel="stylesheet" type="text/css" href="{{asset('assets/fonts/fonts.css?v=2')}}"/>
    <link rel="stylesheet" href="{{ spaces()->url("fonts/css/all.css") }}">

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=PT Sans Narrow">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Noto Sans">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Agdasima">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Barlow Condensed">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Josefin Sans">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">

    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <script>
        window.appEnv = "{{ app()->environment() }}"
    </script>

    @if(auth()->check() && !(auth()->user()?->isAdmin() ?? false))

        <script src='//fw-cdn.com/12170457/4662351.js' chat='true'></script>

        <style>

        </style>

    @endif

    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead

</head>
<body class="font-oswald antialiased">
@inertia
</body>
</html>
