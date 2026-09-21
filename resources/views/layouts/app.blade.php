<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ube Repository') — Online Research Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
</head>
<body>
@php
    $authUser = auth()->user();
    $departmentShortNames = [
        'College of Accountancy and Business Education' => 'CABE',
        'College of Arts and Sciences' => 'CAS',
        'College of Computer Studies' => 'CCS',
        'College of Criminal Justice Education' => 'CCJE',
        'College of Engineering and Architecture' => 'CEA',
        'College of Hospitality Management' => 'CHM',
        'College of Teacher Education' => 'CTE',
    ];
    $adminPortalLabel = auth()->check() && $authUser->isAdmin()
        ? ($authUser->isDepartmentDean() ? 'Dean' : 'Admin')
        : null;
    $isFacultyAccount = auth()->check()
        && $authUser->role === 'researcher'
        && is_null($authUser->graduation_year);
    $isStudentResearcher = auth()->check()
        && $authUser->role === 'researcher'
        && ! is_null($authUser->graduation_year);
    $userRoleLabel = auth()->check()
        ? ($authUser->isDepartmentDean() ? 'Dean' : ($isFacultyAccount ? 'Faculty' : ($isStudentResearcher ? 'Student Researcher' : ucfirst($authUser->role))))
        : null;
@endphp

<!-- TOP NAV -->
<header class="top-nav">
    <a href="{{ route('home') }}" class="logo-wrap" aria-label="Ube Repository home">
        <img src="{{ asset('images/philcstlogologo.png') }}" alt="" class="logo-seal" aria-hidden="true">
        <span class="logo-text">
            <strong>Philippine College of Science and Technology</strong>
            <span>Calasiao, Pangasinan, Philippines 2418</span>
        </span>
    </a>

    <button class="nav-toggle" type="button" aria-label="Toggle navigation" onclick="document.querySelector('.main-nav').classList.toggle('open')">☰</button>
    <nav class="main-nav">
        <button class="nav-toggle" onclick="document.querySelector('.main-nav').classList.toggle('open')">☰</button>
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>
        <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}">About</a>
        <a href="{{ route('faq') }}" class="{{ request()->routeIs('faq') ? 'active' : '' }}">FAQ</a>
        <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'active' : '' }}">Contact</a>

        @if(auth()->check())
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="nav-badge admin">
            ⚙ {{ $adminPortalLabel }}
            </a>
            @else
            <a href="{{ route('user.dashboard') }}" class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">Dashboard</a>
            @endif

            <div class="user-menu">
    <button type="button" class="user-btn" aria-haspopup="menu">
        @if(auth()->user()->profile_photo)
            <img src="{{ asset('storage/' . auth()->user()->profile_photo) }}" alt="avatar" style="width:28px;height:28px;border-radius:50%;object-fit:cover;">
        @else
            <span class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        @endif
        {{ Str::limit(auth()->user()->name, 14) }}
        <span class="caret">▾</span>
    </button>
    <div class="user-dropdown">
    <div class="dropdown-header">
        <div class="dd-avatar-row">
            @if(auth()->user()->profile_photo)
                <img src="{{ asset('storage/' . auth()->user()->profile_photo) }}" alt="avatar" class="dd-avatar-img">
            @else
                <div class="dd-avatar-big">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
            @endif
            <div class="dd-user-meta">
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->email }}</small>
            </div>
        </div>
        <div class="dd-badge-row">
            <span class="role-badge {{ auth()->user()->role }}">
                <span class="dd-role-dot"></span>{{ $userRoleLabel }}
            </span>
            @if(auth()->user()->student_id)
                <span class="dd-user-id">ID: {{ auth()->user()->student_id }}</span>
            @endif
        </div>
    </div>

    <div class="dd-actions">
        <a href="{{ route('profile.edit') }}" class="dropdown-item">
            <div class="dd-item-icon dd-icon-profile">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div class="dd-item-copy">
                <span class="dd-item-label">My Profile</span>
                <span class="dd-item-sub">Manage your account</span>
            </div>
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item dd-signout">
                <div class="dd-item-icon dd-icon-out">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16,17 21,12 16,7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                </div>
                <div class="dd-item-copy">
                    <span class="dd-item-label">Sign Out</span>
                    <span class="dd-item-sub">End your current session</span>
                </div>
            </button>
        </form>
    </div>
</div>
            </div>
        @else
            <a
                href="{{ route('login') }}"
                class="nav-login js-login-modal-trigger"
                data-login-modal-trigger
                aria-haspopup="dialog"
            >Login</a>
        @endif
    </nav>
</header>

@hasSection('hero')
<div class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-content">
        @yield('hero')
    </div>
</div>
@endif

