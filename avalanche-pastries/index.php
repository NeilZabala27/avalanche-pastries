<?php
// The front door of the website. Anyone who opens the site (the folder URL) lands here
// and is sent to the right page:
//   not logged in   -> login.php  (it shows "Create admin account" until the first account exists)
//   logged in       -> orders.php
require_once __DIR__ . '/autoload.php';
require __DIR__ . '/config.php';

Auth::start();

if (Auth::isLoggedIn()) {
    header('Location: orders.php');
    exit;
}

try {
    (new AdminRepository($pdo))->count();      // only checks that the database tables exist
} catch (PDOException $e) {
    // The tables are not there yet.
    http_response_code(500);
    die("The database is not set up. Import database/avalanche_pastries.sql in phpMyAdmin first.");
}

header('Location: login.php');
exit;
