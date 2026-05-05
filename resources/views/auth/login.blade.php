<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Tracking Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-page bg-body-secondary">
    <div class="login-box">
        <div class="card card-outline card-success">
            <div class="card-header text-center">
                <span class="h1"><b>Login</b></span>
            </div>
            <div class="card-body login-card-body">
                <p class="login-box-msg">Sign in to your account</p>

                <form method="POST" action="{{ route('login') }}">
                    @csrf
                    <div class="input-group mb-3">
                        <input type="email" name="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" placeholder="Email" required autofocus>
                        <div class="input-group-text"><span class="bi bi-envelope"></span></div>
                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                        <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                        @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-success btn-block w-100">Sign In</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
