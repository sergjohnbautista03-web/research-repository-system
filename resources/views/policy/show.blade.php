@extends('layouts.app')

@section('title', 'Repository Use Policy')

@section('content')
@php
    $user = auth()->user();
    $hasAcceptedCurrentPolicy = $user
        && $user->policy_accepted_at
        && $user->policy_version === $policyVersion;
@endphp

<section class="policy-shell">
    <div class="policy-hero">
        <span class="policy-kicker">Ube Research Repository</span>
        <h1>Repository Use Policy</h1>
        <p>
            This policy protects registered accounts, research documents, authors, departments, and the academic integrity of the repository.
        </p>
        <div class="policy-meta">
            <span>Version {{ $policyVersion }}</span>
            <span>Applies to students, faculty, deans, administrators, and researchers</span>
        </div>
    </div>

    <div class="policy-layout">
        <article class="policy-card">
            <h2>Acceptable Use</h2>
            <p>
                Access to the Ube Research Repository is provided for authorized academic, research, review, and administrative purposes only. Users may browse, view, submit, review, approve, or manage research materials only within the permissions assigned to their account.
            </p>

            <h2>Account Responsibility</h2>
            <p>
                Your account is issued for your personal or official school role. You must not share your username, password, session access, or any other login credential with another person. You are responsible for activity performed through your account.
            </p>

            <h2>Research Material Protection</h2>
            <p>
                Research files, abstracts, documents, previews, watermarked pages, and repository records must not be copied, sold, uploaded elsewhere, distributed through messaging apps, posted on social media, or given to unauthorized users. Materials may be used only for legitimate school-related academic reference, review, or administrative work.
            </p>

            <h2>Student and Faculty Users</h2>
            <p>
                Students and faculty members may use repository access to support learning, citation, research review, and approved academic work. They must not claim another author's work as their own, remove attribution, bypass viewing restrictions, or share downloaded or viewed materials outside authorized channels.
            </p>

            <h2>Researcher Submissions</h2>
            <p>
                Users who apply as researchers or submit research must provide accurate information and upload documents that they are allowed to submit. Submitted research should respect authorship, citation rules, department requirements, and school review procedures.
            </p>

            <h2>Dean and Administrator Responsibilities</h2>
            <p>
                Deans and administrators are trusted to manage accounts and handle research records carefully. They must not approve unverified accounts, disclose credentials, or provide repository access to unauthorized persons.
            </p>

            <h2>Protected Viewer and Security Controls</h2>
            <p>
                The repository may use watermarking, access logs, screenshot or print detection, copy blocking, and other security controls. Attempting to bypass these controls, extract protected files, or hide unauthorized activity is not allowed.
            </p>

            <h2>Violations</h2>
            <p>
                Violations may result in account deactivation, loss of repository access, rejection of researcher privileges, administrative review, or referral to the appropriate school office for disciplinary action.
            </p>
        </article>

        <aside class="policy-action-card">
            <h2>Agreement</h2>
            <p>
                Before continuing, confirm that you understand and agree to follow this policy.
            </p>

            @auth
                @if($hasAcceptedCurrentPolicy)
                    <div class="policy-accepted-box">
                        <strong>Policy accepted</strong>
                        <span>You accepted version {{ $user->policy_version }} on {{ $user->policy_accepted_at->format('F j, Y h:i A') }}.</span>
                    </div>
                    <a href="{{ $user->isAdmin() ? route('admin.dashboard') : route('user.dashboard') }}" class="policy-btn">
                        Continue
                    </a>
                @else
                    @if($errors->has('accept_policy'))
                        <div class="policy-error">{{ $errors->first('accept_policy') }}</div>
                    @endif
                    <form method="POST" action="{{ route('policy.accept') }}" class="policy-form">
                        @csrf
                        <label class="policy-check">
                            <input type="checkbox" name="accept_policy" value="1" required>
                            <span>
                                I have read, understood, and agree to follow the Repository Use Policy. I will not share my account or protected research materials with unauthorized persons.
                            </span>
                        </label>
                        <button type="submit" class="policy-btn">Accept and Continue</button>
                    </form>
                @endif
            @else
                <div class="policy-accepted-box">
                    <strong>Please sign in</strong>
                    <span>Registered users must accept this policy before using account features.</span>
                </div>
                <a href="{{ route('login') }}" class="policy-btn">Sign In</a>
            @endauth
        </aside>
    </div>
</section>

<style>
.policy-shell{max-width:1120px;margin:0 auto;padding:34px 20px 56px;}
.policy-hero{padding:34px;border-radius:22px;background:linear-gradient(135deg,#fff,#f6f1ff);border:1px solid #eadff8;box-shadow:0 14px 38px rgba(59,15,122,.08);margin-bottom:22px;}
.policy-kicker{display:inline-flex;margin-bottom:10px;font-size:11px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:#7c3aed;}
.policy-hero h1{margin:0 0 10px;font-size:36px;line-height:1.1;color:#1a0638;}
.policy-hero p{max-width:760px;margin:0;color:#6f6290;line-height:1.7;}
.policy-meta{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;}
.policy-meta span{display:inline-flex;padding:8px 12px;border-radius:999px;background:#f0e7ff;border:1px solid #e1d3fa;color:#5b21b6;font-size:12px;font-weight:700;}
.policy-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:20px;align-items:start;}
.policy-card,.policy-action-card{background:#fff;border:1px solid #eee7f7;border-radius:18px;box-shadow:0 8px 28px rgba(59,15,122,.06);}
.policy-card{padding:30px;}
.policy-card h2,.policy-action-card h2{margin:0 0 10px;color:#1a0638;font-size:18px;}
.policy-card h2:not(:first-child){margin-top:28px;}
.policy-card p,.policy-action-card p{margin:0;color:#625472;line-height:1.75;font-size:14px;}
.policy-action-card{position:sticky;top:18px;padding:24px;}
.policy-form{display:grid;gap:18px;margin-top:18px;}
.policy-check{display:flex;align-items:flex-start;gap:10px;padding:14px;border-radius:14px;background:#faf7ff;border:1px solid #e9ddf8;color:#38215b;font-size:13px;line-height:1.65;cursor:pointer;}
.policy-check input{margin-top:4px;accent-color:#7c3aed;}
.policy-btn{display:inline-flex;width:100%;align-items:center;justify-content:center;margin-top:18px;padding:12px 16px;border:0;border-radius:999px;background:#3b0f7a;color:#fff;text-decoration:none;font-weight:800;font-size:14px;cursor:pointer;box-shadow:0 10px 24px rgba(59,15,122,.18);}
.policy-btn:hover{background:#2d0a5e;}
.policy-error{margin-top:16px;padding:12px 14px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px;font-weight:700;}
.policy-accepted-box{display:grid;gap:6px;margin-top:18px;padding:14px;border-radius:14px;background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;}
.policy-accepted-box strong{font-size:14px;}
.policy-accepted-box span{font-size:12.5px;line-height:1.55;color:#166534;}
@media(max-width:860px){.policy-layout{grid-template-columns:1fr;}.policy-action-card{position:static;}.policy-hero{padding:26px;}.policy-hero h1{font-size:30px;}}
</style>
@endsection
