<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: connexion.php');
	exit();
}


$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

// Valider que les champs ne sont pas vides
if (empty($email) || empty($password)) {
	header('Location: connexion.php?error=empty_fields');
	exit();
}


// Connexion à la base de données
$host = 'localhost';
$dbname = 'we4td01';
$user = 'root';
$pass = '';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	header('Location: connexion.php?error=db_error');
	exit();
}

// Vérifier si un compte existe deja pour cet email
$stmt = $pdo->prepare('SELECT Id, Password FROM user WHERE Email = ?');
$stmt->execute([$email]);
$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
	// Vérifier le mot de passe
	if (password_verify($password, $existingUser['Password'])) {
		// Connexion réussie
		$_SESSION['user_id'] = $existingUser['Id'];
		header('Location: index.php');
		exit();
	} else {
		header('Location: connexion.php?error=invalid_credentials');
		exit();
	}
} else {
	header('Location: connexion.php?error=invalid_credentials');
	exit();
}
?>


