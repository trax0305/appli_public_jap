<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\OnboardingController;
use App\Core\Router;
use App\Core\View;
use App\Controllers\MissionController;
use App\Controllers\PathController;
use App\Controllers\QuizController;
use App\Controllers\StatsController;

$router = new Router();

$router->get('/', [HomeController::class, 'index']);
$router->get('/guest', [HomeController::class, 'guestEntry']);

$router->get('/dashboard', [DashboardController::class, 'index']);

$router->get('/onboarding', [OnboardingController::class, 'index']);
$router->post('/onboarding', [OnboardingController::class, 'save']);

$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'handleLogin']);

$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'handleRegister']);

$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/paths', [PathController::class, 'index']);
$router->get('/paths/{id}', [PathController::class, 'show']);

$router->get('/missions/{id}', [MissionController::class, 'show']);
$router->get('/missions/{id}/discovery', [MissionController::class, 'discovery']);
$router->post('/missions/{id}/discovery/complete', [MissionController::class, 'completeDiscovery']);

$router->post('/quiz/start', [QuizController::class, 'start']);
$router->get('/quiz/{id}', [QuizController::class, 'play']);
$router->post('/quiz/{id}/answer', [QuizController::class, 'answer']);
$router->get('/quiz/{id}/results', [QuizController::class, 'results']);
$router->post('/quiz/{id}/retry-errors', [QuizController::class, 'retryErrors']);

$router->get('/free-practice', function () {
    View::render('free-practice.index', [
        'title' => 'Mode libre',
    ]);
});

$router->get('/review', function () {
    View::render('dashboard.index', [
        'title' => 'Révision',
        'dashboard' => [
            'user' => ['username' => 'Test'],
            'mode' => 'guided_without_path',
            'current_path' => null,
            'current_mission' => null,
            'progress' => null,
            'last_session' => null,
        ],
    ]);
});

$router->get('/stats', [StatsController::class, 'index']);

return $router;