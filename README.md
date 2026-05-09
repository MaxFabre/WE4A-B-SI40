# WE4A-B-SI40

# Installation
Ce projet utilise symfony, alors pour l'utiliser correctement il faut installer l'ensemble des dépencances.  
De plus afin de profiter de toutre les fonctionalités du site une base de donnée d'exemple est fournie,
il est nécessaire de l'utiliser avec PostgreSQL. Pour impirter les données il suffit d'executer les commande  
fournie dans le fichier `WE4X_SI40.sql`.

Afin de simplifier la navigation tout les comptes fournie ont pour unique mot de passe: `123456`.
Une fois installé lancer le serveur de test avec `symfony server:start`.

# Dépendances
Utiliser ces commandes afin d'obtenir les packages:
Vous pouvez tout installer d'un coup avec `composer install`

## Front-End
`ccomposer require symfony/twig-bundle`  
`composer require symfony/twig-pack`  
`composer require symfony/asset`  
`composer require symfony/ux-icons`

## Back-End:
`composer require symfony/security-bundle`  
`composer require symfony/orm-pack`
`composer require --dev symfony/maker-bundle`  
`composer require form validator`  
`composer require vich/uploader-bundle`  
`composer require symfony/serializer-pack`

## Point de vigilance
Après toute modification de fichiers dans /assets, il faut "recompiler" (merci asset-mapper):
`php bin/console cache:clear`  
`php bin/console asset-map:compile`
