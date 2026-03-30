<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="marge">
    <header>
        <a href="../../index.php">Accueil</a>
        <a href="connexion.php">Connexion</a>
        <a href="inscription.php">Inscription</a>
    </header>
    <h1 class="grand_titre">Inscription</h1>
    <form action="./process_registration.php" method="post">
        <div class="divcolonne">
            <input type="email" name="email" placeholder="Email">
            <input type="text" name="birthdate" placeholder="Date de naissance YYYY-MM-DD">
            <input type="password" name="password" placeholder="Mot de passe">
            <input type="password" name="confirm_password" placeholder="Confirmation du mot de passe">
            <button type="submit">S'inscrire</button>
        </div>
    </form>
</body>
</html>