# Onboarding tech

## Environnement technique

- [Docker](https://www.docker.com/) / [Compose](https://docs.docker.com/compose/)
- [PHP](https://www.php.net/)
- [Symfony](https://www.symfony.com/)
- [Twig](https://twig.symfony.com/)
- [MySQL](https://www.mysql.com/fr/)

## Montage de l'environnement de travail

Un docker est fourni dans le code source afin d'être au plus proche de la configuration Scalingo, son utilisation est optionnelle.
Ces fichiers sont supprimés lors du déploiement via des commandes le composer.json

### Pour utiliser le docker (optionnel)

Avoir "docker" et "docker compose" d'installer https://www.docker.com/get-started/

Aller dans le répertoire de votre projet et lancer l'installation :

    docker compose build

Ensuite lancer le docker :

    docker compose up -d

Le site est disponible à l'adresse :

    http://localhost:8080

Pour accéder à un des containers, par exemple le php :

    docker exec -it at_php bash

### Installer les composants

#### Composer
Commencer par installer les vendors avec Composer

    composer install

Créer une copie du fichier .env en .env.dev et mettez vos valeur dedans (base de donées, etc...)

Créer un dump du .env.dev

    composer dump-env dev

Ceci va créer un fichier .env.local.php qui sera utilisé par Symfony

*Pour les tests unitaires il suffit d'un fichier .env.test*

#### Yarn

Installer les composants nécessaires

    yarn install

Compiler les assets pour le dev

    yarn encore dev

*Pour compiler les assets en production : yarn encore production*


#### Base de données

Utilisez les commandes Symgony pour créer la base de données

### Lancement des tests

Pour lancer les tests vous pouvez utiliser la commande

    php bin/phpunit src/Tests/Controller/FrontControllerTest.php

Elle teste si les urls du site retournent un code 500 ou non