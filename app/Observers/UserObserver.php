<?php

namespace App\Observers;

use App\Models\{User};
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $user)
    {
        if (empty($user->username)) {
            $emailPrefix = Str::before($user->email, '@');
            $user->username = Str::slug($emailPrefix) . '-' . Str::random(5);
        }

        if (request()->hasFile('profile_image')) {
            $user->profile_image = 'storage/' . $this->storeProfileImage(request()->file('profile_image'), $user);
        }
    }
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        //
    }

    public function updating(User $user)
    {
        //
    }
    public function saving(User $user)
    {
        if ($user->isDirty('email')) {
            $emailPrefix = Str::before($user->email, '@');
            $user->username = Str::slug($emailPrefix) . '-' . Str::random(5);
        }

        if (request()->hasFile('profile_image')) {
            $user->profile_image = 'storage/' . $this->storeProfileImage(request()->file('profile_image'), $user);
        }
    }
    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }

    /**
     * Store profile image in storage and return the path.
     */
    protected function storeProfileImage($image, User $user)
    {
        $filename = Str::slug(Str::before($user->email, '@')) . '-' . now()->format('YmdHis') . '.' . $image->getClientOriginalExtension();

        return $image->storeAs('profile_images', $filename, 'public');
    }
}
