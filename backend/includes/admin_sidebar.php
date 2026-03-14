<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-avatar-container">
            <img src="<?php echo htmlspecialchars(admin_profile_pic()); ?>" alt="Admin Avatar" onerror="this.src='/mombasahamlets_web/frontend/images/29.jpg'; this.onerror=null;">
        </div>
        <h3><?php echo htmlspecialchars(admin_username() ?: 'Admin'); ?></h3>
        <p class="sidebar-role"><?php echo htmlspecialchars(admin_role()); ?></p>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="admin.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active' : ''; ?>" data-tab="dashboard"><i class="fas fa-tachometer-alt"></i> <span>Dashboard</span></a></li>
            <li><a href="admin.php?tab=news" data-tab="news"><i class="fas fa-newspaper"></i> <span>News</span></a></li>
            <li><a href="admin.php?tab=matches" data-tab="matches"><i class="fas fa-calendar-alt"></i> <span>Matches</span></a></li>
            <li><a href="admin.php?tab=players" data-tab="players"><i class="fas fa-users"></i> <span>Players</span></a></li>
            <li><a href="admin_orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? 'active' : ''; ?>"><i class="fas fa-shopping-bag"></i> <span>Orders</span></a></li>
            <li><a href="admin_payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admin_payments.php' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> <span>Payments</span></a></li>
            <li><a href="admin.php?tab=shop" data-tab="shop"><i class="fas fa-shopping-cart"></i> <span>Shop</span></a></li>
            <li><a href="admin.php?tab=events" data-tab="events"><i class="fas fa-calendar-check"></i> <span>Events</span></a></li>
            <li><a href="admin.php?tab=ticketing" data-tab="ticketing"><i class="fas fa-ticket-alt"></i> <span>Ticketing</span></a></li>
            <li><a href="admin.php?tab=partners" data-tab="partners"><i class="fas fa-handshake"></i> <span>Official Partners</span></a></li>
            <li><a href="admin.php?tab=media" data-tab="media"><i class="fas fa-photo-video"></i> <span>Media</span></a></li>
            <li><a href="admin.php?tab=messages" data-tab="messages"><i class="fas fa-envelope"></i> <span>Messages</span></a></li>
            <li><a href="admin.php?tab=about" data-tab="about"><i class="fas fa-info-circle"></i> <span>About Us</span></a></li>
            <li><a href="admin.php?tab=contact" data-tab="contact"><i class="fas fa-address-book"></i> <span>Contact Info</span></a></li>
            <li><a href="admin.php?tab=settings" data-tab="settings"><i class="fas fa-cog"></i> <span>Settings</span></a></li>
        </ul>
    </nav>
</aside>
