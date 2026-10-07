<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $errors[] = 'Name, email and password are all required.';
    }
    if ($name !== '' && strlen($name) > 100) {
        $errors[] = 'Name is too long.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Please log in instead.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hash, 'user']);

        $userId = (int)$pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['name']    = $name;
        $_SESSION['role']    = 'user';

        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'Sign Up - Chemical Connect';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="brand-row">
      <div class="brand-icon">🧪</div>
      <h1>Chemical Connect</h1>
    </div>
    <p class="sub">Create your account to join the community. Sign up requires only your name and email &mdash; without an account you can't view any posts or discussions.</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="signup.php">
      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name" value="<?php echo e($name); ?>" required maxlength="100">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required maxlength="150">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="6">
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign Up</button>
    </form>
    <div class="auth-switch">Already have an account? <a href="login.php">Log in</a></div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
