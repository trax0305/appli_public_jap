# App Japonais - V1 Kana

Application web d'apprentissage des kana japonais. La V1 vise l'apprentissage des hiragana et katakana avec un parcours guide, des missions, une decouverte des kana, des quiz QCM et ecrits, un mode libre, une revision intelligente, des statistiques, des badges, un profil utilisateur et un mode invite minimum viable.

## Stack Technique

- PHP natif
- MariaDB/MySQL
- PDO
- HTML/CSS/JavaScript simple
- CSS custom
- Composer autoload
- XAMPP possible en local
- Pas de framework lourd

## Prerequis

- PHP compatible avec le projet
- MariaDB/MySQL
- Composer
- Serveur local PHP ou Apache/XAMPP
- Navigateur moderne

## Installation Locale

1. Cloner le projet.

```bash
git clone [URL_DU_DEPOT]
```

2. Se placer dans le dossier du projet.

```bash
cd appli_public_jap
```

3. Installer ou regenerer l'autoload Composer.

```bash
composer install
```

Si aucune dependance externe n'est necessaire :

```bash
composer dump-autoload
```

4. Creer et configurer le fichier `.env` a la racine du projet.

5. Importer la base de donnees depuis `database/migrations/app_jap.sql`.

6. Lancer le serveur local.

```bash
php -S localhost:8000 -t public
```

Puis ouvrir : <http://localhost:8000>

## Configuration `.env`

Ne versionnez pas le fichier `.env`. Il doit rester local a chaque environnement.

Exemple sans secret :

```env
APP_NAME="App Japonais"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=app_jap
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

## Base De Donnees

L'export SQL actuel se trouve ici :

```text
database/migrations/app_jap.sql
```

Importez ce fichier dans MariaDB/MySQL, par exemple avec phpMyAdmin ou avec un client MySQL en ligne de commande. Le README ne fournit volontairement pas de commande destructive : faites une sauvegarde avant toute operation sur une base existante.

## Lancement Local

```bash
php -S localhost:8000 -t public
```

Le dossier `public/` doit etre utilise comme document root. C'est lui qui contient le point d'entree web et les assets publics.

URL locale :

```text
http://localhost:8000
```

## Routes Principales

### Publiques

- `GET /`
- `GET /guest`
- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`

### Connectees

- `GET /dashboard`
- `GET /onboarding`
- `POST /onboarding`
- `GET /paths`
- `GET /paths/{id}`
- `GET /missions/{id}`
- `GET /missions/{id}/discovery`
- `POST /missions/{id}/discovery/complete`
- `GET /free-practice`
- `POST /free-practice/start`
- `GET /review`
- `POST /review/start`
- `GET /stats`
- `GET /profile`
- `POST /profile`
- `POST /logout`

### Quiz

- `POST /quiz/start`
- `GET /quiz/{id}`
- `POST /quiz/{id}/answer`
- `GET /quiz/{id}/feedback/{answerId}`
- `GET /quiz/{id}/results`
- `POST /quiz/{id}/retry-errors`

### Invite

- `POST /guest/free-practice/start`

## Fonctionnalites Actuelles

- Inscription, connexion et deconnexion
- Onboarding
- Parcours guide
- Missions
- Decouverte des kana
- Quiz kana vers romaji
- Quiz romaji vers kana
- Quiz ecrit
- Evaluations
- Feedback avec bouton Suivant
- Resultats enrichis
- Refaire ses erreurs
- Statistiques par kana
- Page statistiques
- Revision intelligente
- Mode libre connecte
- Deblocage du parcours suivant
- Badges
- Profil
- Navigation mobile
- Mode invite minimum viable

## Roadmap Courte

- Theme sombre reel si besoin de le finaliser
- Amelioration du mode invite avec sauvegarde apres inscription
- Progression plus fine en revision intelligente
- PWA
- Contenu vocabulaire et phrases apres les kana
- Amelioration accessibilite
- Tests automatises

## Securite

- Mots de passe geres avec `password_hash` et `password_verify`
- Requetes SQL via PDO prepare
- Protection CSRF sur les formulaires `POST`
- Echappement HTML avec `e()`
- Fichier `.env` non versionne
- `public/` utilise comme document root
- Controle d'acces aux quiz prives et aux quiz invites

## Notes Developpeur

- Faire un commit avant toute grosse modification.
- Tester les flux principaux apres chaque etape importante.
- Ne pas modifier directement une base de production sans sauvegarde.
