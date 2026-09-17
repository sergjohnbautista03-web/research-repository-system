@extends('layouts.app')
@section('title', 'About - Ube Repository')

@section('content')

@php
$colleges = [
    'College of Accountancy and Business Education' => [
        'short' => 'CABE',
        'description' => 'Prepares students for careers in business, accounting, and hospitality through rigorous academic training and practical experience.',
        'program' => [
            'Accountancy' => 'Covers financial accounting, auditing, taxation, and management advisory services preparing students for the CPA board exam.',
            'Business Administration-Marketing Mngt.' => 'Focuses on marketing strategies, consumer behavior, brand management, and business development.',
            'Hospitality Management' => 'Trains students in hotel operations, food and beverage management, and hospitality industry practices.',
            'Tourism Management' => 'Covers travel and tour operations, tourism planning, and sustainable tourism development.',
        ],
    ],
    'College of Computer Studies' => [
        'short' => 'CCS',
        'description' => 'Produces competent IT professionals equipped with the latest knowledge in computing, software development, and information systems.',
        'program' => [
            'Computer Science' => 'Focuses on algorithms, software engineering, artificial intelligence, and advanced computing concepts.',
            'Information Technology' => 'Covers network administration, database management, web development, and IT infrastructure.',
        ],
    ],
    'College of Criminal Justice Education' => [
        'short' => 'CCJE',
        'description' => 'Trains future law enforcement officers, criminologists, and justice professionals dedicated to public safety and crime prevention.',
        'program' => [
            'Criminology' => 'Studies crime, criminal behavior, law enforcement, forensic science, and the criminal justice system.',
        ],
    ],
    'College of Education' => [
        'short' => 'COE',
        'description' => 'Develops highly competent and values-driven educators committed to delivering quality education at all levels.',
        'program' => [
            'Elementary Education' => 'Prepares teachers for grades 1-6, covering curriculum development, child psychology, and teaching strategies.',
            'Secondary Education-General Science' => 'Focuses on science education methodologies, preparing teachers for high school science subjects.',
        ],
    ],
    'College of Engineering and Architecture' => [
        'short' => 'CEA',
        'description' => 'Produces world-class engineers equipped with technical knowledge and practical skills to address real-world engineering challenges.',
        'program' => [
            'Civil Engineering' => 'Covers structural design, construction management, geotechnical engineering, and infrastructure development.',
            'Computer Engineering' => 'Combines hardware and software engineering, covering embedded systems, digital electronics, and computer architecture.',
            'Electrical Engineering' => 'Focuses on power systems, electrical machines, control systems, and renewable energy technologies.',
            'Electronics Engineering' => 'Covers analog and digital electronics, telecommunications, microprocessors, and signal processing.',
            'Mechanical Engineering' => 'Studies thermodynamics, fluid mechanics, machine design, and manufacturing processes.',
        ],
    ],
    'College of Maritime Studies' => [
        'short' => 'CMS',
        'description' => 'Trains future maritime professionals and seafarers with the technical expertise and competence required by international maritime standards.',
        'program' => [
            'Marine Engineering' => 'Covers marine diesel engines, ship systems, naval architecture, and maritime safety operations.',
            'Transportation' => 'Focuses on navigation, ship handling, maritime law, and transport logistics management.',
        ],
    ],
];
@endphp

