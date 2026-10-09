<?php
require_once __DIR__ . '/includes/init.php';
requireLogin();

$searchQuery = trim($_GET['q'] ?? '');
$postResults = [];
$productResults = [];

if ($searchQuery !== '') {
    $pattern = '%' . $searchQuery . '%';

    $postStmt = $pdo->prepare(" 
        SELECT p.id, p.content, p.media_type, p.created_at, u.name AS author_name
        FROM admin_posts p
        JOIN users u ON u.id = p.admin_id
        WHERE u.role = 'admin' AND (p.content LIKE ? OR u.name LIKE ?)
        ORDER BY p.created_at DESC
        LIMIT 30
    ");
    $postStmt->execute([$pattern, $pattern]);
    $postResults = $postStmt->fetchAll();

    $productStmt = $pdo->prepare('SELECT id, name, description, media_type, created_at FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC LIMIT 30');
    $productStmt->execute([$pattern, $pattern]);
    $productResults = $productStmt->fetchAll();
}

$pageTitle = 'Search - Chemical Connect';
$assetPrefix = '';
$bodyClass = isAdmin() ? '' : 'member-app';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
$homeHref = isAdmin() ? 'admin/dashboard.php' : 'index.php';
?>
<main class="search-page">
  <header class="search-page-heading">
    <span class="product-rail-kicker">COMMUNITY SEARCH</span>
    <h1><?php echo $searchQuery !== '' ? 'Results for “' . e($searchQuery) . '”' : 'Search Chemical Connect'; ?></h1>
    <?php if ($searchQuery !== ''): ?>
      <p><?php echo count($postResults) + count($productResults); ?> public result<?php echo count($postResults) + count($productResults) === 1 ? '' : 's'; ?></p>
    <?php else: ?>
      <p>Search public announcements and the product catalog.</p>
    <?php endif; ?>
  </header>

  <?php if ($searchQuery === ''): ?>
    <p class="search-empty">Enter a keyword in the search field above to get started.</p>
  <?php elseif (empty($postResults) && empty($productResults)): ?>
    <p class="search-empty">No public announcements or products matched that search.</p>
  <?php else: ?>
    <?php if (!empty($productResults)): ?>
      <section class="search-result-section" aria-labelledby="product-results-title">
        <h2 id="product-results-title">Products</h2>
        <div class="search-product-grid">
          <?php foreach ($productResults as $product): ?>
            <article class="search-product-result">
              <div class="search-result-media">
                <?php if ($product['media_type'] === 'video'): ?>
                  <video controls muted playsinline preload="metadata" src="product_media.php?id=<?php echo (int)$product['id']; ?>"></video>
                <?php else: ?>
                  <img src="product_media.php?id=<?php echo (int)$product['id']; ?>" alt="<?php echo e($product['name']); ?>">
                <?php endif; ?>
              </div>
              <div class="search-result-copy">
                <span><?php echo e(timeAgo($product['created_at'])); ?> · Product</span>
                <h3><?php echo e($product['name']); ?></h3>
                <?php if (trim($product['description']) !== ''): ?><p><?php echo e($product['description']); ?></p><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php if (!empty($postResults)): ?>
      <section class="search-result-section" aria-labelledby="announcement-results-title">
        <h2 id="announcement-results-title">Announcements</h2>
        <div class="search-post-list">
          <?php foreach ($postResults as $post): ?>
            <article class="search-post-result">
              <div class="search-result-copy">
                <span><?php echo e($post['author_name']); ?> · <?php echo e(timeAgo($post['created_at'])); ?></span>
                <p><?php echo $post['content'] !== null && trim($post['content']) !== '' ? nl2br(e($post['content'])) : 'Public announcement'; ?></p>
                <a href="<?php echo e($homeHref); ?>#post-<?php echo (int)$post['id']; ?>">Open announcement</a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>