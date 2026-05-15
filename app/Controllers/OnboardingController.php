<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\OnboardingService;

final class OnboardingController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        View::render('auth.onboarding', [
            'title' => 'Premiers réglages',
        ], 'auth');
    }

    public function save(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/onboarding');
        }

        $userId = (int) Session::get('user_id');
        $learningMode = $_POST['learning_mode'] ?? 'guided';
        $pathCode = $_POST['path_code'] ?? null;

        $service = new OnboardingService();
        $service->saveLearningMode($userId, $learningMode, $pathCode);

        redirect('/dashboard');
    }
}