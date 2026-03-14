<header class="main-header">
    <div class="header-left">
        <button class="menu-toggle" aria-label="Toggle Menu"><i class="fas fa-bars"></i></button>
        <h1 id="page-title"><?php echo $pageTitle ?? 'Admin Panel'; ?></h1>
    </div>
    
    <div class="header-right">
        <?php if (basename($_SERVER['PHP_SELF']) === 'admin.php'): ?>
        <div class="notification-container">
            <div class="notification-bell" id="notification-bell" title="Notifications">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="notification-unread-count" style="display: none;">0</span>
            </div>
            <div class="notification-dropdown" id="notification-dropdown">
                <div class="dropdown-header">
                    <h3>Notifications</h3>
                    <button id="mark-all-read">Mark all as read</button>
                </div>
                <div class="notification-list" id="notification-list">
                    <div class="no-notifications">No new notifications</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <a href="admin_profile.php" class="user-pill-link">
            <div class="user-pill">
                <div class="user-avatar-wrapper">
                    <img src="<?php echo htmlspecialchars(admin_profile_pic()); ?>" alt="Admin Avatar" class="user-avatar" onerror="this.src='/mombasahamlets_web/frontend/images/29.jpg';">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars(admin_username() ?: 'Admin'); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars(admin_role()); ?></span>
                </div>
            </div>
        </a>
        <a href="logout.php" class="btn-logout-circle" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</header>
