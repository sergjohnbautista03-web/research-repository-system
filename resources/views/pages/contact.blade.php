@extends('layouts.app')
@section('title', 'Contact - Ube Repository')

@section('content')

<div class="info-page contact-page">
    <div class="hero-banner">
        <div class="hero-bg-mesh"></div>
        <div class="hero-inner">
            <span class="hero-kicker">Contact PhilCST</span>
            <h1 class="hero-title">Contact Ube Repository</h1>
            <p class="hero-sub">Reach the PhilCST repository team for access questions, submission concerns, and research portal support.</p>
        </div>
    </div>

    <main class="info-wrap ct-body">
        <div class="ct-grid">
            <section class="ct-info" aria-labelledby="contact-info-title">
                <div class="ct-lede info-card">
                    <span class="info-kicker">Contact Information</span>
                    <h2 id="contact-info-title">Get In Touch</h2>
                    <p>Questions, concerns, and feedback about the Ube Repository can be directed through the official PhilCST channels below.</p>
                </div>

                <div class="ct-cards">
                    <div class="contact-card info-card">
                        <div class="ct-card-icon-wrap ct-icon-loc" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Address</strong>
                            <p>Philippine College of Science and Technology<br>Calasiao, Pangasinan, Philippines 2418</p>
                        </div>
                    </div>

                    <div class="contact-card info-card">
                        <div class="ct-card-icon-wrap ct-icon-mail" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Email</strong>
                            <p><a href="mailto:philcstreg@yahoo.com">philcstreg@yahoo.com</a></p>
                        </div>
                    </div>

                    <div class="contact-card info-card">
                        <div class="ct-card-icon-wrap ct-icon-phone" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.23h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Phone</strong>
                            <p>(075) 522-8032<br>Monday-Friday, 8AM-5PM</p>
                        </div>
                    </div>

                    <div class="contact-card info-card">
                        <div class="ct-card-icon-wrap ct-icon-web" aria-hidden="true">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="2" y1="12" x2="22" y2="12"/>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                        </div>
                        <div>
                            <strong>Website</strong>
                            <p><a href="https://www.philcst.edu.ph" target="_blank" rel="noopener">www.philcst.edu.ph</a></p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="ct-map-panel info-card" aria-labelledby="contact-location-title">
                <span class="info-kicker">Location</span>
                <h2 id="contact-location-title">Our Location</h2>
                <p>Visit the Philippine College of Science and Technology in Calasiao, Pangasinan.</p>
                <div class="map-wrap">
                    <iframe
                        src="https://www.google.com/maps?q=Philippine+College+of+Science+and+Technology+Calasiao+Pangasinan&output=embed"
                        width="100%" height="100%"
                        style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <a href="https://maps.google.com/?q=Philippine+College+of+Science+and+Technology+Calasiao+Pangasinan"
                   target="_blank" rel="noopener" class="ct-maps-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                    Open in Google Maps
                </a>
            </section>
        </div>
    </main>
</div>

@endsection
