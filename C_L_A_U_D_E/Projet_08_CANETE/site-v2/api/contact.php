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

// Captcha check
$captchaAnswer = $_SESSION['captcha_answer'] ?? null;
$captchaInput = intval($_POST['captcha'] ?? 0);
if ($captchaAnswer === null || $captchaInput !== $captchaAnswer) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mauvaise réponse au captcha. Réessaie.']);
    exit;
}

// Sanitize inputs
$name = sanitize($_POST['name'] ?? '');
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$phone = sanitize($_POST['phone'] ?? '');
$message = sanitize($_POST['message'] ?? '');

// Validate
if (empty($name) || !$email || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
    exit;
}

// Handle file upload (optional, JPG/PNG only, max 2 MB)
$attachmentPath = null;
$attachmentName = null;
if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['attachment'];
    $maxSize = 2 * 1024 * 1024; // 2 MB

    // Check size
    if ($file['size'] > $maxSize) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Le fichier ne doit pas dépasser 2 Mo.']);
        exit;
    }

    // Check MIME type via finfo (not user-supplied type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png'];
    if (!in_array($mime, $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Seuls les fichiers JPG et PNG sont acceptés.']);
        exit;
    }

    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Extension de fichier non autorisée.']);
        exit;
    }

    // Move to secure uploads dir (outside webroot ideally, here in uploads/)
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = $uploadDir . $safeName;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload.']);
        exit;
    }
    $attachmentPath = $dest;
    $attachmentName = $file['name'];
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
$body = "Nom : $name\nEmail : $email\n" . ($phone ? "Tél : $phone\n" : "") . "\nMessage :\n$message";

if ($attachmentPath && $attachmentName) {
    // Send multipart email with attachment
    $boundary = md5(uniqid(time()));
    $headers = "From: noreply@agencepingpong.fr\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"";

    $emailBody = "--$boundary\r\n";
    $emailBody .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $emailBody .= $body . "\r\n\r\n";
    $emailBody .= "--$boundary\r\n";
    $emailBody .= "Content-Type: application/octet-stream; name=\"" . basename($attachmentName) . "\"\r\n";
    $emailBody .= "Content-Transfer-Encoding: base64\r\n";
    $emailBody .= "Content-Disposition: attachment; filename=\"" . basename($attachmentName) . "\"\r\n\r\n";
    $emailBody .= chunk_split(base64_encode(file_get_contents($attachmentPath))) . "\r\n";
    $emailBody .= "--$boundary--";

    mail($to, $subject, $emailBody, $headers);
} else {
    $headers = "From: noreply@agencepingpong.fr\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
    mail($to, $subject, $body, $headers);
}

// Reset CSRF token
unset($_SESSION['csrf_token']);

echo json_encode(['success' => true]);
