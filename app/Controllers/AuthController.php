<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Controller;
use App\Core\Session;
use App\Core\TOTP;
use App\Core\WebAuthn;
use App\Models\User;
use App\Models\WebAuthnCredential;

class AuthController extends Controller
{
    // ── Login / logout ────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (Auth::isMfaVerified()) {
            $this->redirect('/admin/dashboard');
        }

        $this->render('auth/login', [
            'title' => 'Admin Login',
            'error' => Session::flash('error'),
        ]);
    }

    public function login(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/login');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $remember = isset($_POST['remember']);

        $user = User::findByUsername($username);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Session::flash('error', 'Invalid username or password.');
            $this->redirect('/admin/login');
        }

        Session::regenerate();
        Session::put('pending_mfa_user_id', (int) $user['id']);
        Session::put('pending_remember', $remember);
        Session::forget('user_id');
        Session::put('authenticated', false);
        Session::put('mfa_verified', false);

        if (!empty($user['mfa_enabled'])) {
            $this->redirect('/admin/mfa/verify');
        }

        $this->redirect('/admin/mfa/setup');
    }

    // ── TOTP setup ────────────────────────────────────────────────────────────

    public function showMfaSetup(): void
    {
        $user = $this->pendingUserOrRedirect();

        if (!empty($user['mfa_enabled'])) {
            $this->redirect('/admin/mfa/verify');
        }

        $secret = (string) Session::get('pending_totp_secret', '');
        if ($secret === '') {
            $secret = TOTP::generateSecret();
            Session::put('pending_totp_secret', $secret);
        }

        $issuer = (string) app_config('totp.issuer', 'Vernocchi Photography');
        $uri = TOTP::getProvisioningUri($secret, (string) $user['username'], $issuer);

        $this->render('auth/mfa_setup', [
            'title' => 'MFA Setup',
            'error' => Session::flash('error'),
            'secret' => $secret,
            'qrUrl' => TOTP::getQRCodeUrl($uri),
        ]);
    }

    public function setupMfa(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/mfa/setup');
        }

        $user = $this->pendingUserOrRedirect();
        $secret = (string) Session::get('pending_totp_secret', '');
        if ($secret === '') {
            Session::flash('error', 'MFA setup session expired. Please log in again.');
            $this->redirect('/admin/login');
        }

        $code = (string) ($_POST['code'] ?? '');
        if (!TOTP::verifyCode($secret, $code)) {
            Session::flash('error', 'Invalid authentication code.');
            $this->redirect('/admin/mfa/setup');
        }

        User::enableMfa((int) $user['id'], $secret);
        $remember = (bool) Session::get('pending_remember', false);
        Auth::completeLogin((int) $user['id'], $remember);
        Session::forget('pending_totp_secret');
        Session::forget('pending_remember');
        Session::flash('success', 'MFA has been enabled successfully.');

        $this->redirect('/admin/dashboard');
    }

    // ── MFA verify (TOTP + WebAuthn tabs) ────────────────────────────────────

    public function showMfaVerify(): void
    {
        $user = $this->pendingUserOrRedirect();

        if (empty($user['mfa_enabled'])) {
            $this->redirect('/admin/mfa/setup');
        }

        $this->render('auth/mfa_verify', [
            'title'            => 'MFA Verification',
            'error'            => Session::flash('error'),
            'webauthn_enabled' => !empty($user['webauthn_enabled']),
        ]);
    }

    public function verifyMfa(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/admin/mfa/verify');
        }

        $user = $this->pendingUserOrRedirect();
        if (empty($user['mfa_enabled']) || empty($user['totp_secret'])) {
            $this->redirect('/admin/mfa/setup');
        }

        $code = (string) ($_POST['code'] ?? '');
        if (!TOTP::verifyCode((string) $user['totp_secret'], $code)) {
            Session::flash('error', 'Invalid authentication code.');
            $this->redirect('/admin/mfa/verify');
        }

        $remember = (bool) Session::get('pending_remember', false);
        Auth::completeLogin((int) $user['id'], $remember);
        Session::forget('pending_remember');

        $this->redirect('/admin/dashboard');
    }

    // ── WebAuthn: assertion at login ──────────────────────────────────────────

    /**
     * POST /admin/mfa/webauthn/assert/options  (JSON, AJAX)
     * Returns a PublicKeyCredentialRequestOptions object for the browser.
     * Accessible during the pending-MFA phase (before full authentication).
     */
    public function webAuthnAssertOptions(): void
    {
        $input  = $this->jsonInput();
        if (!CSRF::check((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonError('Invalid security token.', 403);
        }

        $userId = Auth::pendingMfaUserId();
        if ($userId === null) {
            $this->jsonError('Unauthorized.', 401);
        }

        $user = User::findById($userId);
        if ($user === null || empty($user['webauthn_enabled'])) {
            $this->jsonError('WebAuthn not enabled for this account.', 403);
        }

        $credentials = WebAuthnCredential::findByUserId($userId);
        if ($credentials === []) {
            $this->jsonError('No security keys registered.', 400);
        }

        [$rpId] = $this->rpIdAndOrigin();
        $options = WebAuthn::generateAssertionOptions($rpId, $credentials);

        Session::put('webauthn_assert_challenge', $options['challenge']);

        $this->json(['ok' => true, 'options' => $options]);
    }

    /**
     * POST /admin/mfa/webauthn/assert  (JSON, AJAX)
     * Verifies the authenticator assertion and completes login.
     */
    public function webAuthnAssert(): void
    {
        $input = $this->jsonInput();
        // Use check() (no token rotation) so a failed assertion allows retrying
        // without the CSRF token becoming stale. SameSite=Strict cookies provide
        // the underlying CSRF protection for these JSON endpoints.
        if (!CSRF::check((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonError('Invalid security token.', 403);
        }

        $userId = Auth::pendingMfaUserId();
        if ($userId === null) {
            $this->jsonError('Unauthorized.', 401);
        }

        $user = User::findById($userId);
        if ($user === null || empty($user['webauthn_enabled'])) {
            $this->jsonError('WebAuthn not enabled for this account.', 403);
        }

        $challenge = (string) Session::get('webauthn_assert_challenge', '');
        Session::forget('webauthn_assert_challenge');
        if ($challenge === '') {
            $this->jsonError('Challenge expired. Please try again.', 400);
        }

        $credentialId = (string) ($input['id'] ?? '');
        $credential   = WebAuthnCredential::findByCredentialId($credentialId);

        if ($credential === null || (int) $credential['user_id'] !== $userId) {
            $this->jsonError('Unrecognised security key.', 400);
        }

        [$rpId, $origin] = $this->rpIdAndOrigin();

        try {
            $newSignCount = WebAuthn::verifyAssertionResponse(
                $input,
                $challenge,
                $rpId,
                $origin,
                (string) $credential['public_key_pem'],
                (int)    $credential['sign_count']
            );
        } catch (\RuntimeException $e) {
            $this->jsonError('Security key verification failed: ' . $e->getMessage(), 400);
        }

        WebAuthnCredential::updateSignCount((int) $credential['id'], $newSignCount);

        $remember = (bool) Session::get('pending_remember', false);
        Auth::completeLogin($userId, $remember);
        Session::forget('pending_remember');

        $this->json(['ok' => true, 'redirect' => '/admin/dashboard']);
    }

    // ── WebAuthn: registration (post-login admin settings) ───────────────────

    /**
     * GET /admin/mfa/webauthn/setup
     * Page for registering a new security key. Requires full authentication.
     */
    public function showWebAuthnSetup(): void
    {
        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->redirect('/admin/login');
        }

        $user = User::findById($userId);
        if ($user === null) {
            $this->redirect('/admin/login');
        }

        $credentials = WebAuthnCredential::findByUserId($userId);

        $this->render('auth/webauthn_setup', [
            'title'       => 'Security Keys',
            'success'     => Session::flash('success'),
            'error'       => Session::flash('error'),
            'credentials' => $credentials,
        ]);
    }

    /**
     * POST /admin/mfa/webauthn/register/options  (JSON, AJAX)
     * Returns a PublicKeyCredentialCreationOptions object for the browser.
     */
    public function webAuthnRegisterOptions(): void
    {
        $input = $this->jsonInput();
        if (!CSRF::check((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonError('Invalid security token.', 403);
        }

        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->jsonError('Unauthorized.', 401);
        }

        $user = User::findById($userId);
        if ($user === null) {
            $this->jsonError('User not found.', 404);
        }

        [$rpId, , $rpName] = $this->rpIdAndOrigin();
        $options = WebAuthn::generateRegistrationOptions(
            $rpId,
            $rpName,
            (string) $userId,
            (string) $user['username']
        );

        Session::put('webauthn_register_challenge', $options['challenge']);

        $this->json(['ok' => true, 'options' => $options]);
    }

    /**
     * POST /admin/mfa/webauthn/register  (JSON, AJAX)
     * Verifies the attestation response and stores the new credential.
     */
    public function webAuthnRegister(): void
    {
        $input = $this->jsonInput();
        // Use check() (no token rotation) so a failed registration allows retrying
        // without the CSRF token becoming stale. SameSite=Strict cookies + the
        // session-bound challenge provide the underlying CSRF/replay protection.
        if (!CSRF::check((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonError('Invalid security token.', 403);
        }

        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->jsonError('Unauthorized.', 401);
        }

        $challenge = (string) Session::get('webauthn_register_challenge', '');
        Session::forget('webauthn_register_challenge');
        if ($challenge === '') {
            $this->jsonError('Registration challenge expired. Please try again.', 400);
        }

        [$rpId, $origin] = $this->rpIdAndOrigin();

        try {
            $credential = WebAuthn::verifyRegistrationResponse($input, $challenge, $rpId, $origin);
        } catch (\RuntimeException $e) {
            $this->jsonError('Security key registration failed: ' . $e->getMessage(), 400);
        }

        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            $name = 'Security Key';
        }
        if (strlen($name) > 255) {
            $name = substr($name, 0, 255);
        }

        WebAuthnCredential::create(
            $userId,
            $credential['credential_id'],
            $credential['public_key_pem'],
            $name
        );

        User::enableWebAuthn($userId);

        $this->json(['ok' => true]);
    }

    /**
     * POST /admin/mfa/webauthn/{id}/delete  (JSON, AJAX)
     * Removes a registered security key for the authenticated user.
     */
    public function webAuthnDeleteKey(int $id): void
    {
        $input = $this->jsonInput();
        if (!CSRF::validate((string) ($input['csrf_token'] ?? ''))) {
            $this->jsonError('Invalid security token.', 403);
        }

        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->jsonError('Unauthorized.', 401);
        }

        $deleted = WebAuthnCredential::delete($id, $userId);
        if (!$deleted) {
            $this->jsonError('Key not found.', 404);
        }

        // If the user has no more keys, clear the webauthn_enabled flag.
        $remaining = WebAuthnCredential::findByUserId($userId);
        if ($remaining === []) {
            User::disableWebAuthn($userId);
        }

        $this->json(['ok' => true]);
    }

    // ── Password change ───────────────────────────────────────────────────────

    public function showChangePassword(): void
    {
        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->redirect('/admin/login');
        }

        $this->render('admin/settings/password', [
            'title' => 'Change Password',
            'success' => Session::flash('success'),
            'error' => Session::flash('error'),
        ]);
    }

    public function changePassword(): void
    {
        if (!CSRF::validate($_POST['csrf_token'] ?? null)) {
            Session::flash('error', 'Invalid security token.');
            $this->redirect('/admin/settings/password');
        }

        $userId = Auth::currentUserId();
        if ($userId === null || !Auth::isMfaVerified()) {
            $this->redirect('/admin/login');
        }

        $user = User::findById($userId);
        if ($user === null) {
            $this->redirect('/admin/login');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['new_password_confirmation'] ?? '');

        if (!password_verify($currentPassword, (string) $user['password_hash'])) {
            Session::flash('error', 'Current password is incorrect.');
            $this->redirect('/admin/settings/password');
        }

        if (strlen($newPassword) < 12 || !hash_equals($newPassword, $confirmPassword)) {
            Session::flash('error', 'New password must be at least 12 characters and match confirmation.');
            $this->redirect('/admin/settings/password');
        }

        User::updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT));
        Session::flash('success', 'Password updated successfully.');
        $this->redirect('/admin/settings/password');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function pendingUserOrRedirect(): array
    {
        $userId = Auth::pendingMfaUserId();
        if ($userId === null) {
            $this->redirect('/admin/login');
        }

        $user = User::findById($userId);
        if ($user === null) {
            $this->redirect('/admin/login');
        }

        return $user;
    }

    /**
     * Derive the WebAuthn RP ID, origin, and RP name from the app URL config.
     *
     * @return array{0: string, 1: string, 2: string}  [$rpId, $origin, $rpName]
     */
    private function rpIdAndOrigin(): array
    {
        $appUrl  = rtrim((string) app_config('app.url', ''), '/');
        $rpName  = (string) app_config('app.name', 'Vernocchi Photography');
        $parsed  = parse_url($appUrl);
        $rpId    = (string) ($parsed['host'] ?? $appUrl);
        $origin  = ($parsed['scheme'] ?? 'https') . '://' . $rpId;

        if (isset($parsed['port'])) {
            $origin .= ':' . $parsed['port'];
        }

        return [$rpId, $origin, $rpName];
    }
}
