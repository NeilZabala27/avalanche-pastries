<?php
require_once 'autoload.php';
require 'config.php';

Auth::start();
if (Auth::isLoggedIn()) {
    header('Location: orders.php');
    exit;
}

$admins = new AdminRepository($pdo);

// The very first time nobody has an account, so this page shows the "Create admin account" form.
// As soon as one account exists, only the normal log-in form is shown (nobody can register again).
try {
    $firstTime = ($admins->count() === 0);
} catch (PDOException $e) {
    http_response_code(500);
    die("The database is not set up. Import database/avalanche_pastries.sql in phpMyAdmin first.");
}

$error = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($firstTime) {
        // Create the first (admin) account, then log in with it straight away
        try {
            $admin = new Admin();
            $admin->setName($username);                              // the Admin class checks its own data
            $admin->setPassword($password, $_POST['confirm'] ?? '');
            $admins->save($admin);
            Auth::login($pdo, $username, $password);
            header('Location: orders.php');
            exit;
        } catch (InvalidArgumentException | DomainException $e) {
            $error = $e->getMessage();
        }
    } elseif ($username === '') {
        $error = "Enter your username.";
    } elseif ($password === '') {
        $error = "Enter your password.";
    } elseif (Auth::login($pdo, $username, $password)) {
        header('Location: orders.php');
        exit;
    } else {
        $error = "Wrong username or password.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><title><?= $firstTime ? 'Create admin account' : 'Log in' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container narrow">
    <h1>Avalanche Pastries</h1>
    <?php if ($firstTime): ?>
        <p class="subtitle">Welcome! There is no account yet. Create the admin account to start using the system.</p>
    <?php endif; ?>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST" class="card">
        <?php if ($firstTime): ?><h3 class="card-title">Create admin account</h3><?php endif; ?>
        <div class="field"><label>Username</label><input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" autocomplete="username"<?= $username === '' ? ' autofocus' : '' ?>></div>
        <div class="field"><label>Password</label><input type="password" name="password" id="password" autocomplete="<?= $firstTime ? 'new-password' : 'current-password' ?>"<?= $username !== '' ? ' autofocus' : '' ?>></div>
        <?php if ($firstTime): ?>
            <div class="field"><label>Confirm password</label><input type="password" name="confirm" autocomplete="new-password">
                <small>Username: 3 to 50 letters, numbers, dots, dashes or underscores. Password: at least 6 characters.</small></div>
        <?php endif; ?>
        <button type="submit"><?= $firstTime ? 'Create admin account' : 'Log in' ?></button>
    </form>
</div>
<script>
    // Pressing Enter in the username box moves to the password box. Enter in the last box submits.
    document.getElementById('username').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('password').focus();
        }
    });
</script>
</body>
</html>