<div class="info-page about-page">
    <div class="hero-banner">
        <div class="hero-bg-mesh"></div>
        <div class="hero-inner">
            <span class="hero-kicker">About the Repository</span>
            <h1 class="hero-title">About Ube Repository</h1>
            <p class="hero-sub">The official PhilCST research portal for preserving, reviewing, and sharing academic work across the institution.</p>
        </div>
    </div>

    <main class="info-wrap ab-body">
        <section class="about-intro-grid">
            <div class="about-copy info-card">
                <span class="info-kicker">Overview</span>
                <h2>What is Ube Repository?</h2>
                <p>Ube Repository is the official digital research portal of the <strong>Philippine College of Science and Technology (PhilCST)</strong>, located in Calasiao, Pangasinan, Philippines.</p>
                <p>It organizes approved research outputs, supports researcher submissions, and provides role-based access for students, researchers, deans, and administrators across the institution.</p>
                <div class="about-focus-list" aria-label="Repository focus areas">
                    <span>Research preservation</span>
                    <span>Structured review flow</span>
                    <span>Responsible access</span>
                </div>
            </div>

            <figure class="about-campus-frame">
                <img src="{{ asset('images/philcstarea.jpg') }}" alt="Philippine College of Science and Technology campus">
                <figcaption class="about-campus-caption">PhilCST, Calasiao, Pangasinan</figcaption>
            </figure>
        </section>

        <section class="ab-mv-grid" aria-label="Mission and vision">
            <div class="ab-mv-card info-card">
                <span class="info-icon-box teal" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <circle cx="12" cy="12" r="4"/>
                        <line x1="12" y1="2" x2="12" y2="5"/>
                        <line x1="12" y1="19" x2="12" y2="22"/>
                        <line x1="2" y1="12" x2="5" y2="12"/>
                        <line x1="19" y1="12" x2="22" y2="12"/>
                    </svg>
                </span>
                <div>
                    <h3>Mission</h3>
                    <p>To provide a reliable and structured platform for preserving, reviewing, and sharing PhilCST research outputs while supporting responsible access to academic work.</p>
                </div>
            </div>
            <div class="ab-mv-card info-card">
                <span class="info-icon-box" aria-hidden="true">
                    <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </span>
                <div>
                    <h3>Vision</h3>
                    <p>To become a trusted academic repository that helps PhilCST strengthen research culture, institutional memory, and secure access to scholarly output.</p>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section-head">
                <div>
                    <span class="info-kicker">Features</span>
                    <h2>What You Can Do</h2>
                </div>
                <p>Core repository workflows are grouped into focused tools for discovery, submission, saving, and protected viewing.</p>
            </div>

            <div class="ab-features-grid">
                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </span>
                    <h4>Search & Browse</h4>
                    <p>Explore approved research by keyword, department, program, year, or type.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Search by title, author, abstract, or keyword</li>
                            <li>Filter by department and publication year</li>
                            <li>Browse research by college and program</li>
                            <li>Open research details before entering the protected viewer</li>
                        </ul>
                    </div>
                </div>

                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17,8 12,3 7,8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                    </span>
                    <h4>Submit Research</h4>
                    <p>Approved researchers can submit research files for review and publication.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Fill in title, abstract, keywords, department, and course details</li>
                            <li>Upload a PDF copy of the research</li>
                            <li>Submitted research is reviewed before appearing on the portal</li>
                        </ul>
                    </div>
                </div>

                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box teal" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                        </svg>
                    </span>
                    <h4>Save Research</h4>
                    <p>Keep important papers in a pinned list for quick return access.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Save any research paper from its details page</li>
                            <li>Access all saved papers from the Saved Research page</li>
                            <li>Unpin anytime when no longer needed</li>
                            <li>Available to logged-in non-admin users</li>
                        </ul>
                    </div>
                </div>

                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box teal" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        </svg>
                    </span>
                    <h4>Protected Viewing</h4>
                    <p>Full documents open in a protected viewer with access controls and PhilCST overlays.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>PhilCST access is controlled through active approved user accounts</li>
                            <li>Protected viewer limits easy browser actions such as save, print, and copy where possible</li>

                            <li>Approved files remain organized inside the portal flow</li>
                        </ul>
                    </div>
                </div>

                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                    </span>
                    <h4>Browse by Department</h4>
                    <p>Research outputs are grouped by college, department, and program for easier discovery.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Browse the available colleges and academic programs of PhilCST</li>
                            <li>Filter by specific program within each college</li>
                            <li>Follow department-based organization across the repository</li>
                            <li>Quickly find studies related to your field</li>
                        </ul>
                    </div>
                </div>

                <div class="ab-feature" role="button" tabindex="0" aria-expanded="false" onclick="toggleFeature(this)" onkeydown="handleFeatureKey(event, this)">
                    <span class="feature-icon-box info-icon-box teal" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22,4 12,14.01 9,11.01"/>
                        </svg>
                    </span>
                    <h4>Quality Assured</h4>
                    <p>Submissions are screened by authorized admins before they appear in the repository.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Every submission is reviewed before publishing</li>
                            <li>Rejected submissions receive a reason for rejection</li>
                            <li>Status can be tracked through the submitter's submissions page</li>
                            <li>Helps keep repository records organized and relevant</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section-head">
                <div>
                    <span class="info-kicker">Colleges</span>
                    <h2>Colleges & Departments</h2>
                </div>
                <p>Browse academic areas represented in the repository and open research connected to each program.</p>
            </div>

            <div class="colleges-accordion">
                @foreach($colleges as $college => $data)
                    <div class="college-item info-card" id="college-{{ $loop->index }}">
                        <button class="college-header" id="college-button-{{ $loop->index }}" type="button" onclick="toggleCollege({{ $loop->index }})" aria-expanded="false" aria-controls="body-{{ $loop->index }}">
                            <span class="college-title-row">
                                <span class="ab-college-icon-wrap">{{ $data['short'] }}</span>
                                <span>
                                    <span class="college-name">{{ $college }}</span>
                                    <span class="college-count">{{ count($data['program']) }} {{ count($data['program']) == 1 ? 'program' : 'programs' }}</span>
                                </span>
                            </span>
                            <span class="college-chevron" aria-hidden="true">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <polyline points="6,9 12,15 18,9"/>
                                </svg>
                            </span>
                        </button>
                        <div class="college-body" id="body-{{ $loop->index }}" hidden>
                            <p class="ab-college-desc">{{ $data['description'] }}</p>
                            <div class="courses-grid">
                                @foreach($data['program'] as $course => $desc)
                                    <div class="course-card">
                                        <div class="course-card-head">
                                            <h4>{{ $course }}</h4>
                                            <a href="{{ route('research.course', [$college, $course]) }}" class="ab-view-link">View Research</a>
                                        </div>
                                        <p>{{ $desc }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </main>
</div>

@push('scripts')
<script>
function toggleFeature(card) {
    const expand = card.querySelector('.feature-expand');
    const isOpen = !expand.hidden;

    document.querySelectorAll('.feature-expand').forEach((panel) => {
        panel.hidden = true;
    });
    document.querySelectorAll('.ab-feature').forEach((feature) => {
        feature.classList.remove('active');
        feature.setAttribute('aria-expanded', 'false');
    });

    if (!isOpen) {
        expand.hidden = false;
        card.classList.add('active');
        card.setAttribute('aria-expanded', 'true');
    }
}

function handleFeatureKey(event, card) {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleFeature(card);
    }
}

function toggleCollege(index) {
    const button = document.getElementById('college-button-' + index);
    const body = document.getElementById('body-' + index);
    const isOpen = !body.hidden;

    body.hidden = isOpen;
    button.classList.toggle('is-open', !isOpen);
    button.setAttribute('aria-expanded', String(!isOpen));
}
</script>
@endpush

@endsection
