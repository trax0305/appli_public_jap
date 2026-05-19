<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\ProfileService;

final class ProfileController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');

        $service = new ProfileService();
        $profile = $service->getProfileData($userId);

        if ($profile === null) {
            Session::flash('error', 'Profil introuvable.');
            redirect('/dashboard');
        }

        View::render('profile.index', [
            'title' => 'Mon profil',
            'profile' => $profile,
        ]);
    }

    public function update(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/profile');
        }

        $userId = (int) Session::get('user_id');
        $service = new ProfileService();

        $result = $service->updatePreferences($userId, $_POST);

        Session::flash($result['success'] ? 'success' : 'error', $result['message']);

        redirect('/profile');
    }
}
