<?php
$activeAdminPage = $activeAdminPage ?? '';
?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="admin-brand">
    <div>
      <strong>Chemical Connect</strong>
      <span>Admin workspace</span>
    </div>
    <button class="admin-sidebar-close" type="button" aria-label="Close navigation" title="Close navigation">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
    </button>
  </div>
  <nav class="admin-nav" aria-label="Admin navigation">
    <a class="admin-nav-item <?php echo $activeAdminPage === 'newsfeed' ? 'active' : ''; ?>" href="dashboard.php" <?php echo $activeAdminPage === 'newsfeed' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m3.5 10 8.5-7 8.5 7v10a1 1 0 0 1-1 1h-5.3v-6.2h-4.4V21H4.5a1 1 0 0 1-1-1z"/></svg></span><span>Newsfeed</span>
    </a>
    <a class="admin-nav-item <?php echo $activeAdminPage === 'members' ? 'active' : ''; ?>" href="users.php" <?php echo $activeAdminPage === 'members' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20v-1.2a6.2 6.2 0 0 1 12.4 0V20zM16 5a3.5 3.5 0 0 1 0 6.8M18 14a4.8 4.8 0 0 1 3.2 4.6V20h-3"/></svg></span><span>Members</span>
    </a>
    <a class="admin-nav-item <?php echo $activeAdminPage === 'messages' ? 'active' : ''; ?>" href="messages.php" <?php echo $activeAdminPage === 'messages' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><span>Messages</span>
    </a>
    <a class="admin-nav-item <?php echo $activeAdminPage === 'products' ? 'active' : ''; ?>" href="products.php" <?php echo $activeAdminPage === 'products' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 8.5 4.5v9L12 21l-8.5-4.5v-9zM3.8 7.7 12 12l8.2-4.3M12 12v9"/></svg></span><span>Products</span>
    </a>
    <a class="admin-nav-item <?php echo $activeAdminPage === 'settings' ? 'active' : ''; ?>" href="settings.php" <?php echo $activeAdminPage === 'settings' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.4 1.1-1.4 2.4-1.7-.7a8 8 0 0 1-1.8 1l-.3 1.8h-2.8l-.3-1.8a8 8 0 0 1-1.8-1l-1.7.7-1.4-2.4 1.4-1.1a7 7 0 0 1 0-2l-1.4-1.1 1.4-2.4 1.7.7a8 8 0 0 1 1.8-1l.3-1.8h2.8l.3 1.8a8 8 0 0 1 1.8 1l1.7-.7 1.4 2.4-1.4 1.1a7 7 0 0 1 0 2z"/></svg></span><span>Settings</span>
    </a>
    <a class="admin-nav-item <?php echo $activeAdminPage === 'bulk-email' ? 'active' : ''; ?>" href="bulk_email.php" <?php echo $activeAdminPage === 'bulk-email' ? 'aria-current="page"' : ''; ?>>
      <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg></span><span>Bulk Email</span>
    </a>
  </nav>
  <a class="admin-nav-item admin-logout" href="../logout.php">
    <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5M14 16l4-4-4-4M18 12H9"/></svg></span><span>Log out</span>
  </a>
</aside>
<button class="admin-sidebar-scrim" type="button" aria-label="Close navigation" tabindex="-1" aria-hidden="true"></button>