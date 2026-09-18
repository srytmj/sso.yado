<?php

namespace App\Actions\Account;

use App\Models\User;
use App\Services\Dashboard\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateAvatarAction
{
    public function __construct(private readonly SettingsService $settings) {}

    public function execute(User $user, UploadedFile $file): void
    {
        $disk = $this->settings->avatarDisk() === 's3' ? 's3' : 'public';
        $path = 'avatars/' . $user->id . '/' . Str::random(20) . '.' . $file->extension();

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()), 'public');

        if ($user->avatar_disk && $user->avatar_path) {
            Storage::disk($user->avatar_disk)->delete($user->avatar_path);
        }

        $user->update([
            'avatar_disk' => $disk,
            'avatar_path' => $path,
        ]);
    }
}
