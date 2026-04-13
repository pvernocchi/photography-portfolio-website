<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Controller;
use App\Core\Session;
use App\Core\TOTP;
use App\Models\User;

class AuthController extends Controller
{
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

    public function showMfaVerify(): void
    {
        $user = $this->pendingUserOrRedirect();

        if (empty($user['mfa_enabled'])) {
            $this->redirect('/admin/mfa/setup');
        }

        $this->render('auth/mfa_verify', [
            'title' => 'MFA Verification',
            'error' => Session::flash('error'),
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

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }

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
}
