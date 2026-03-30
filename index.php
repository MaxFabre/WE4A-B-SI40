<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="marge">
    <header>
        <a href="../../index.php">Accueil</a>
        <a href="src/gestion_compte/connexion.php">Connexion</a>
        <a href="src/gestion_compte/inscription.php">Inscription</a>
    </header>
    <h1 class="grand_titre">Ceci est l'accueil</h1>
    <p>Que souhaitez vous faire ? </p>
    <button class="transform transition-transform duration-200 hover:scale-105 bg-blue-600 active:bg-blue-100 text-white px-4 py-2 rounded" onclick="window.location.href='connexion.php'">Se connecter</button>
    <button class="transform transition-transform duration-200 hover:scale-105* bg-green-600 active:bg-green-100 text-white px-4 py-2 rounded ml-2" onclick="window.location.href='inscription.php'">S'inscrire</button>
</body>
</html>