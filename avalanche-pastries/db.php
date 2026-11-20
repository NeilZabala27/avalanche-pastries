<?php
// Shared setup for the inventory pages: login check, class loader, database connection.
require_once __DIR__ . '/auth.php';     // starts the session, loads classes/, and requires a logged-in user
require_once __DIR__ . '/config.php';   // the group's shared database connection ($pdo)
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function h($text) { return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8'); }

function num($value) { return number_format((float)$value, 2, '.', ''); }

function flash($message) { $_SESSION['msg'] = $message; }
