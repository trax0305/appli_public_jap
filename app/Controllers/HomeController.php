<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\QuizGeneratorService;

final class HomeController
{
    public function index(): void
    {
        View::render('public.home', [
            'title' => 'App Japonais — Apprendre les kana simplement',
        ], 'public');
    }

    public function guestEntry(): void
    {
        View::render('public.guest-entry', [
            'title' => 'Commencer gratuitement',
        ], 'public');
    }

    public function startGuestFreePractice(): void
    {
        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/guest');
        }

        $generator = new QuizGeneratorService();

        try {
            $sessionId = $generator->startGuestFreeQuiz();
        } catch (\RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/guest');
        }

        redirect('/quiz/' . $sessionId);
    }
}
