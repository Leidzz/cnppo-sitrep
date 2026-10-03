<?php

namespace App\Services;

use App\Models\User;

class NotificationService
{
    /**
     * In-system notification to one user (spec Section 15).
     * Schema per migration: user_id, type, message, is_read.
     */
    public function notifyUser(User $user, string $type, string $message): void
    {
        $user->notifications()->create([
            'type' => $type,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Notify every user holding a role (e.g. all command-center reviewers).
     */
    public function notifyRole(string $role, string $type, string $message): void
    {
        User::role($role)->get()->each(
            fn (User $user) => $this->notifyUser($user, $type, $message)
        );
    }
}
