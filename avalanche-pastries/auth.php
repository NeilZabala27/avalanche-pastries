<?php
// Put "require 'auth.php';" at the top of every protected page.
require_once __DIR__ . '/autoload.php';
Auth::requireLogin();
