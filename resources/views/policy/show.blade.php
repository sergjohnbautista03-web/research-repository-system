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
            Use published PhilCST research and journals responsibly, protect account access, and follow the responsibilities assigned to your role in the repository.
        </p>
        <div class="policy-meta">
            <span>Version {{ $policyVersion }}</span>
            <span>Applies to visitors, Students, Faculty, Department Deans, Research Coordinators, and Admin</span>
        </div>
    </div>

    <div class="policy-layout">
        <article class="policy-card">
            <h2>Academic Use & Public Browsing</h2>
            <p>
                The Ube Research Repository supports academic reference, journal preparation, review, publication, and authorized administration. Visitors may browse published titles, authors, abstracts, and publication details. Full-document viewing, saving research, and staff actions require the appropriate signed-in account and permissions.
            </p>

            <h2>Account Creation, Activation & Responsibility</h2>
            <p>
                Department Deans create or import Student and Faculty accounts for their assigned department and program. There is no public self-registration. Student and Faculty access to full documents requires an active, approved account and active enrollment in an open semester. Contact your Department Dean if your account is inactive or has not been activated for the current semester. Resetting a password or accepting this policy does not activate an account or grant additional permissions.
            </p>
            <p>
                Use only the account issued to you. Keep your password and signed-in session private, and do not allow another person to use them. Obtain your initial login details from your Department Dean, change your password through Profile Settings when needed, and sign out after using a shared device.
            </p>

            <h2>Authorship, Citations & Protected Materials</h2>
            <p>
                Credit the recorded authors when using a publication as an academic reference. You may use the publication details and Copy citation feature to identify and cite an entry; check the reference against the citation style required for your work. Do not claim another author's work as your own, falsify author names, or remove attribution.
            </p>
            <p>
                Access to a full document is permission to read it for authorized academic, review, or administrative work. It does not grant permission to sell, repost, upload elsewhere, distribute through messaging apps or social media, or give protected PDFs or captured pages to unauthorized persons. Do not remove PhilCST watermarks or use another account to obtain restricted material.
            </p>

            <h2>Student & Faculty Access</h2>
            <p>
                Students and Faculty may browse publications, save research to their dashboard, and view full documents when eligible. Saving a publication does not change full-document permissions. Students and Faculty do not upload research or publish journals directly through the portal; coordinate with your Department Dean about including completed work in a journal.
            </p>

            <h2>Department Dean Responsibilities</h2>
            <p>
                Deans must verify Student and Faculty account details, assign the correct department and program, and manage activation for the current semester. Provide initial login details only to the verified account holder. Do not grant access to unauthorized persons or disclose another user's credentials.
            </p>
            <p>
                For journal publication, the Dean assigns a preparation task to the department's Research Coordinator for a specified Academic Year and program. The Dean reviews the completed batch and its PDFs, then forwards it to Admin for publication. Deans must work within their assigned department and task responsibilities.
            </p>

            <h2>Research Coordinator Responsibilities</h2>
            <p>
                Coordinators prepare completed Student or Faculty research journals for the tasks assigned to them. Record accurate titles, authors, abstracts, categories, research types, and publication years, and upload only PDFs that the department is authorized to submit. Use the department, program, and Academic Year assigned by the Dean; Academic Year and publication year are separate details.
            </p>
            <p>
                Check every journal's details and completed PDF before submitting the whole batch to the Dean. Details may be edited during preparation; submitted batches are locked for review. Coordinators must not bypass the Dean's forwarding step or publish journals using another role's account.
            </p>

            <h2>Admin Publication Responsibilities</h2>
            <p>
                Admin handles repository administration and publication of completed journal batches forwarded by the Dean. Each journal must have complete details and an available PDF before the batch can be published. Journals are published together as a batch, and each journal appears as a separate repository entry. Handle records and account information only for authorized institutional work.
            </p>

            <h2>Protected Viewer and Security Controls</h2>
            <p>
                Full documents open through the protected viewer with PhilCST watermarks and controls that restrict printing and copying. The repository records account activity and document-access events for authorized administrative review. Do not bypass access checks or viewer restrictions, extract protected files, alter records to conceal activity, or use another person's session.
            </p>

            <h2>Access Concerns & Violations</h2>
            <p>
                Report incorrect account details, access problems, or publication concerns to your Department Dean or the repository administrator. Misuse may result in account deactivation, loss of repository access, administrative review, or referral to the appropriate school office. Account activation and journal publication remain subject to the responsible staff's review.
            </p>
        </article>

        <aside class="policy-action-card">
            <h2>Agreement</h2>
            <p>
                Signed-in users must accept the current policy before continuing to account features. Acceptance does not change your role, semester activation, or document permissions.
            </p>

            @auth
                @if($hasAcceptedCurrentPolicy)
                    <div class="policy-accepted-box">
                        <strong>Policy accepted</strong>
                        <span>You accepted version {{ $user->policy_version }} on {{ $user->policy_accepted_at->timezone('Asia/Manila')->format('F j, Y h:i A') }} (Philippine time).</span>
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
                                I have read and agree to follow the current Repository Use Policy, use only my assigned permissions, credit authors, and keep my account and protected materials private.
                            </span>
                        </label>
                        <button type="submit" class="policy-btn">Accept and Continue</button>
                    </form>
                @endif
            @else
                <div class="policy-accepted-box">
                    <strong>Please sign in</strong>
                    <span>Visitors may browse published entries. Sign in with your issued account to accept the policy and use eligible account features.</span>
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
.policy-card p + p{margin-top:12px;}
.policy-action-card{position:sticky;top:18px;padding:24px;}
.policy-form{display:grid;gap:18px;margin-top:18px;}
.policy-check{display:flex;align-items:flex-start;gap:10px;padding:14px;border-radius:14px;background:#faf7ff;border:1px solid #e9ddf8;color:#38215b;font-size:13px;line-height:1.65;cursor:pointer;}
.policy-check input[type="checkbox"]{width:22px;height:22px;min-width:22px;flex:0 0 22px;margin:2px 0 0;accent-color:#7c3aed;cursor:pointer;}
.policy-check span{flex:1;min-width:0;}
.policy-btn{display:inline-flex;width:100%;align-items:center;justify-content:center;margin-top:18px;padding:12px 16px;border:0;border-radius:999px;background:#3b0f7a;color:#fff;text-decoration:none;font-weight:800;font-size:14px;cursor:pointer;box-shadow:0 10px 24px rgba(59,15,122,.18);}
.policy-btn:hover{background:#2d0a5e;}
.policy-error{margin-top:16px;padding:12px 14px;border-radius:12px;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:13px;font-weight:700;}
.policy-accepted-box{display:grid;gap:6px;margin-top:18px;padding:14px;border-radius:14px;background:#ecfdf5;border:1px solid #bbf7d0;color:#166534;}
.policy-accepted-box strong{font-size:14px;}
.policy-accepted-box span{font-size:12.5px;line-height:1.55;color:#166534;}
@media(max-width:860px){.policy-layout{grid-template-columns:1fr;}.policy-action-card{position:static;}.policy-hero{padding:26px;}.policy-hero h1{font-size:30px;}}
</style>
@endsection
