@extends('layouts.app')
@section('title', 'FAQ - Ube Repository')

@section('content')

<div class="info-page faq-page">
    <div class="hero-banner">
        <div class="hero-bg-mesh"></div>
        <div class="hero-inner">
            <span class="hero-kicker">Repository Help</span>
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="hero-sub">Clear answers about accounts, research submission, protected viewing, and everyday repository access.</p>
        </div>
    </div>

    <main class="info-wrap faq-body" aria-label="Frequently asked questions">
        <aside class="faq-guide info-card">
            <div>
                <span class="info-kicker">At a Glance</span>
                <h2>Find the right answer faster.</h2>
                <p>The FAQ now uses a wider landscape layout so the main topics sit side by side on desktop and tablet screens.</p>
            </div>
            <div class="faq-guide-list" aria-label="FAQ topics">
                <div class="faq-guide-row"><span>01</span>Account setup and user roles</div>
                <div class="faq-guide-row"><span>02</span>Research submission and review</div>
                <div class="faq-guide-row"><span>03</span>Protected viewing and saved papers</div>
            </div>
        </aside>

        <section class="faq-category faq-account info-card" aria-labelledby="faq-account-title">
            <div class="faq-cat-header">
                <span class="info-icon-box" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21a8 8 0 0 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                <div>
                    <h2 class="faq-cat-title" id="faq-account-title">Account & Registration</h2>
                    <p class="faq-cat-sub">Eligibility, roles, and account basics.</p>
                </div>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Who can register an account?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Members of the PhilCST community can register in the system. Approved accounts can browse approved research, view full protected documents, save papers, and submit research for review.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Is registration free?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Yes. Registration is free for qualified PhilCST users who need access to the repository.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What is the difference between a Regular User, a Researcher, and a Graduated User?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p><strong>Regular User</strong> can browse approved research, save papers, and access protected full viewing when their account or department access allows it.</p>
                        <p><strong>Researcher</strong> is an approved account that can browse, pin, and submit research for review.</p>
                        <p><strong>Graduated User</strong> keeps their submitted research and author credit in the repository, but their account is automatically deactivated after graduation.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq-category faq-submit info-card" aria-labelledby="faq-submit-title">
            <div class="faq-cat-header">
                <span class="info-icon-box" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17,8 12,3 7,8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                </span>
                <div>
                    <h2 class="faq-cat-title" id="faq-submit-title">Submitting Research</h2>
                    <p class="faq-cat-sub">Upload requirements and review flow.</p>
                </div>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        How do I submit a research paper?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>You must be using an active approved PhilCST account. Once logged in, open <strong>Submit Research</strong>, complete the required metadata such as title, abstract, department, course, and year, then upload the PDF file.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What file formats are accepted?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>The system currently accepts <strong>PDF</strong> files only, with a maximum upload size of <strong>30MB</strong>.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        How long does the approval process take?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Submissions stay in review until an authorized admin processes them. You can monitor the current status through your <strong>My Submissions</strong> page.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What happens if my submission is rejected?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>If a submission is rejected, the rejection reason is shown in your submissions record so you can review the feedback and prepare a corrected version if needed.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="faq-category faq-access info-card" aria-labelledby="faq-access-title">
            <div class="faq-cat-header">
                <span class="info-icon-box teal" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <div>
                    <h2 class="faq-cat-title" id="faq-access-title">Viewing & Access</h2>
                    <p class="faq-cat-sub">Browsing, protected files, and saved research.</p>
                </div>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Do I need an account to browse research?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>No. Public visitors can browse titles, metadata, and abstracts. Full protected document viewing depends on account role and access permissions.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Who can view full research files?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Active approved PhilCST accounts can view full protected documents directly after signing in. Public visitors can still browse titles, metadata, and abstracts.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What is the Save feature?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>The Save feature lets logged-in users bookmark research papers for quick return access through the <strong>Saved Research</strong> page.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

@push('scripts')
<script>
function toggleFaq(btn) {
    const answer = btn.nextElementSibling;
    const isOpen = btn.classList.contains('open');

    document.querySelectorAll('.faq-question.open').forEach((question) => {
        question.classList.remove('open');
        question.setAttribute('aria-expanded', 'false');
        question.nextElementSibling.hidden = true;
    });

    if (!isOpen) {
        btn.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
        answer.hidden = false;
    }
}
</script>
@endpush

@endsection
