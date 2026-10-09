<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$recipientsStmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'user' ORDER BY email ASC");
$recipients = $recipientsStmt->fetchAll();
$recipientCount = count($recipients);
$subject = '';
$message = '';
$error = '';
$result = '';
$resultType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!csrfCheck()) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($subject === '' || mb_strlen($subject) > 180) {
        $error = 'Enter a subject no longer than 180 characters.';
    } elseif ($message === '' || mb_strlen($message) > 10000) {
        $error = 'Enter a message no longer than 10,000 characters.';
    } elseif ($recipientCount === 0) {
        $error = 'There are no member accounts to email.';
    } else {
        $sentCount = 0;
        try {
            require_once __DIR__ . '/../includes/mailer.php';
            $mailer = createAppMailer();
            $mailer->SMTPKeepAlive = true;
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = '<div style="font-family:Arial,sans-serif;font-size:15px;line-height:1.6;color:#20363d">'
                . '<p>' . nl2br(e($message), false) . '</p>'
                . '<p style="margin-top:28px;color:#647a80">Chemical Connect</p></div>';
            $mailer->AltBody = $message . "\n\nChemical Connect";

            foreach ($recipients as $recipient) {
                $mailer->clearAllRecipients();
                $mailer->addAddress($recipient['email'], $recipient['name']);
                try {
                    $mailer->send();
                    $sentCount++;
                } catch (Throwable $exception) {
                    error_log('Bulk email stopped for member ' . $recipient['id'] . ': ' . $exception->getMessage());
                    break;
                }
            }
            $mailer->smtpClose();

            if ($sentCount === $recipientCount) {
                $result = 'Email sent to all ' . $sentCount . ' member accounts.';
                $subject = '';
                $message = '';
            } else {
                $resultType = 'error';
                $result = 'Delivery stopped after ' . $sentCount . ' of ' . $recipientCount . ' messages. Check the SMTP settings and server log before trying again.';
            }
        } catch (Throwable $exception) {
            error_log('Bulk email setup failed: ' . $exception->getMessage());
            $error = 'Email could not be sent. Check the SMTP settings and try again.';
        }
    }
}

$pageTitle = 'Bulk Email - Chemical Connect';
$assetPrefix = '../';
$activeAdminPage = 'bulk-email';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <?php require __DIR__ . '/../includes/admin_sidebar.php'; ?>
  <main class="admin-main admin-bulk-email-main">
    <div class="dashboard-header">
      <button class="admin-sidebar-toggle" type="button" aria-controls="adminSidebar" aria-expanded="true" aria-label="Hide navigation" title="Hide navigation">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <div>
        <h1>Bulk email</h1>
        <p>Send a private email to every member account.</p>
      </div>
    </div>

    <?php if ($result !== ''): ?>
      <div class="alert alert-<?php echo e($resultType); ?>" role="status"><?php echo e($result); ?></div>
    <?php endif; ?>
    <?php if ($error !== ''): ?>
      <div class="alert alert-error" role="alert"><?php echo e($error); ?></div>
    <?php endif; ?>

    <section class="panel admin-bulk-email-panel" aria-labelledby="bulk-email-title">
      <div class="panel-header">
        <h2 id="bulk-email-title">Compose announcement</h2>
        <span class="panel-tag"><?php echo $recipientCount; ?> member<?php echo $recipientCount === 1 ? '' : 's'; ?></span>
      </div>
      <p class="bulk-email-notice">Messages are sent separately, so recipients cannot see one another's email addresses.</p>
      <form class="admin-bulk-email-form" method="POST" onsubmit="return confirm('Send this email to all <?php echo $recipientCount; ?> member accounts?');">
        <input type="hidden" name="csrf_token" value="<?php echo e(csrfToken()); ?>">
        <label>
          <span>Subject</span>
          <input type="text" name="subject" maxlength="180" value="<?php echo e($subject); ?>" required>
        </label>
        <label>
          <span>Message</span>
          <textarea name="message" rows="12" maxlength="10000" required><?php echo e($message); ?></textarea>
          <small>Plain text, up to 10,000 characters.</small>
        </label>
        <button class="btn btn-primary" type="submit" <?php echo $recipientCount === 0 ? 'disabled' : ''; ?>>Send to all members</button>
      </form>
    </section>
  </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>