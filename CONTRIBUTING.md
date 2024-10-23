# Contribuer à Aides-territoires

## Installation

Une documentation détaillée de l'installation en local est disponible sur [ONBOARDING.md](./ONBOARDING.md).

```
git clone https://github.com/MTES-MCT/aides-territoires-symfony
cd aides-territoires
```

## Tests

Pour faire tourner les tests :

Charger les fixtures si besoin

```
php bin/console doctrine:fixtures:load --env=test
```

Ne pas oublier le --env=test

Puis lancer les tests avec : 

```
php bin/phpunit src/Tests/
```

## Définition du fini

Avant chaque mise en production, les intervenant·es sont prié·es de [passer
cette liste en revue](./DOD.md).

## Gestion des dépendances PHP

Pour installer les dépendances PHP du projet :

    composer install

Pour installer un nouveau paquet PHP et l'ajouter aux dépendances :

   composer require <paquet>

Pour un paquet ne servant que pour le développement, e.g *debug-toolbar* :

    composer require --dev <paquet>

## Gestion des dépendances assets (JS, CSS)

Pour installer les dépendances assets du projet :

    yarn install

Pour installer un nouveau paquet et l'ajouter aux dépendances :

   yarn add <paquet>

Pour un paquet ne servant que pour le développement, e.g *debug-toolbar* :

    yarn add --dev <paquet>

## Configuration locale, production

Les variables d'environnement sont à mettre dans un fichier .env.local, à dupliquer à partir du .env

Typiquement :

 * configuration locale spécifique à chaque intervenant·e sur le projet, e.g
   paramètres de connexion à la base de données ;
 * configuration de production.

Vous pouvez ensuite le compiler en php avec la commande

    composer dump-env local

En staging et en production, les variables d'environments sont spécifiées directement sur Scalingo.

## CSS, Sass et compression

### Maintenir le code HTML propre

Le projet utilise [Le Système de Design de l'Etat](https://github.com/GouvernementFR/dsfr) pour faciliter le développement, proposer un rendu homogène. 

Les intervenant·es sur le code sont donc *prié·es d'utiliser autant que possible les classes
spécifiques au Système de Design de l'Etat dans le HTML.

### Utilisation de webpack encore

Le projet utilise webpack encore.

Pour compiler les assets en développement

    yarn encore dev

Pour compiler les assets en production

    yarn encore production

## Utilisation de Redis

Nous utilisons Redis en production pour stocker les sessions.

Ceci permet de ne pas déconnecter les utilisateurs lors d'un deploy.

En local vous pouvez laisser cette configuration dans votre .env

    REDIS_URL=redis://localhost

## Linter de code / Code Style

Nous utilisons `squizlabs/php_codesniffer` et `phpstan/phpstan`

Exécuter les commandes suivantes pour vérifier le code :

    vendor/bin/phpcs src

    vendor/bin/phpstan analyse src


Pour vérifier son code, on peut intégrer le linter adapté à son IDE, par exemple SonarLint


## Déploiement

### Variables d'environnement

En staging et en production, les variables d'environments sont spécifiées directement sur Scalingo.

### Envoi d'email

Les emails transactionnels sont envoyés via Brevo.

### Fichiers media

Nous utilisons un service d'« Object Storage » compatible avec l'API S3 pour le stockage de tous les fichiers medias.

### Double authentification

La partie administration est protégée par une double authentification (TOTP)

Le jeton d'authentification peut-être obtenu via une application mobile comme
Google Authenticator ou Authy.

Lors de la première utilisation et avant d'activer la double authentification,
il faudra faire en sorte qu'un premier utilisateur admin puisse se connecter.

### Mise en production

Le site est actuellement hébergé sur Scalingo. Cf. la documentation d'infogérance.

Note : demander l'accès au moment de l'*onboarding*.
