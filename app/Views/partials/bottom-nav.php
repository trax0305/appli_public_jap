<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$isActive = static function (string $tab, string $path): bool {
    return match ($tab) {
        'home' => $path === '/dashboard',
        'paths' => str_starts_with($path, '/paths') || str_starts_with($path, '/missions'),
        'free' => str_starts_with($path, '/free-practice'),
        'stats' => str_starts_with($path, '/stats'),
        default => false,
    };
};
?>

<nav class="bottom-nav" aria-label="Navigation principale">
    <a class="bottom-nav-item <?= $isActive('home', $currentPath) ? 'is-active' : '' ?>" href="/dashboard">
        <span class="bottom-nav-icon" aria-hidden="true">⌂</span>
        <span>Accueil</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('paths', $currentPath) ? 'is-active' : '' ?>" href="/paths">
        <span class="bottom-nav-icon" aria-hidden="true">学</span>
        <span>Parcours</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('free', $currentPath) ? 'is-active' : '' ?>" href="/free-practice">
        <span class="bottom-nav-icon" aria-hidden="true">✍︎</span>
        <span>Libre</span>
    </a>

    <a class="bottom-nav-item <?= $isActive('stats', $currentPath) ? 'is-active' : '' ?>" href="/stats">
        <span class="bottom-nav-icon" aria-hidden="true">▤</span>
        <span>Stats</span>
    </a>
</nav>
