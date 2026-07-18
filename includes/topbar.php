<?php
$page_title = $page_title ?? 'Dashboard';
$initials = strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1));
?>
<div class="topbar">
  <h2><?= h($page_title) ?></h2>
  <div class="user-chip">
    <div class="avatar"><?= h($initials) ?></div>
    <span><?= h($_SESSION['admin_name'] ?? 'Admin') ?></span>
  </div>
</div>
