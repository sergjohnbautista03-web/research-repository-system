<div class="su-page">
    @unless($inModal ?? false)<a class="su-back" href="{{ route('admin.users') }}">&larr; Back to Manage Users</a>@endunless
    <section class="su-intro">
        <div><h2>Continuing Students &amp; Faculty</h2><p>Select accounts from {{ auth()->user()->department }} to activate for the current semester. Their accounts, passwords, and previous semester records will be kept.</p></div>
        @if($activeSemester)<div class="su-current"><span>Current Active Semester</span><strong>{{ $activeSemester->label }}</strong></div>@endif
    </section>
    @if(session('success'))<p class="su-alert su-success" role="status">{{ session('success') }}</p>@endif
    @if($errors->any())<div class="su-alert su-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @unless($activeSemester)<p class="su-alert su-warning">No active semester available. Ask the Admin to activate a semester before activating users.</p>@endunless

    <form method="GET" class="su-filters" action="{{ route('admin.users.activate-existing') }}">
        <label>Search<input type="search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Name, ID, or email"></label>
        <label><select name="member_type" aria-label="Member Type"><option value="">Member Type</option><option value="student" @selected(request('member_type') === 'student')>Student</option><option value="faculty" @selected(request('member_type') === 'faculty')>Faculty</option></select></label>
        <label data-semester-year-filter @if(request('member_type') !== 'student') hidden @endif>
            <select name="year_level" aria-label="Year Level" @disabled(request('member_type') !== 'student')>
                <option value="">Year Level</option>
                @foreach([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $year => $label)
                    <option value="{{ $year }}" @selected(request('member_type') === 'student' && (string) request('year_level') === (string) $year)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="su-button">Search</button><a class="su-button su-secondary" href="{{ route('admin.users.activate-existing') }}">Clear</a>
    </form>

    <form method="POST" id="semester-users-form" class="su-records" action="{{ route('admin.users.activate-current') }}">
        @csrf
        <input type="hidden" name="semester_id" value="{{ $activeSemester?->id }}">
        <div class="su-toolbar"><label class="su-select-all"><input type="checkbox" id="semester-select-all" @disabled(! $activeSemester)> Select available users on this page</label><span id="semester-selection-count" role="status" aria-live="polite">0 selected</span></div>
        <div class="su-table-wrap">
            <table class="su-table">
                <thead><tr><th scope="col">Select</th><th scope="col">Name</th><th scope="col">Login ID</th><th scope="col">Member Type</th><th scope="col">Last Assigned Semester</th><th scope="col">Current Semester Status</th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $alreadyActive = $activeSemester && $user->current_semester_id === $activeSemester->id && $user->is_active
                            && $user->semesterEnrollments->contains('status', \App\Models\SemesterEnrollment::STATUS_ACTIVE);
                        $isStudent = $user->role === 'user' || $user->graduation_year !== null;
                    @endphp
                    <tr>
                        <td><input type="checkbox" name="user_ids[]" value="{{ $user->id }}" aria-label="Activate {{ $user->name }}" @disabled(! $activeSemester || $alreadyActive) @checked(! $alreadyActive && in_array($user->id, old('user_ids', [])))></td>
                        <td><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></td>
                        <td><code>{{ $user->student_id }}</code></td>
                        <td>{{ $isStudent ? 'Student' : 'Faculty' }}</td>
                        <td>{{ $user->currentSemester?->label ?? 'Not yet assigned' }}</td>
                        <td><span class="su-status {{ $alreadyActive ? 'is-active' : '' }}">{{ $alreadyActive ? 'Already Active' : 'Needs Activation' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="su-empty">No matching approved Student or Faculty accounts found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="su-footer"><p>Showing {{ $users->firstItem() ?? 0 }}â€“{{ $users->lastItem() ?? 0 }} of {{ $users->total() }} users. Activate this selection before moving to another page.</p><button type="submit" id="semester-activate-button" class="su-button" @disabled(! $activeSemester)>Activate for Current Semester</button></div>
    </form>
    @if($users->hasPages())<div class="su-pagination">{{ $users->links() }}</div>@endif
</div>

