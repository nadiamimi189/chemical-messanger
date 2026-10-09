<?php
require_once __DIR__ . '/../includes/init.php';
requireAdminRoot();

$adminId = currentUserId();
$uploadError = null;
$justCreated = isset($_GET['created']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_product') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!csrfCheck()) {
        $uploadError = 'Invalid request, please try again.';
    } elseif ($name === '') {
        $uploadError = 'Enter a product name.';
    } elseif (strlen($name) > 150) {
        $uploadError = 'Product names must be 150 characters or fewer.';
    } else {
        try {
            $media = handleMediaUpload('media', 'uploads/products');
            if (!$media) {
                $uploadError = 'Choose a product image or video to upload.';
            } else {
                $insertProduct = $pdo->prepare('INSERT INTO products (admin_id, name, description, media_path, media_type) VALUES (?, ?, ?, ?, ?)');
                $insertProduct->execute([$adminId, $name, $description, $media['path'], $media['type']]);
                header('Location: products.php?created=1');
                exit;
            }
        } catch (RuntimeException $e) {
            $uploadError = $e->getMessage();
        }
    }
}

$productsStmt = $pdo->query('SELECT id, name, description, media_type, created_at FROM products ORDER BY created_at DESC');
$products = $productsStmt->fetchAll();

$pageTitle = 'Products - Chemical Connect';
$assetPrefix = '../';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/navbar.php';
?>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <div>
        <strong>Chemical Ops</strong>
        <span>Control center</span>
      </div>
    </div>
    <nav class="admin-nav" aria-label="Admin navigation">
      <a class="admin-nav-item" href="dashboard.php">Dashboard</a>
      <a class="admin-nav-item" href="users.php">Members</a>
      <a class="admin-nav-item" href="messages.php">Messages</a>
      <a class="admin-nav-item" href="../index.php">Community</a>
      <a class="admin-nav-item active" href="products.php">Products</a>
      <a class="admin-nav-item" href="../logout.php">Logout</a>
    </nav>
    <div class="admin-sidebar-footer">
      <div class="system-badge"><span>System status</span><span class="indicator"></span></div>
    </div>
  </aside>

  <main class="admin-main product-admin-main">
    <div class="dashboard-header">
      <div>
        <h1>Product library</h1>
        <p>Upload product photos and videos to feature them in the member feed.</p>
      </div>
      <span class="soft-pill success"><?php echo count($products); ?> products</span>
    </div>

    <?php if ($justCreated): ?>
      <div class="alert alert-success">Product uploaded and added to the member feed.</div>
    <?php endif; ?>
    <?php if ($uploadError): ?>
      <div class="alert alert-error"><?php echo e($uploadError); ?></div>
    <?php endif; ?>

    <section class="product-upload-panel" aria-labelledby="product-upload-title">
      <div class="product-admin-heading">
        <div>
          <span class="product-rail-kicker">CATALOG</span>
          <h2 id="product-upload-title">Add a product</h2>
        </div>
      </div>
      <form method="POST" enctype="multipart/form-data" class="product-upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
        <input type="hidden" name="action" value="create_product">
        <label>
          <span>Product name</span>
          <input type="text" name="name" maxlength="150" required value="<?php echo e($_POST['name'] ?? ''); ?>" placeholder="e.g. Industrial-grade solvent">
        </label>
        <label>
          <span>Description <small>Optional</small></span>
          <textarea name="description" rows="3" placeholder="Key details for members"><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </label>
        <label class="product-file-field">
          <span>Product image or video</span>
          <input type="file" name="media" class="media-input" accept="image/*,video/*" required>
          <small>JPG, PNG, GIF, WEBP, MP4, WEBM, MOV or OGG. Max 50 MB.</small>
        </label>
        <div class="file-preview" aria-live="polite"></div>
        <button type="submit" class="btn btn-primary">Upload product</button>
      </form>
    </section>

    <section class="product-library" aria-labelledby="product-library-title">
      <div class="product-admin-heading">
        <div>
          <span class="product-rail-kicker">LATEST UPLOADS</span>
          <h2 id="product-library-title">Products</h2>
        </div>
      </div>
      <?php if (empty($products)): ?>
        <p class="product-rail-empty">No products uploaded yet.</p>
      <?php else: ?>
        <div class="admin-product-grid">
          <?php foreach ($products as $product): ?>
            <article class="admin-product-card">
              <div class="product-card-media">
                <?php if ($product['media_type'] === 'video'): ?>
                  <video controls muted playsinline preload="metadata" src="../product_media.php?id=<?php echo (int)$product['id']; ?>"></video>
                  <span class="product-media-type">VIDEO</span>
                <?php else: ?>
                  <img src="../product_media.php?id=<?php echo (int)$product['id']; ?>" alt="<?php echo e($product['name']); ?>">
                  <span class="product-media-type">PRODUCT</span>
                <?php endif; ?>
              </div>
              <div class="product-card-copy">
                <span class="product-card-date"><?php echo e(timeAgo($product['created_at'])); ?></span>
                <strong><?php echo e($product['name']); ?></strong>
                <?php if (trim($product['description']) !== ''): ?>
                  <p><?php echo e($product['description']); ?></p>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>