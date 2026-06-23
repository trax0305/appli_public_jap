<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isActive = static function (string $tab, string $path): bool {
    return match ($tab) {
        'home' => $path === '/dashboard',
        'learn' => str_starts_with($path, '/paths') || str_starts_with($path, '/missions'),
        'quiz' => str_starts_with($path, '/free-practice'),
        'stats' => str_starts_with($path, '/stats'),
        'profile' => str_starts_with($path, '/profile'),
        default => false,
    };
};
?>

<nav class="bottom-nav" aria-label="Navigation principale">
    <a class="bottom-nav-item <?= $isActive('home', $currentPath) ? 'is-active' : '' ?>" href="/dashboard">
        <span class="bottom-nav-icon" aria-hidden="true">⌂</span>
        <span>Accueil</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('learn', $currentPath) ? 'is-active' : '' ?>" href="/paths">
        <span class="bottom-nav-icon" aria-hidden="true">学</span>
        <span>Apprendre</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('quiz', $currentPath) ? 'is-active' : '' ?>" href="/free-practice">
        <span class="bottom-nav-icon" aria-hidden="true">✎</span>
        <span>Quiz</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('stats', $currentPath) ? 'is-active' : '' ?>" href="/stats">
        <span class="bottom-nav-icon" aria-hidden="true">▤</span>
        <span>Stats</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('profile', $currentPath) ? 'is-active' : '' ?>" href="/profile">
        <span class="bottom-nav-icon" aria-hidden="true">○</span>
        <span>Profil</span>
    </a>
</nav>
