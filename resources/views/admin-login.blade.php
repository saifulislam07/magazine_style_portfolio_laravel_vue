<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f0e8">
        <title>Admin sign in — Fieldnotes</title>
        @vite(['resources/css/app.css', 'resources/css/admin.css'])
    </head>
    <body class="admin-body">
        <main class="login-wrap">
            <a class="admin-brand" href="{{ url('/') }}">
                <span>F.</span> FIELDNOTES <small>ADMIN</small>
            </a>
            <section class="login-card">
                <p class="admin-eyebrow">PRIVATE STUDIO</p>
                <h1>Welcome back.</h1>
                <p class="login-copy">Sign in to edit your photography book.</p>
                @if ($errors->any())
                    <div class="admin-alert" role="alert">{{ $errors->first() }}</div>
                @endif
                <form method="POST" action="{{ route('admin.login.store') }}">
                    @csrf
                    <label>
                        Email address
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                    </label>
                    <label>
                        Password
                        <input type="password" name="password" autocomplete="current-password" required>
                    </label>
                    <label class="remember-row">
                        <input type="checkbox" name="remember" value="1">
                        Keep me signed in
                    </label>
                    <button class="admin-primary" type="submit">Sign in <span>→</span></button>
                </form>
            </section>
            <a class="back-to-book" href="{{ url('/') }}">← Return to the book</a>
        </main>
    </body>
</html>
