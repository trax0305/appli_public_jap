<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\ReviewService;

final class ReviewController
{
    public function index(): void
    {
        AuthMiddleware::requireAuth();

        $userId = (int) Session::get('user_id');

        $service = new ReviewService();

        View::render('review.index', [
            'title' => 'Révision intelligente',
            'review' => $service->getReviewPageData($userId),
        ]);
    }

    public function start(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/review');
        }

        $userId = (int) Session::get('user_id');

        $service = new ReviewService();

        try {
            $sessionId = $service->startSmartReview($userId);
        } catch (\Throwable $exception) {
            Session::flash('error', 'Fais quelques quiz pour débloquer la révision intelligente.');
            redirect('/review');
        }

        redirect('/quiz/' . $sessionId);
    }
}
