<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f0e8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Admin — Fieldnotes</title>
        @vite(['resources/css/app.css', 'resources/css/admin.css', 'resources/js/admin.js'])
    </head>
    <body class="admin-body">
        <div id="admin-app"></div>
    </body>
</html>
