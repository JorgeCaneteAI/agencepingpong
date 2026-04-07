<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalide']);
    exit;
}

// Sanitize inputs
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$message = sanitize($_POST['message'] ?? '');

// Validate
if (empty($name) || !$email || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont requis']);
    exit;
}

// Store in DB
$pdo = connectDb();
if ($pdo) {
    $stmt = $pdo->prepare('INSERT INTO contacts (name, email, message, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->execute([$name, $email, $message]);
}

// Send email
$to = CONTACT_EMAIL;
$subject = 'Agence Ping Pong — Nouveau message de ' . $name;
$body = "Nom : $name\nEmail : $email\n\nMessage :\n$message";
$headers = "From: noreply@agencepingpong.fr\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";

mail($to, $subject, $body, $headers);

// Reset CSRF token
unset($_SESSION['csrf_token']);

echo json_encode(['success' => true]);
