<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f3f0e8">
        <title>Admin sign in — {{ ucfirst(strtolower($brand)) }}</title>
        @vite(['resources/css/app.css', 'resources/css/admin.css'])
    </head>
    <body class="admin-body">
        <main class="login-wrap">
            <aside class="login-visual" aria-hidden="true">
                @if ($coverImage)
                    <img src="{{ $coverImage }}" alt="">
                @endif
                <div class="login-visual-shade"></div>
                <div class="login-visual-copy">
                    <span class="login-visual-mark">{{ mb_substr($brand, 0, 1) }}.</span>
                    <p>{{ $brand }} STUDIO</p>
                    <h2>Every page,<br>in your hands.</h2>
                    @if ($author)
                        <small>{{ $author }}</small>
                    @endif
                </div>
            </aside>

            <section class="login-panel">
                <a class="admin-brand" href="{{ url('/') }}">
                    <span>{{ mb_substr($brand, 0, 1) }}.</span> {{ $brand }} <small>ADMIN</small>
                </a>

                <div class="login-card">
                    <p class="admin-eyebrow">PRIVATE STUDIO</p>
                    <h1>Welcome back.</h1>
                    <p class="login-copy">Sign in to edit your photography book.</p>

                    @if ($errors->any())
                        <div class="admin-alert" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.login.store') }}">
                        @csrf
                        <label class="admin-field">
                            <span>Email address</span>
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="you@example.com" required autofocus>
                        </label>
                        <label class="admin-field">
                            <span>Password</span>
                            <input type="password" name="password" autocomplete="current-password" placeholder="••••••••" required>
                        </label>
                        <label class="remember-row">
                            <input type="checkbox" name="remember" value="1">
                            Keep me signed in
                        </label>
                        <button class="admin-primary" type="submit">Sign in <span>→</span></button>
                    </form>
                </div>

                <a class="back-to-book" href="{{ url('/') }}">← Return to the book</a>
            </section>
        </main>
    </body>
</html>
