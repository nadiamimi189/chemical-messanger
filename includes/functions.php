<?php
/**
 * Shared helper functions: sanitization, file uploads, time formatting, admin lookup.
 */

function e($str): string
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function timeAgo(string $datetime): string
{
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    return date('M j, Y', $timestamp);
}

/**
 * Returns the single admin user's id (system assumes one admin account,
 * matching the reference design's single "Admin" persona).
 */
function getAdminId(PDO $pdo)
{
    static $adminId = null;
    if ($adminId === null) {
        $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        $row = $stmt->fetch();
        $adminId = $row ? (int)$row['id'] : 0;
    }
    return $adminId;
}

/**
 * Handles a single image/video upload.
 * Returns ['path' => relative_path, 'type' => 'image'|'video'] or null if no file was sent.
 * Throws RuntimeException on validation failure.
 */
function handleMediaUpload(string $fieldName, string $destDirRelative): ?array
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error (code ' . $file['error'] . ').');
    }

    $maxBytes = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File is too large. Maximum size is 50MB.');
    }

    $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $videoExt = ['mp4', 'webm', 'mov', 'ogg'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $imageExt, true)) {
        $type = 'image';
    } elseif (in_array($ext, $videoExt, true)) {
        $type = 'video';
    } else {
        throw new RuntimeException('Unsupported file type. Allowed: jpg, png, gif, webp, mp4, webm, mov, ogg.');
    }

    $destDirAbsolute = __DIR__ . '/../' . $destDirRelative;
    if (!is_dir($destDirAbsolute)) {
        mkdir($destDirAbsolute, 0755, true);
    }

    $filename = uniqid('media_', true) . '.' . $ext;
    $destPath = $destDirAbsolute . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new RuntimeException('Failed to save uploaded file.');
    }

    return [
        'path' => $destDirRelative . '/' . $filename,
        'type' => $type,
    ];
}

function sendUploadedMedia(string $relativePath, string $mediaType, string $expectedDirectory): void
{
    $directory = realpath(__DIR__ . '/../' . $expectedDirectory);
    $filePath = realpath(__DIR__ . '/../' . $relativePath);

    if (!$directory || !$filePath || !is_file($filePath)) {
        http_response_code(404);
        exit;
    }

    $directoryPrefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $normalizedPrefix = DIRECTORY_SEPARATOR === '\\' ? strtolower($directoryPrefix) : $directoryPrefix;
    $normalizedPath = DIRECTORY_SEPARATOR === '\\' ? strtolower($filePath) : $filePath;
    if (strncmp($normalizedPath, $normalizedPrefix, strlen($normalizedPrefix)) !== 0) {
        http_response_code(404);
        exit;
    }

    $allowedMimeTypes = $mediaType === 'image'
        ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        : ($mediaType === 'video'
            ? ['video/mp4', 'video/webm', 'video/quicktime', 'video/ogg']
            : []);
    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($filePath);
    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        http_response_code(404);
        exit;
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    readfile($filePath);
    exit;
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Renders a single comment bubble. $row must contain:
 * author_name, author_role, comment, created_at
 */
function renderCommentHtml(array $row): string
{
    $isAdminAuthor = $row['author_role'] === 'admin';
    $bubbleClass = $isAdminAuthor ? 'comment-bubble from-admin' : 'comment-bubble';
    $tag = $isAdminAuthor ? '<span class="tag">Admin</span>' : '';

    ob_start();
    ?>
    <div class="comment-item">
      <img class="avatar" style="width:28px;height:28px;" src="https://api.dicebear.com/7.x/initials/svg?seed=<?php echo urlencode($row['author_name']); ?>&backgroundColor=1d5aa8" alt="">
      <div>
        <div class="<?php echo $bubbleClass; ?>">
          <div class="comment-name"><?php echo e($row['author_name']); ?><?php echo $tag; ?></div>
          <?php echo nl2br(e($row['comment'])); ?>
        </div>
        <div class="comment-time"><?php echo timeAgo($row['created_at']); ?></div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renders a single chat bubble. $row must contain: sender_id, message, created_at.
 * $currentUserId is used to decide left/right alignment.
 */
function renderChatMsgHtml(array $row, $currentUserId): string
{
    $mine = (int)$row['sender_id'] === (int)$currentUserId;
    $cls = $mine ? 'chat-msg mine' : 'chat-msg theirs';
    ob_start();
    ?>
    <div class="<?php echo $cls; ?>">
      <?php echo nl2br(e($row['message'])); ?>
      <span class="chat-time"><?php echo timeAgo($row['created_at']); ?></span>
    </div>
    <?php
    return ob_get_clean();
}
