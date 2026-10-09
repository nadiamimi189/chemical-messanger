<?php
require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
  header('Location: ' . (isAdmin() ? 'admin/dashboard.php' : 'index.php'));
    exit;
}

$errors = [];
$email = '';
$justRegistered = isset($_GET['registered']);
$isAjax = strtolower($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json'
  || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

  if (!csrfCheck()) {
    $errors[] = 'Your session has expired. Refresh the page and try again.';
  } elseif ($email === '' || $password === '') {
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

            $redirect = $user['role'] === 'admin' ? 'admin/dashboard.php' : 'index.php';
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true, 'redirect' => $redirect]);
                exit;
            }

            header('Location: ' . $redirect);
            exit;
        }
    }

    if ($isAjax) {
        http_response_code(422);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
}

$pageTitle = 'Log In - Chemical Connect';
$bodyClass = 'login-page';
$csrfToken = csrfToken();
require __DIR__ . '/includes/header.php';
?>
<main class="auth-wrap login-wrap">
  <section class="login-intro" aria-labelledby="login-intro-title">
    <a class="login-brand" href="index.php" aria-label="Chemical Connect home">
      <span class="brand-icon" aria-hidden="true">🧪</span>
      <span>Chemical Connect</span>
    </a>
    <div class="login-intro-copy">
      <p class="login-eyebrow">THE CHEMISTRY COMMUNITY</p>
      <h1 id="login-intro-title">Good ideas react<br>when we connect.</h1>
      <p>Pick up where your curiosity left off. Sign in to join the conversation.</p>
    </div>
    <div class="login-note" aria-hidden="true">
      <span class="login-note-mark">CC</span>
      <span>Knowledge grows through exchange.</span>
    </div>
  </section>

  <section class="auth-card login-card" aria-labelledby="login-title">
    <div class="brand-row">
      <p class="login-eyebrow">WELCOME BACK</p>
      <h2 id="login-title">Log in to your account</h2>
      <p class="login-subtitle">Enter your details to continue.</p>
    </div>

    <?php if ($justRegistered): ?>
      <div class="alert alert-success" role="status">Account created! You can now log in.</div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-error" role="alert"><?php echo e($err); ?></div>
    <?php endforeach; ?>

    <div class="login-feedback" id="loginFeedback" role="alert" aria-live="polite" hidden></div>
    <form method="POST" action="login.php" class="login-form" id="loginForm">
      <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo e($email); ?>" required maxlength="150" autocomplete="username" autofocus aria-describedby="emailHint">
        
      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-control">
          <input type="password" id="password" name="password" required autocomplete="current-password" aria-describedby="passwordHint">
          <button class="password-toggle" type="button" id="passwordToggle" aria-label="Show password" aria-pressed="false">Show</button>
        </div>
       
        <a class="login-forgot" href="forgot_password.php">Forgot password?</a>
      </div>
      <button type="submit" class="btn btn-primary btn-block login-submit" id="loginSubmit">
        <span class="button-label">Log in</span>
        <span class="button-spinner" aria-hidden="true"></span>
      </button>
    </form>
    <div class="auth-switch">New to Chemical Connect? <a href="signup.php">Create an account</a></div>
   
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
