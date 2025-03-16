<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Cash Cows') }} - Admin</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom admin styles -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background-color: #f8f9fc;
        }
        
        /* Sidebar styles */
        .sidebar {
            width: 250px;
            min-height: 100vh;
            background-color: #4e73df;
            background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            background-size: cover;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            height: 70px;
            display: flex;
            align-items: center;
            padding-left: 1.5rem;
            color: white;
            font-weight: 800;
            font-size: 1.2rem;
        }
        
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem;
        }
        
        .sidebar-heading {
            padding: 0.7rem 1rem;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.05rem;
        }
        
        .nav-item {
            position: relative;
            margin-bottom: 0.2rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.7rem 1rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 0.35rem;
        }
        
        .nav-link i {
            margin-right: 0.7rem;
        }
        
        /* Topbar styles */
        .topbar {
            height: 70px;
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            z-index: 900;
            transition: all 0.3s;
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
        }
        
        .content-wrapper {
            margin-left: 250px;
            padding-top: 90px;
            padding-bottom: 3rem;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .dropdown-menu {
            font-size: 0.85rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        /* Card styles */
        .card {
            margin-bottom: 24px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border-radius: 0.35rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .border-left-primary {
            border-left: 0.25rem solid var(--primary-color) !important;
        }
        
        .border-left-success {
            border-left: 0.25rem solid var(--success-color) !important;
        }
        
        .border-left-info {
            border-left: 0.25rem solid var(--info-color) !important;
        }
        
        .border-left-warning {
            border-left: 0.25rem solid var(--warning-color) !important;
        }
        
        .border-left-danger {
            border-left: 0.25rem solid var(--danger-color) !important;
        }
        
        .chart-area {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100px;
            }
            
            .sidebar .nav-link span {
                display: none;
            }
            
            .sidebar-brand {
                padding-left: 1rem;
                justify-content: center;
            }
            
            .topbar, .content-wrapper {
                margin-left: 100px;
            }
        }
        
        /* Sidebar toggle */
        .sidebar-toggled .sidebar {
            width: 100px;
            .sidebar-toggled .sidebar {
            width: 100px;
        }
        
        .sidebar-toggled .sidebar .nav-link span {
            display: none;
        }
        
        .sidebar-toggled .sidebar-brand {
            padding-left: 1rem;
            justify-content: center;
        }
        
        .sidebar-toggled .topbar, 
        .sidebar-toggled .content-wrapper {
            margin-left: 100px;
        }
    </style>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <ul class="sidebar navbar-nav">
            <li class="sidebar-brand d-flex align-items-center justify-content-center">
                <div class="sidebar-brand-icon">
                    <i class="fas fa-cow"></i>
                </div>
                <div class="sidebar-brand-text mx-3">Cash Cows</div>
            </li>
            
            <hr class="sidebar-divider my-0">
            
            <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Member Management
            </div>
            
            <li class="nav-item {{ request()->routeIs('admin.members.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.members.index') }}">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Members</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Financial Management
            </div>
            
            <li class="nav-item {{ request()->routeIs('admin.contributions.*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.contributions.index') }}">
                    <i class="fas fa-fw fa-money-bill-wave"></i>
                    <span>Contributions</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link" href="{{ route('admin.contributions.batch.create') }}">
                    <i class="fas fa-fw fa-tasks"></i>
                    <span>Batch Entry</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading">
                Reports
            </div>
            
            <li class="nav-item {{ request()->routeIs('admin.reports*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.reports') }}">
                    <i class="fas fa-fw fa-chart-bar"></i>
                    <span>Reports & Analytics</span>
                </a>
            </li>
            
            <hr class="sidebar-divider d-none d-md-block">
            
            <li class="nav-item">
                <a class="nav-link" href="{{ route('member.dashboard') }}">
                    <i class="fas fa-fw fa-arrow-left"></i>
                    <span>Return to Member Area</span>
                </a>
            </li>
        </ul>
        
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    
                    <!-- Topbar Search -->
                    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0 small" placeholder="Search for..."
                                aria-label="Search" aria-describedby="basic-addon2">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button">
                                    <i class="fas fa-search fa-sm"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>
                        
                        <!-- Nav Item - Alerts -->
                        <li class="nav-item dropdown no-arrow mx-1">
                            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-bell fa-fw"></i>
                                <!-- Counter - Alerts -->
                                <span class="badge badge-danger badge-counter">3+</span>
                            </a>
                            <!-- Dropdown - Alerts -->
                            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="alertsDropdown">
                                <h6 class="dropdown-header">
                                    Alerts Center
                                </h6>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-primary">
                                            <i class="fas fa-file-alt text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 12, 2024</div>
                                        <span class="font-weight-bold">3 new members joined the Sacco!</span>
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-success">
                                            <i class="fas fa-donate text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 7, 2024</div>
                                        15 contributions were verified and processed.
                                    </div>
                                </a>
                                <a class="dropdown-item d-flex align-items-center" href="#">
                                    <div class="mr-3">
                                        <div class="icon-circle bg-warning">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="small text-gray-500">December 2, 2024</div>
                                        5 members have outstanding payments for this month.
                                    </div>
                                </a>
                                <a class="dropdown-item text-center small text-gray-500" href="#">Show All Alerts</a>
                            </div>
                        </li>
                        
                        <div class="topbar-divider d-none d-sm-block"></div>
                        
                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small">{{ Auth::user()->name }}</span>
                                <img class="img-profile rounded-circle"
                                    src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=4e73df&color=ffffff">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profile
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </li>
                    </ul>
                </nav>
                <!-- End of Topbar -->
                
                <!-- Alert Messages -->
                <div class="container-fluid">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                </div>
                
                <!-- Begin Page Content -->
                <div class="content-wrapper">
                    @yield('content')
                </div>
                <!-- End of Page Content -->
            </div>
            
            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Cash Cows Sacco {{ date('Y') }}</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        $(document).ready(function() {
            $("#sidebarToggleTop").click(function() {
                $("body").toggleClass("sidebar-toggled");
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>