@extends('layouts.app')
@section('title', 'About - Ube Repository')

@section('content')

@php
$colleges = [
    'College of Accountancy and Business Education' => [
        'short' => 'CABE',
        'description' => 'Browse published research and journals connected to accountancy, business, hospitality, and tourism.',
        'program' => [
            'Accountancy' => 'Find studies related to accounting practices, financial reporting, auditing, and taxation.',
            'Business Administration-Marketing Mngt.' => 'Explore research on marketing, consumer behavior, business strategies, and enterprise development.',
            'Hospitality Management' => 'Browse studies on hospitality services, hotel operations, food service, and customer experience.',
            'Tourism Management' => 'Explore research on tourism services, destinations, travel operations, and sustainable tourism.',
        ],
    ],
    'College of Computer Studies' => [
        'short' => 'CCS',
        'description' => 'Browse computing research, software projects, and journals under Computer Science and Information Technology.',
        'program' => [
            'Computer Science' => 'Explore studies on algorithms, software development, artificial intelligence, and computing applications.',
            'Information Technology' => 'Find research on information systems, web applications, databases, networks, and technology services.',
        ],
    ],
    'College of Criminal Justice Education' => [
        'short' => 'CCJE',
        'description' => 'Explore published work related to criminology, public safety, and the criminal justice system.',
        'program' => [
            'Criminology' => 'Browse research on crime prevention, law enforcement, criminal behavior, and community safety.',
        ],
    ],
    'College of Education' => [
        'short' => 'COE',
        'description' => 'Find education research and journals on teaching, learning, and classroom practice.',
        'program' => [
            'Elementary Education' => 'Explore studies on elementary teaching, learner development, instructional materials, and classroom strategies.',
            'Secondary Education-General Science' => 'Browse research on science teaching, student learning, and instructional approaches in secondary education.',
        ],
    ],
    'College of Engineering and Architecture' => [
        'short' => 'CEA',
        'description' => 'Browse engineering research and technical studies across the programs listed in the repository.',
        'program' => [
            'Civil Engineering' => 'Find studies on structures, construction, materials, and infrastructure.',
            'Computer Engineering' => 'Explore research on computer hardware, embedded systems, electronics, and integrated software solutions.',
            'Electrical Engineering' => 'Browse studies on electrical systems, power, control, and energy applications.',
            'Electronics Engineering' => 'Find research on electronic systems, communications, circuits, and signal processing.',
            'Mechanical Engineering' => 'Explore studies on machines, thermal systems, manufacturing, and mechanical design.',
        ],
    ],
    'College of Maritime Studies' => [
        'short' => 'CMS',
        'description' => 'Explore published maritime research related to ship systems, navigation, and transport operations.',
        'program' => [
            'Marine Engineering' => 'Browse studies on marine machinery, ship systems, maintenance, and operational safety.',
            'Transportation' => 'Find research on navigation, maritime transport, ship operations, and logistics.',
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
            <p class="hero-sub">Discover published PhilCST research and journals, understand how publications reach the repository, and learn how account access works.</p>
        </div>
    </div>

    <main class="info-wrap ab-body">
        <section class="about-intro-grid">
            <div class="about-copy info-card">
                <span class="info-kicker">Overview</span>
                <h2>What is Ube Repository?</h2>
                <p>Ube Repository is the official digital research portal of the <strong>Philippine College of Science and Technology (PhilCST)</strong>, located in Calasiao, Pangasinan, Philippines.</p>
                <p>It brings published research, <strong>Student Research Journals</strong>, and <strong>Faculty Research Journals</strong> into a searchable collection. Visitors can browse titles, authors, abstracts, and publication details; eligible signed-in users can open full documents in the protected viewer.</p>
                <p>Department Deans manage Student and Faculty accounts and assign journal preparation tasks by Academic Year and program. Research Coordinators prepare the completed journals, submit them to the Dean, and the Dean forwards the batch to Admin for publication.</p>
                <div class="about-focus-list" aria-label="Repository focus areas">
                    <span>Research preservation</span>
                    <span>Academic Year journal batches</span>
                    <span>Semester-based member access</span>
                </div>
            </div>

            <figure class="about-campus-frame">
                <img src="{{ asset('images/philcstarea.jpg') }}" alt="Philippine College of Science and Technology campus">
                <figcaption class="about-campus-caption">PhilCST, Calasiao, Pangasinan</figcaption>
            </figure>
        </section>

        <section class="ab-mv-grid" aria-label="Repository purpose and workflow">
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
                    <h3>Repository Purpose</h3>
                    <p>Preserve PhilCST research and journals in an organized collection so readers can find published work, identify its authors, and use it as a reference for academic study.</p>
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
                    <h3>How the Repository Works</h3>
                    <p>Keep account access, journal preparation, and publication assigned to the responsible roles. Organize records by department, program, Academic Year, and publication year while guiding readers through the Repository Use Policy.</p>
                </div>
            </div>
        </section>

        <section class="info-section">
            <div class="info-section-head">
                <div>
                    <span class="info-kicker">Features</span>
                    <h2>What You Can Do</h2>
                </div>
                <p>Browse published work, save useful references, and understand the staff workflow that brings journals into the collection.</p>
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
                    <p>Find published research and journals using keywords, college links, and publication year filters.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Search titles, authors, abstracts, and keywords; use Search By to narrow the field</li>
                            <li>Filter Home results by department and a publication year range</li>
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
                    <h4>Journal Preparation & Publication</h4>
                    <p>Journals move through a Dean-assigned task, Coordinator preparation, Dean forwarding, and Admin publication.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>The Dean assigns an Academic Year, program, category, and instructions</li>
                            <li>The Coordinator adds journal details and completed PDFs to the batch</li>
                            <li>The Coordinator submits the completed batch to the Dean, who forwards it to Admin</li>
                            <li>Admin publishes the whole batch; each journal appears as a separate repository entry</li>
                            <li>Students and Faculty coordinate with their Department Dean instead of uploading directly</li>
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
                    <p>Bookmark useful publications and return to them from Saved Research in your dashboard.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Save any research paper from its details page</li>
                            <li>Open Dashboard and select Saved Research to find your saved papers</li>
                            <li>Select the saved button again to remove a paper from your list</li>
                            <li>Available to signed-in Student and Faculty accounts</li>
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
                    <p>Eligible accounts can read full PDFs through the protected viewer after accepting the Repository Use Policy.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Student and Faculty access requires an active, approved account and active semester enrollment</li>
                            <li>Authorized staff use their assigned account permissions</li>
                            <li>Full documents carry PhilCST watermarks and open with viewer controls that restrict printing and copying</li>
                            <li>Public visitors can read publication details and abstracts without signing in</li>
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
                    <p>Use college and program links to find published work connected to your academic area.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Browse the colleges and programs listed in the repository</li>
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
                    <h4>Research Details & Citations</h4>
                    <p>Read a publication's abstract, authors, keywords, and year, then copy its repository citation.</p>
                    <div class="feature-expand" hidden>
                        <ul>
                            <li>Open a published entry to view its authors and abstract</li>
                            <li>Check the department, program, category, and publication year</li>
                            <li>Academic Year is shown when recorded and is separate from publication year</li>
                            <li>Use Copy citation on the details page to copy the reference text</li>
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
                <p>These are the college and program groupings used by the repository. Open View Research to see the published entries available for a program.</p>
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
