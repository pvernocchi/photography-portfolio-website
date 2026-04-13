<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class AdminController extends Controller
{
    public function index(): void
    {
        $userId = Auth::currentUserId();
        if ($userId === null) {
            $this->redirect('/admin/login');
        }

        $user = User::findById($userId);
        if ($user === null) {
            $this->redirect('/admin/login');
        }

        $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'success' => Session::flash('success'),
        ]);
    }
}
