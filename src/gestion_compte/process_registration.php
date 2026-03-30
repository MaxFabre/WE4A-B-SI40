<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header('Location: inscription.php');
	exit();
}


$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$birthdate = isset($_POST['birthdate']) ? trim($_POST['birthdate']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirmPassword = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// Valider que les champs ne sont pas vides
if (empty($email) || empty($birthdate) || empty($password) || empty($confirmPassword)) {
	header('Location: inscription.php?error=empty_fields');
	exit();
}

if ($password !== $confirmPassword) {
	header('Location: inscription.php?error=password_mismatch');
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
	header('Location: inscription.php?error=db_error');
	exit();
}

// Vérifier si un compte existe deja pour cet email
$stmt = $pdo->prepare('SELECT Id FROM user WHERE Email = ?');
$stmt->execute([$email]);
$existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existingUser) {
	header('Location: inscription.php?error=email_exists');
	exit();
}

// Hacher le mot de passe avant insertion
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
	$insertStmt = $pdo->prepare('INSERT INTO user (Email, Birthdate, Password) VALUES (?, ?, ?)');
	$insertStmt->execute([$email, $birthdate, $passwordHash]);
	header('Location: connexion.php?success=registration_done');
	exit();
} catch (PDOException $e) {
	header('Location: inscription.php?error=registration_failed');
	exit();
}


