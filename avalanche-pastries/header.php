<?php
// Shared top of every logged-in page: <head>, the stylesheet, the menu, and the message shown after a save.
// A page sets $pageTitle (the browser tab title) BEFORE including this file. footer.php closes the page.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = $pageTitle ?? 'Avalanche Pastries';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> - Avalanche Pastries</title>
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
</head>
<body>
<div class="container">
<?php include __DIR__ . '/nav.php'; ?>
<?php if (!empty($_SESSION['msg'])): ?>
    <div class="success"><?= htmlspecialchars($_SESSION['msg']) ?></div>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>
