@extends('layouts.app')
@section('title', 'FAQ - Ube Repository')

@section('content')

<div class="info-page faq-page">
    <div class="hero-banner">
        <div class="hero-bg-mesh"></div>
        <div class="hero-inner">
            <span class="hero-kicker">Repository Help</span>
            <h1 class="hero-title">Frequently Asked Questions</h1>
            <p class="hero-sub">Answers about Dean-managed accounts, semester access, journal preparation and publication, and finding research in the repository.</p>
        </div>
    </div>

    <main class="info-wrap faq-body" aria-label="Frequently asked questions">
        <aside class="faq-guide info-card">
            <div>
                <span class="info-kicker">At a Glance</span>
                <h2>Find the right answer faster.</h2>
                <p>Start with accounts if you need help signing in, publication if you are preparing journals, or viewing if you want to read and save research.</p>
            </div>
            <div class="faq-guide-list" aria-label="FAQ topics">
                <div class="faq-guide-row"><span>01</span>Accounts, semester access, and passwords</div>
                <div class="faq-guide-row"><span>02</span>Journal tasks, batches, and publication</div>
                <div class="faq-guide-row"><span>03</span>Browsing, protected viewing, and references</div>
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
                    <h2 class="faq-cat-title" id="faq-account-title">Accounts & Sign In</h2>
                    <p class="faq-cat-sub">Account creation, user roles, and semester activation.</p>
                </div>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        How do I get a Student or Faculty account?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Contact your <strong>Department Dean</strong>. The Dean creates or imports Student and Faculty accounts under the department and assigns the correct program. There is no public self-registration form. Your account must be activated for the current semester before you can sign in and access full documents.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What should I use to sign in?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Select <strong>Login</strong> and enter your Student ID, Faculty/Employee ID, or the email recorded on your account in the Username field, together with your password. Obtain your initial login details from your Department Dean. You can change your password through Profile Settings after signing in.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What can Students, Faculty, Deans, Coordinators, and Admin do?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p><strong>Students and Faculty</strong> browse publications, save research, and read full documents when their account and semester access are active. They do not upload research directly.</p>
                        <p><strong>Department Deans</strong> manage Student and Faculty accounts, assign journal preparation tasks, review completed batches, and forward them to Admin.</p>
                        <p><strong>Research Coordinators</strong> prepare and upload completed journals for the tasks assigned to them, then submit the batch to the Dean.</p>
                        <p><strong>Admin</strong> manages repository administration and publishes the completed journal batches forwarded by Deans.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Why is my account inactive or not activated for the current semester?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>An account can be created before an active semester is available. Access also depends on enrollment in a semester that is still open. Contact your <strong>Department Dean</strong> to check your account status and activate your enrollment for the current semester. A password reset does not reactivate an account.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What if I forget my password?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Open <strong>Login</strong>, select <strong>Forgot Password?</strong> in the same modal, and enter the email recorded on your account. Follow the reset link sent to that email. If the recorded email is unavailable to you or you cannot receive the link, contact your Department Dean for help with your account details.</p>
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
                    <h2 class="faq-cat-title" id="faq-submit-title">Journals & Publication</h2>
                    <p class="faq-cat-sub">Assigned tasks, completed PDFs, and batch publication.</p>
                </div>
            </div>
            <div class="faq-list">
                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Can Students and Faculty upload research directly?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>No. Coordinate with your <strong>Department Dean</strong> about including your completed research in a journal. The Dean assigns journal preparation to the Research Coordinator. The Coordinator prepares the finished journals and uploads their details and PDFs through the assigned task.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        How does a journal batch reach the repository?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>The <strong>Dean</strong> assigns a journal task for an Academic Year and program. The <strong>Research Coordinator</strong> adds the completed journals and submits the whole batch to the Dean. The Dean reviews the PDFs and forwards the batch to <strong>Admin</strong>, who publishes all journals together. Each journal then appears as a separate published entry.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        What details and files are needed before submitting a batch?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>The batch must contain at least one journal. Every journal needs its title, authors, abstract, category, research type, publication year, and a completed <strong>PDF</strong>. The upload limit is <strong>30 MB per PDF</strong>. The assigned department, program, and Academic Year carry over from the task. Coordinators can save details while preparing, but all required information and PDFs must be complete before submission.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Where can staff track preparation and publication?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Coordinators use <strong>Assigned Journal Tasks</strong> for pending and ongoing preparation. After submission, the batch appears in <strong>Journal Batches</strong> for the assigned staff. Deans forward completed batches, and Admin uses <strong>Journal Batches for Publication</strong> to publish them. Completion time depends on the responsible staff; the portal does not promise a fixed approval period.</p>
                        <p>Journal details can be edited during preparation. Submitted batches are locked for review, so check the details and PDFs before submitting. Students and Faculty should contact their Dean for publication updates.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Is Academic Year the same as publication year or semester access?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>No. <strong>Academic Year</strong> identifies the assigned journal batch, while <strong>publication year</strong> is recorded on each journal. Student and Faculty access is managed through current semester enrollment. Staff can continue preparing and processing Academic Year journal batches after a semester closes.</p>
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
                        <p>No. Visitors can browse <strong>published</strong> titles, authors, abstracts, and publication details. Use the Home keyword search, department and publication year filters, or college and program links to find entries. Full document viewing and saving research require an eligible signed-in account.</p>
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
                        <p>Students and Faculty need an <strong>active, approved account with active enrollment in an open semester</strong>. Authorized staff use their assigned account permissions. Accept the current <a href="{{ route('policy.show') }}">Repository Use Policy</a>, open a published entry, and select <strong>View Full Document</strong>. If access is restricted, ask your Department Dean to check your account and semester enrollment.</p>
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
                        <p>Signed-in Student and Faculty users can select <strong>Save</strong> on a publication to bookmark it. Open <strong>Dashboard &gt; Saved Research</strong> to return to your saved papers. Select the saved button again to remove an entry from the list. Saving a paper does not change your full-document permissions.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        How do I copy a research citation?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>Open the publication's details page and select <strong>Copy citation</strong>. The reference includes the recorded authors, publication year, title, and repository link. Check the formatting against the citation style required for your work.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" onclick="toggleFaq(this)" aria-expanded="false">
                        Why do full documents open in a protected viewer?
                        <span class="faq-arrow" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6,9 12,15 18,9"/></svg>
                        </span>
                    </button>
                    <div class="faq-answer" hidden>
                        <p>The viewer applies account access controls and PhilCST watermarks, and restricts printing and copying through its controls. Use full documents for academic reference in accordance with the <a href="{{ route('policy.show') }}">Repository Use Policy</a>, and credit the authors when referring to their work.</p>
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
