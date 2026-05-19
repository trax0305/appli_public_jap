<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuthMiddleware;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\View;
use App\Services\QuizCorrectionService;
use App\Services\QuizGeneratorService;

final class QuizController
{
    public function start(): void
    {
        AuthMiddleware::requireAuth();

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/dashboard');
        }

        $objectiveId = (int) ($_POST['objective_id'] ?? 0);
        $userId = (int) Session::get('user_id');

        $generator = new QuizGeneratorService();
        $sessionId = $generator->startObjectiveQuiz($userId, $objectiveId);

        redirect('/quiz/' . $sessionId);
    }

    public function play(string $id): void
    {
        AuthMiddleware::requireAuth();

        $sessionId = (int) $id;

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionId);
        $question = $service->getCurrentQuestion($sessionId);

        if ($session === null) {
            http_response_code(404);
            echo 'Quiz introuvable';
            return;
        }

        if ($question === null) {
            redirect('/quiz/' . $sessionId . '/results');
        }

        View::render('quiz.play', [
            'title' => 'Quiz',
            'session' => $session,
            'question' => $question,
            'options' => json_decode($question['options_json'], true) ?: [],
        ]);
    }

    public function answer(string $id): void
    {
        AuthMiddleware::requireAuth();

        $sessionId = (int) $id;

        if (!Csrf::check($_POST['_csrf'] ?? null)) {
            Session::flash('error', 'Session expirée. Réessaie.');
            redirect('/quiz/' . $sessionId);
        }

        $answerId = (int) ($_POST['answer_id'] ?? 0);
        $userAnswer = (string) ($_POST['answer'] ?? '');

        $service = new QuizCorrectionService();
        $service->answerQuestion($sessionId, $answerId, $userAnswer);

        redirect('/quiz/' . $sessionId . '/feedback/' . $answerId);
    }


    public function feedback(string $sessionId, string $answerId): void
    {
        AuthMiddleware::requireAuth();

        $sessionIdInt = (int) $sessionId;
        $answerIdInt = (int) $answerId;
        $userId = (int) Session::get('user_id');

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionIdInt);

        if ($session === null || (int) ($session['user_id'] ?? 0) !== $userId) {
            redirect('/quiz/' . $sessionIdInt);
        }

        $answer = $service->getAnswerForFeedback($userId, $sessionIdInt, $answerIdInt);

        if ($answer === null) {
            redirect('/quiz/' . $sessionIdInt);
        }

        View::render('quiz.feedback', [
            'title' => 'Correction',
            'session' => $session,
            'answer' => $answer,
        ]);
    }

    public function results(string $id): void
    {
        AuthMiddleware::requireAuth();

        $sessionId = (int) $id;

        $service = new QuizCorrectionService();
        $session = $service->getSession($sessionId);

        if ($session === null) {
            http_response_code(404);
            echo 'Quiz introuvable';
            return;
        }

        $newBadges = Session::get('new_badges_quiz_' . $sessionId, []);
        Session::forget('new_badges_quiz_' . $sessionId);

        if (!is_array($newBadges)) {
            $newBadges = [];
        }

        View::render('quiz.results', [
            'title' => 'Résultat',
            'session' => $session,
            'answers' => $service->getSessionAnswers($sessionId),
            'missionId' => $service->getMissionIdFromSession($sessionId),
            'newBadges' => $newBadges,
        ]);
    }

    public function retryErrors(string $id): void
{
    AuthMiddleware::requireAuth();

    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        Session::flash('error', 'Session expirée. Réessaie.');
        redirect('/quiz/' . $id . '/results');
    }

    $sourceSessionId = (int) $id;
    $userId = (int) Session::get('user_id');

    $generator = new QuizGeneratorService();

    try {
        $newSessionId = $generator->startErrorReviewQuiz($userId, $sourceSessionId);
    } catch (\Throwable $exception) {
        Session::flash('error', 'Aucune erreur à revoir.');
        redirect('/quiz/' . $sourceSessionId . '/results');
    }

    redirect('/quiz/' . $newSessionId);
}
}