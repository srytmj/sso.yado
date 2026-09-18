<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuditLogService $auditLog,
    ) {}

    public function show(Request $request): InertiaResponse
    {
        /** @var User $user */
        $user = $request->user();

        $pendingSecret = $request->session()->get('pending_2fa_secret');

        return Inertia::render('Account/TwoFactor', [
            'user' => $user,
            'qrCodeSvg' => $pendingSecret ? $this->twoFactor->qrCodeSvg($user, $pendingSecret) : null,
            'manualKey' => $pendingSecret,
            'recoveryCodes' => $request->session()->get('new_recovery_codes'),
        ]);
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => __('two_factor.wrong_password')]);
        }

        $request->session()->put('pending_2fa_secret', $this->twoFactor->generateSecret());

        return redirect()->route('account.two-factor.show');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $secret = $request->session()->get('pending_2fa_secret');

        if (! $secret || ! $this->twoFactor->verify($secret, $request->input('code'))) {
            return back()->withErrors(['code' => __('two_factor.invalid_code')]);
        }

        /** @var User $user */
        $user = $request->user();
        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($codes),
            'two_factor_confirmed_at' => now(),
        ]);

        $request->session()->forget('pending_2fa_secret');
        $request->session()->flash('new_recovery_codes', $codes);
        $this->auditLog->record('account.two_factor_enabled', 'Two-factor authentication diaktifkan', $user);

        return redirect()->route('account.two-factor.show')->with('success', __('two_factor.enabled'));
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['current_password' => ['required', 'string']]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => __('two_factor.wrong_password')]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        $this->auditLog->record('account.two_factor_disabled', 'Two-factor authentication dinonaktifkan', $user);

        return redirect()->route('account.two-factor.show')->with('success', __('two_factor.disabled'));
    }
}
