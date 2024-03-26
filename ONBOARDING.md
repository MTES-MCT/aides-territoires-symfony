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

### Récupérer les fichiers depuis le repository

    git clone git@github.com:MTES-MCT/aides-territoires-symfony.git
    

### Pour utiliser le docker (optionnel)

Avoir "docker" et "docker compose" d'installés https://www.docker.com/get-started/

Aller dans le répertoire de votre projet et lancer l'installation :

    docker compose build

Ensuite lancer le docker :

    docker compose up -d

Le site est disponible à l'adresse :

    http://localhost:8080

=> Pour modifier le port il faut modifier l'entrée **apache** dans le docker compose

        apache:
            ...
            - "8080:80"
            expose:
            - "8080"
            ...
        
Pour accéder à un des containers, par exemple le php (pour lancer des commandes php bin/console ....) :

    docker exec -it at_php bash

### Installer les composants

#### Composer
Commencer par installer les vendors avec Composer

    composer install

#### Base de données

Vous avez deux possibilités :

##### Importer la base de staging

- Récupérer le backup sur Scalingo
- Copier le fichier .sql dans le sous-dossier .docker/data/db
- Accéder au container mysql

        docker exec -it at_mysql bash

- Aller dans le dossier contenant le .sql

        cd var/lib/mysql

- Lancer la commande d'import

        mysql -uroot -p votrebase < fichier.sql

##### Utiliser les commandes symfony pour créer la base

- Créer la base de données

        php bin/console doctrine:database:create

- Créer les tables

        php bin/console d:s:u --complete --force

#### Fichier de configuration

Créer une copie du fichier .env en .env.dev et mettez vos valeur dedans (base de donées, etc...)

Créer un dump du .env.dev

    composer dump-env dev

Ceci va créer un fichier .env.local.php qui sera utilisé par Symfony

*Pour les tests unitaires il suffit d'un fichier .env.test, pas besoin d'écraser votre fichier .env.local.php*

#### Yarn

Installer les composants nécessaires

    yarn install

Compiler les assets pour le dev

    yarn encore dev

*Pour compiler les assets en production : yarn encore production*

### Lancement des tests

Pour avoir des données dans la base de test

    php bin/console doctrine:fixtures:load --env=test

Pour lancer les tests vous pouvez utiliser la commande

    php bin/phpunit src/Tests/Controller/FrontControllerTest.php

Elle teste si les urls du site retournent un code 500 ou non
