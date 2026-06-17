<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', [
            'departments' => [
                'College of Accountancy and Business Education',
                'College of Computer Studies',
                'College of Criminal Justice Education',
                'College of Education',
                'College of Engineering and Architecture',
                'College of Maritime Studies',
            ],
        ]);
    }

    public function update(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            return back()->with('error', 'Account details are managed by your dean or administrator.');
        }

        $request->validate([
            'name' => ['required', 'regex:/^[a-zA-Z\s]+$/', 'max:255'],
        ], [
            'name.regex' => 'Name must contain letters only.',
        ]);

        Auth::user()->update([
            'name' => $request->name,
        ]);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        if (! Auth::user()->isAdmin()) {
            return back()->with('error', 'Password updates are managed by your dean or administrator.');
        }

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)->numbers()->symbols()],
        ]);

        if (!Hash::check($request->current_password, Auth::user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'profile_photo.required' => 'Please select a photo.',
            'profile_photo.image'    => 'File must be an image.',
            'profile_photo.mimes'    => 'Only JPG, PNG, GIF are allowed.',
            'profile_photo.max'      => 'Photo must not exceed 2MB.',
        ]);

        $user = Auth::user();

        // Delete old photo if exists
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        // Store new photo
        $path = $request->file('profile_photo')->store('profile-photos', 'public');

        $user->update(['profile_photo' => $path]);

        return back()->with('success', 'Profile photo updated successfully!');
    }

    public function removePhoto()
    {
        $user = Auth::user();

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
            $user->update(['profile_photo' => null]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
