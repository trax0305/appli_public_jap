# Appli Public Jap

Application web PHP d'apprentissage des kana japonais.

## Prérequis

- PHP 8+
- Composer

## Installation

```bash
composer install
```

## Lancer en local

```bash
php -S localhost:8000 -t public
```

Puis ouvrir <http://localhost:8000>.

## Structure du projet

- `app/` : logique applicative (services, modèles, vues, coeur)
- `routes/` : définition des routes
- `public/` : point d'entrée web et assets publics
- `config/` : configuration
