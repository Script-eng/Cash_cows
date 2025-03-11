<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Cash Cows') }}</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }
        .hero {
            background-color: #4a6fdc;
            color: white;
            padding: 100px 0;
        }
        .features {
            padding: 80px 0;
        }
        .feature-card {
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: 100%;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            color: #4a6fdc;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Cash Cows</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="nav-link">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link">Log in</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a href="{{ route('register') }}" class="nav-link">Register</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Welcome to Cash Cows</h1>
            <p class="lead mb-5">A modern solution for managing your Sacco savings and contributions</p>
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="btn btn-light btn-lg px-5">Go to Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-light btn-lg me-3">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Register</a>
                    @endif
                @endauth
            @endif
        </div>
    </section>

    <section class="features bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Features</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">📊</div>
                        <h3>Track Contributions</h3>
                        <p>Easily monitor your savings contributions and track your growth over time.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">📑</div>
                        <h3>Generate Reports</h3>
                        <p>Create detailed reports for any time period to review your financial progress.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card bg-white">
                        <div class="feature-icon">🔒</div>
                        <h3>Secure Access</h3>
                        <p>Role-based access ensures your financial data remains secure and private.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; {{ date('Y') }} Cash Cows. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>