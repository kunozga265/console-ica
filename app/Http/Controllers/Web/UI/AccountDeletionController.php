<?php

namespace App\Http\Controllers\Web\UI;

use App\Http\Controllers\Controller;
use App\Mail\ConfirmAccountDeletion;
use App\Models\User;
use App\Support\AccountDeletion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

/**
 * /deactivate-account — app-store account deletion. The person only enters
 * their email; we email a one-time signed link to that address, and the
 * account is deleted once they confirm from it. This stops anyone deleting
 * someone else's account just by knowing their email (the legacy page
 * deleted immediately). The response never reveals whether an email exists.
 */
class AccountDeletionController extends Controller
{
    private const LINK_MINUTES = 60;

    public function show()
    {
        return Inertia::render('UI/DeactivateAccount', ['stage' => 'request']);
    }

    public function request(Request $request)
    {
        $email = $request->validate(['email' => ['required', 'email', 'max:191']])['email'];
        $user = User::where('email', $email)->first();

        if ($user) {
            $url = URL::temporarySignedRoute('ui.deactivate.confirm', now()->addMinutes(self::LINK_MINUTES), [
                'user' => $user->id,
                // Ties the link to the current email, so it dies if the email changes.
                'hash' => sha1($user->email),
            ]);

            try {
                Mail::to($user->email)->send(new ConfirmAccountDeletion($user->first_name ?: 'there', $url, self::LINK_MINUTES));
            } catch (\Throwable $e) {
                Log::error('Account deletion email failed', ['user' => $user->id, 'error' => $e->getMessage()]);

                return back()->withErrors(['email' => 'We couldn’t send the confirmation email right now. Please try again later.']);
            }
        }

        return Inertia::render('UI/DeactivateAccount', ['stage' => 'sent', 'email' => $email, 'minutes' => self::LINK_MINUTES]);
    }

    /** From the emailed link: shows what will be deleted and asks for a final confirmation. */
    public function confirm(Request $request, User $user, string $hash)
    {
        abort_unless(hash_equals(sha1($user->email), $hash), 403);

        return Inertia::render('UI/DeactivateAccount', [
            'stage'     => 'confirm',
            'email'     => $user->email,
            'name'      => $user->fullName(),
            // The signed URL to POST to (signature covers the path + query).
            'deleteUrl' => $request->fullUrl(),
        ]);
    }

    public function destroy(Request $request, User $user, string $hash)
    {
        abort_unless(hash_equals(sha1($user->email), $hash), 403);

        if (Auth::id() === $user->id) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        AccountDeletion::delete($user);

        return Inertia::render('UI/DeactivateAccount', ['stage' => 'done']);
    }
}
