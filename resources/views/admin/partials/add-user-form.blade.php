@error('semester', 'addUser')<p class="member-notice" role="alert">{{ $message }}</p>@enderror
<form id="dean-add-user-form" method="POST" action="{{ route('admin.store-user') }}">
    @csrf
    @if($activeSemester)<p>Automatically assigned to <strong>{{ $activeSemester->label }}</strong>.</p>@endif
    <div class="member-fields">
        <label><span>Member Type</span><select name="member_type" id="member-type" required><option value="student" @selected(old('member_type') === 'student')>Student</option><option value="faculty" @selected(old('member_type') === 'faculty')>Faculty</option></select><span id="member-error-member_type" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('member_type') }}</span></label>
        <label><span id="member-id-label">Student ID</span><input name="student_id" value="{{ old('student_id') }}" maxlength="50" required autocomplete="off" placeholder="e.g. 00037838"><span id="member-error-student_id" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('student_id') }}</span></label>
        @foreach(['firstname' => 'First Name', 'lastname' => 'Last Name', 'middlename' => 'Middle Name (optional)'] as $field => $label)
            <label><span>{{ $label }}</span><input name="{{ $field }}" value="{{ old($field) }}" maxlength="100" @required($field !== 'middlename') autocomplete="off"><span id="member-error-{{ $field }}" class="member-field-error" aria-live="polite">{{ $errors->addUser->first($field) }}</span></label>
        @endforeach
        <label id="member-year"><span>Year Level</span><select name="year_level" required>@foreach([1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'] as $year => $label)<option value="{{ $year }}" @selected(old('year_level') == $year)>{{ $label }}</option>@endforeach</select><span id="member-error-year_level" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('year_level') }}</span></label>
        <label class="member-span-full"><span>Email Address</span><input type="email" name="email" value="{{ old('email') }}" maxlength="255" required autocomplete="off" placeholder="name@example.com"><span id="member-error-email" class="member-field-error" aria-live="polite">{{ $errors->addUser->first('email') }}</span></label>
    </div>
    @php
        $namePrefix = ucfirst(strtolower(substr(preg_replace('/[^a-zA-Z]/', '', old('firstname', '')), 0, 3)));
        $generatedPassword = old('student_id') && $namePrefix ? old('student_id') . '_' . $namePrefix : '';
    @endphp
    <section class="member-access" aria-labelledby="member-access-title">
        <h3 id="member-access-title">Login Details</h3>
        <p>Generated from the ID and first three letters of the first name.</p>
        <div class="member-access-fields">
            <label><span>Username</span><input id="member-username" value="{{ old('student_id') }}" readonly placeholder="Enter an ID above" autocomplete="off"></label>
            <label><span>Generated Password</span><input id="member-password" value="{{ $generatedPassword }}" readonly placeholder="Enter ID and first name" autocomplete="off"></label>
        </div>
    </section>
    @if(! $activeSemester)<p class="member-notice">No active semester available. An administrator must activate a semester first.</p>@endif
    <div class="member-actions">@if($inModal ?? false)<button type="button" class="member-cancel" data-close-add-user>Cancel</button>@else<a href="{{ route('admin.users') }}">Cancel</a>@endif<button type="submit" @disabled(! $activeSemester)>Add User</button></div>
</form>
