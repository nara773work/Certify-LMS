<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Setting\AvatarRequest;
use App\Http\Requests\Setting\PasswordRequest;
use App\Http\Requests\Setting\ProfileRequest;

class SettingController extends Controller
{
    public function edit()
    {

        $user = Auth()->user();

        $this->authorize('profile.update', $user);

        return view('settings.profile', compact('user'));
    }

    public function update(ProfileRequest $request)
    {

        $user = Auth()->user();
        $this->authorize('profile.update', $user);

        $user->update([
            'name' => $request->name,
            'bio' => $request->bio,
        ]);

        return back()->with('success', 'プロフィールを更新しました');
    }

    public function avatar(AvatarRequest $request)
    {

        $user = Auth()->user();

        $this->authorize('profile.avatar', $user);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');

            $user->update([
                'avatar_url' => '/storage/'.$path,
            ]);
        }

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', 'アバター画像を登録しました');
    }

    public function avatardelete()
    {

        $user = Auth()->user();
        $this->authorize('profile.avatardelete', $user);

        $user->update([
            'avatar_url' => null,
        ]);

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', 'アバター画像を削除しました');
    }

    public function updatepassword(PasswordRequest $request)
    {

        $user = Auth()->user();
        $this->authorize('profile.passwordupdate', $user);

        $user->update([
            'password' => $request->password,
        ]);

        return redirect()
            ->route('settings.profile.edit')
            ->with('success', 'パスワードを更新しました');
    }
}
