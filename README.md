# WE4A-B-SI40

# Front-End

## Requirements
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
