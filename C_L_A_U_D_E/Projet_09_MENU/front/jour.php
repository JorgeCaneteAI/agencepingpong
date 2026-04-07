<?php
/**
 * MealCoach — Vue jour
 * Redirige vers /semaine?jour=N
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth.php';
requireLogin();

$jour = isset($_GET['jour']) ? max(0, min(6, (int) $_GET['jour'])) : (int) date('N') - 1;

header('Location: ' . BASE_URL . '/semaine?jour=' . $jour);
exit;
