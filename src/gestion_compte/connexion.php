<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="marge">
    <header>
        <a href="../../index.php">Accueil</a>
        <a href="connexion.php">Connexion</a>
        <a href="inscription.php">Inscription</a>
    </header>
    <h1 class="grand_titre">Bienvenu sur la page de connexion</h1>

    <form action="./process_signin.php" method="post">
        <div class="divcolonne">
            <input type="email" placeholder="Email" name="email">
            <input type="password" placeholder="Mot de passe" name="password">
            <button type="submit">Se connecter</button>
        </div>
    </form>
</body>
</html>