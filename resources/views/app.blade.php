<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'AI CS Chatbot') }}</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
<!-- AI CS Chatbot Widget -->
<script
  src="{{ asset('chatbot.js') }}"
  data-bot-id="2"
  defer></script>
</head>
<body class="antialiased">
    @inertia
</body>
</html>
