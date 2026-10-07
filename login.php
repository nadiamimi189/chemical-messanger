<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';
$justRegistered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, password, role, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] === 'blocked') {
            $errors[] = 'This account has been blocked. Please contact the Admin.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            header('Location: ' . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php'));
            exit;
        }
    }
}

$pageTitle = 'Log In - Chemical Connect';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
  <div class="auth-scene" aria-hidden="true">
    <div class="shape shape-one"></div>
    <div class="shape shape-two"></div>
    <div class="shape shape-three"></div>
  </div>

  <div class="auth-card">
    <div class="brand-row">
      <div class="brand-icon">🧪</div>
      <h1>Chemical Connect</h1>
    </div>
    
    <?php if ($justRegistered): ?>
      <div class="alert alert-success">Account created! You can now log in.</div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required autofocus>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <div class="auth-switch">New here? <a href="signup.php">Create an account</a></div>

  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
