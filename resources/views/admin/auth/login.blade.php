<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="{{ asset('assets/img/Logo-ZA.png') }}" rel="icon">
    <title>{{ $title ?? 'Login' }}</title>

    <style>
        :root {
            --brand: #7d297f;
            --brand-dark: #5a2d67;
            --border: #e9eaf0;
            --text: #1f2430;
            --muted: #6b7280;
        }

        * {
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                radial-gradient(1100px 500px at 10% -10%, rgba(125, 41, 127, .22), transparent 60%),
                radial-gradient(900px 500px at 100% 110%, rgba(90, 45, 103, .25), transparent 55%),
                #f5f6fa;
        }

        .login-card {
            width: 100%;
            max-width: 410px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: 0 24px 60px -24px rgba(90, 45, 103, .35);
            padding: 34px 30px;
        }

        .login-logo {
            width: 56px;
            height: 56px;
            border-radius: 15px;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            box-shadow: 0 12px 24px -12px rgba(125, 41, 127, .7);
        }

        .login-logo img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .login-title {
            text-align: center;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -.02em;
            margin: 0 0 4px;
        }

        .login-sub {
            text-align: center;
            color: var(--muted);
            font-size: 14px;
            margin: 0 0 24px;
        }

        .field {
            margin-bottom: 16px;
        }

        .field label {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 17px;
        }

        .field input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14.5px;
            font-family: inherit;
            transition: border-color .15s, box-shadow .15s;
        }

        .field input:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(125, 41, 127, .12);
        }

        .field input.is-invalid {
            border-color: #d92d20;
        }

        .field-error {
            color: #d92d20;
            font-size: 12.5px;
            margin-top: 5px;
            display: block;
        }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 12px;
            padding: 13px;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            cursor: pointer;
            transition: transform .08s, filter .15s;
            margin-top: 6px;
        }

        .btn-login:hover {
            filter: brightness(1.05);
        }

        .btn-login:active {
            transform: scale(.98);
        }

        .login-foot {
            text-align: center;
            color: var(--muted);
            font-size: 12.5px;
            margin-top: 20px;
        }

        .alert-box {
            background: #fef3f2;
            border: 1px solid #fecdca;
            color: #b42318;
            border-radius: 12px;
            padding: 11px 14px;
            font-size: 13.5px;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <form class="login-card" method="POST" action="{{ route('auth') }}">
        @csrf

        <div class="login-logo">
            <img src="{{ asset('assets/img/Logo-ZA.png') }}" alt="Zillenial Action">
        </div>
        <h1 class="login-title">Selamat Datang</h1>
        <p class="login-sub">Masuk ke portal admin Zillenial Action</p>

        @if (session('error'))
            <div class="alert-box">{{ session('error') }}</div>
        @endif

        <div class="field">
            <label for="email">Email</label>
            <div class="input-wrap">
                <i class="bi bi-envelope"></i>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                    class="@error('email') is-invalid @enderror" placeholder="admin@zillenialaction.com" required
                    autocomplete="email" autofocus>
            </div>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Password</label>
            <div class="input-wrap">
                <i class="bi bi-lock"></i>
                <input id="password" type="password" name="password"
                    class="@error('password') is-invalid @enderror" placeholder="••••••••" required
                    autocomplete="current-password">
            </div>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn-login">Masuk</button>

        <p class="login-foot">© {{ date('Y') }} Zillenial Action</p>
    </form>
</body>

</html>
