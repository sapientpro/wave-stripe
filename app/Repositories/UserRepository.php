<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Stripe\Customer;
use TCG\Voyager\Models\Role;
use Wave\User;

class UserRepository
{
    public function getByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function updateStripeId(User $user, string $stripeId): void
    {
        $user->stripe_id = $stripeId;
        $user->save();
        $user->syncStripeCustomerDetails();
    }

    public function getUniqueUsernameFromEmail(string $email): string
    {
        $username = Str::before($email, '@');
        $usernameOriginal = $username;
        $similarUsernames = User::query()->where('username', 'like', $username.'%')->pluck('username');
        $counter = 1;

        while ($similarUsernames->contains($username)) {
            $username = $usernameOriginal . $counter;
            $counter += 1;
        }

        return $username;
    }

    public function getByUsername(string $username): ?User
    {
        return User::where('username', '=', $username)->first();
    }

    public function createFromStripeCustomer(Customer $customer): User
    {
        $role = Role::where('name', '=', config('voyager.user.default_role'))->first();

        $verification_code = NULL;
        $verified = 1;

        if (setting('auth.verify_email', false)) {
            $verification_code = Str::random(30);
            $verified = 0;
        }

        $username = $this->getUniqueUsernameFromEmail($customer->email);

        $trial_days = setting('billing.trial_days', 0);
        $trial_ends_at = null;
        // if trial days is not zero we will set trial_ends_at to ending date
        if (intval($trial_days) > 0) {
            $trial_ends_at = now()->addDays(setting('billing.trial_days', 0));
        }

        $newPassword = Str::random(16);

        return User::create([
            'name' => $customer->name,
            'email' => $customer->email,
            'stripe_id' => $customer->id,
            'username' => $username,
            'password' => Hash::make($newPassword),
            'role_id' => $role->id,
            'verification_code' => $verification_code,
            'verified' => $verified,
            'trial_ends_at' => $trial_ends_at
        ]);
    }
}