<!-- Flash Messages -->
@if(session('success'))
    <div class="flash flash-success" id="flashMsg">
        <span>✓</span> {{ session('success') }}
        <button onclick="this.parentElement.remove()">×</button>
    </div>
@endif
@if(session('error'))
    <div class="flash flash-error" id="flashMsg">
        <span>✕</span> {{ session('error') }}
        <button onclick="this.parentElement.remove()">×</button>
    </div>
@endif

@yield('content')

@guest
    @php
        $loginModalHasErrors = $errors->has('login') || $errors->has('password');
        $loginModalShouldOpen = $loginModalHasErrors || session('show_login_modal');
    @endphp
    <div
        class="login-modal{{ $loginModalShouldOpen ? ' is-open' : '' }}"
        id="loginModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="loginModalTitle"
        data-login-modal
        data-login-url="{{ route('login') }}"
        data-open-on-load="{{ $loginModalShouldOpen ? 'true' : 'false' }}"
    >
        <div class="login-modal-backdrop" data-login-modal-close></div>
        <div class="login-modal-panel" role="document">
            <button type="button" class="login-modal-close" data-login-modal-close aria-label="Close sign in modal">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>

            <div class="login-modal-header">
                <div class="login-modal-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                        <polyline points="10 17 15 12 10 7"/>
                        <line x1="15" y1="12" x2="3" y2="12"/>
                    </svg>
                </div>
                <div>
                    <h2 id="loginModalTitle">Sign In</h2>
                    <p>Enter your credentials to continue</p>
                </div>
            </div>

            @if($loginModalHasErrors)
                <div class="login-modal-error">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="login-modal-form" autocomplete="off">
                @csrf
                <div class="login-modal-group">
                    <label for="modal-login">Username</label>
                    <div class="login-modal-input-wrap">
                        <span class="login-modal-input-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21a8 8 0 0 0-16 0"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                        </span>
                        <input type="text" id="modal-login" name="login" value="{{ old('login') }}" placeholder="Enter your username" autocomplete="username">
                    </div>
                </div>

                <div class="login-modal-group">
                    <label for="modal-password">Password</label>
                    <div class="login-modal-input-wrap login-modal-password-wrap">
                        <span class="login-modal-input-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="modal-password" name="password" placeholder="Your password" autocomplete="current-password">
                        <button type="button" class="login-modal-toggle" onclick="togglePassword('modal-password', this)" aria-label="Show or hide password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-modal-options">
                    <label class="login-modal-remember">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="{{ route('password.request') }}" class="login-modal-forgot">Forgot Password?</a>
                </div>

                <button type="submit" class="login-modal-submit">
                    <span>Sign In</span>
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </button>

                <p class="login-modal-policy">
                    By signing in, you agree to follow the <a href="{{ route('policy.show') }}">Repository Use Policy</a>.
                </p>
            </form>
        </div>
    </div>
@endguest

<footer class="site-footer">
    <div class="footer-inner">

        <div class="footer-top">
            <!-- Brand -->
            <div class="footer-col">
                <span class="footer-logo">Ube Repository</span>
                <p class="footer-tagline">"Preserving Knowledge, Empowering Research"</p>
                <p class="footer-desc">The official online research portal of the Philippine College of Science and Technology.</p>
                <div class="footer-socials">
                    <a href="https://www.facebook.com/PhilCST94" target="_blank" title="Facebook">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                    </a>
                    <a href="mailto:repository@philcst.edu.ph" title="Email">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-col">
                <h4 class="footer-col-title">Quick Links</h4>
                <ul class="footer-links-list">
                    <li><a href="{{ route('home') }}">Home</a></li>
                    <li><a href="{{ route('about') }}">About</a></li>
                    <li><a href="{{ route('faq') }}">FAQ</a></li>
                    <li><a href="{{ route('contact') }}">Contact</a></li>
                    <li><a href="{{ route('policy.show') }}">Repository Policy</a></li>
                    @auth
                    @unless(auth()->user()->isAdmin())
                        @if(auth()->user()->canSubmitResearch())
                        <li><a href="{{ route('research.submit') }}">Submit Research</a></li>
                        @endif
                    @endunless
                    @endauth
                </ul>
            </div>

            <!-- Address -->
            <div class="footer-col">
                <h4 class="footer-col-title">Address</h4>
                <p class="footer-address">
                    Philippine College of Science and Technology<br>
                    Calasiao, Pangasinan<br>
                    Philippines 2418
                </p>
                <p class="footer-address" style="margin-top:10px;">
                    (075) 522-8032<br>
                    philcstreg@yahoo.com
                </p>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} Ube Repository. All rights reserved.</p>
            <p>Philippine College of Science and Technology</p>
        </div>

    </div>
</footer>

<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
