@error('semester', 'addUser')<p class="member-notice" role="alert">{{ $message }}</p>@enderror
<form id="dean-add-user-form" method="POST" action="{{ route('admin.store-user') }}">
    @csrf
    @if($activeSemester)<p>Automatically assigned to <strong>{{ $activeSemester->label }}</strong>.</p>@endif
    <div class="member-fields">
        <label><span>Member Type</span><select name="member_type" id="member-type" required><option value="student" @selected(old('member_type') === 'student')>Student</option><option value="faculty" @selected(old('member_type') === 'faculty')>Faculty</option></select><span id="member-error-member_type" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('member_type') }}</span></label>
        <label><span id="member-id-label">Student ID</span><input type="text" name="student_id" value="{{ old('student_id') }}" inputmode="numeric" pattern="{{ old('member_type') === 'faculty' ? '[0-9]{6}' : '[0-9]+' }}" minlength="{{ old('member_type') === 'faculty' ? 6 : 1 }}" maxlength="{{ old('member_type') === 'faculty' ? 6 : 8 }}" required autocomplete="off" placeholder="e.g. 37893"><span id="member-error-student_id" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('student_id') }}</span></label>
        @foreach(['firstname' => 'First Name', 'lastname' => 'Last Name', 'middlename' => 'Middle Name (optional)'] as $field => $label)
            <label><span>{{ $label }}</span><input name="{{ $field }}" value="{{ old($field) }}" maxlength="100" @required($field !== 'middlename') autocomplete="off"><span id="member-error-{{ $field }}" class="member-field-error" aria-live="polite">{{ $errors->addUser->first($field) }}</span></label>
        @endforeach
        <label id="member-year"><span>Year Level</span><select name="year_level" required>@foreach([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $year => $label)<option value="{{ $year }}" @selected(old('year_level') == $year)>{{ $label }}</option>@endforeach</select><span id="member-error-year_level" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('year_level') }}</span></label>
        <label><span>Program (required)</span><select name="program" required><option value="">Select Program</option>@foreach(\App\Models\Research::programsByDepartment()[auth()->user()->department] ?? [] as $program)<option value="{{ $program }}" @selected(old('program') === $program)>{{ $program }}</option>@endforeach</select><span id="member-error-program" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('program') }}</span></label>
        <label class="member-span-full"><span>Email Address</span><input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email" autocapitalize="none" spellcheck="false" placeholder="name@gmail.com"><span id="member-error-email" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('email') }}</span></label>
    </div>
    <section class="member-access" aria-labelledby="member-access-title">
        <h3 id="member-access-title">Login Details</h3>
        <p>Generated from the ID and first three letters of the first name.</p>
        <div class="member-access-fields">
            <label><span>Username</span><input id="member-username" value="" readonly placeholder="Automatically generated" autocomplete="off"></label>
            <label><span>Generated Password</span><input id="member-password" value="" readonly placeholder="Enter a first name" autocomplete="off"></label>
        </div>
    </section>
    @if(! $activeSemester)<p class="member-notice">You can add users now. Activate them once an active semester is available.</p>@endif
    <div class="member-actions">@if($inModal ?? false)<button type="button" class="member-cancel" data-close-add-user>Cancel</button>@else<a href="{{ route('admin.users') }}">Cancel</a>@endif<button type="submit">Add User</button></div>
</form>
