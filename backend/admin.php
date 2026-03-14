<?php
require_once __DIR__ . '/includes/auth.php';
admin_require_login();
$adminUser = admin_username() ?? 'Admin User';

// Fetch dynamic counts for dashboard
$newsCount = 0;
$matchesCount = 0;
$playersCount = 0;
$productsCount = 0;

if (isset($conn)) {
    // News Articles
    $res = $conn->query("SELECT COUNT(*) as total FROM news");
    if ($res) $newsCount = $res->fetch_assoc()['total'];
    
    // Upcoming Matches (Scheduled or Upcoming)
    $res = $conn->query("SELECT COUNT(*) as total FROM matches WHERE status IN ('upcoming', 'scheduled')");
    if ($res) $matchesCount = $res->fetch_assoc()['total'];
    
    // Players
    $res = $conn->query("SELECT COUNT(*) as total FROM players");
    if ($res) $playersCount = $res->fetch_assoc()['total'];
    
    // Products
    $res = $conn->query("SELECT COUNT(*) as total FROM shop");
    if ($res) $productsCount = $res->fetch_assoc()['total'];

    // Payments
    $res = $conn->query("SELECT COUNT(*) as total FROM payments");
    if ($res) $paymentsCount = $res->fetch_assoc()['total'];

    $latestPayments = [];
    $res = $conn->query("SELECT id, amount, status, created_at FROM payments ORDER BY created_at DESC LIMIT 5");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $latestPayments[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Mombasa Hamlets FC</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Removed TinyMCE dependency for lightweight textareas -->

    <style>
        /* Temporary styles to ensure basic functionality */
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .btn-edit, .btn-delete { 
            background: none; 
            border: none; 
            cursor: pointer; 
            margin: 0 5px; 
        }
        .btn-edit { color: #3498db; }
        .btn-delete { color: #e74c3c; }
    </style>
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
</head>


<body>
    <script>
        // Tab switching logic is now in admin.js
    </script>
      
    <div class="sidebar-overlay"></div>
    <div class="admin-container">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="main-content">
            <?php 
            $pageTitle = 'Dashboard';
            include __DIR__ . '/includes/admin_header.php'; 
            ?>

            <div class="content-wrapper">
                <!-- Dashboard -->
                <section id="dashboard-tab" class="tab-content active">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <i class="fas fa-newspaper"></i>
                            <h3>News Articles</h3>
                            <p class="number"><?php echo (int)$newsCount; ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Upcoming Matches</h3>
                            <p class="number"><?php echo (int)$matchesCount; ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-users"></i>
                            <h3>Players</h3>
                            <p class="number"><?php echo (int)$playersCount; ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>Products</h3>
                            <p class="number"><?php echo (int)$productsCount; ?></p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-credit-card"></i>
                            <h3>Total Payments</h3>
                            <p class="number"><?php echo (int)$paymentsCount; ?></p>
                        </div>
                    </div>
                    
                    <div class="dashboard-secondary-grid">
                        <div class="card">
                            <h2>Recent Activity</h2>
                            <p>Welcome to your admin panel. Here you can manage all aspects of your website. Use the sidebar to navigate through news articles, match updates, player management, and and your store settings.</p>
                        </div>

                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <h2 style="margin: 0;">Latest Payments</h2>
                                <a href="admin_payments.php" style="font-size: 0.85rem; color: var(--primary);">View All</a>
                            </div>
                            <table class="admin-table" style="font-size: 0.85rem;">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($latestPayments as $lp): ?>
                                    <tr>
                                        <td>#<?php echo $lp['id']; ?></td>
                                        <td>KSh <?php echo number_format($lp['amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo (strpos(strtoupper($lp['status']), 'COMPLETED') !== false || strpos(strtoupper($lp['status']), 'SUCCESS') !== false) ? 'status-active' : 'status-pending'; ?>" style="padding: 2px 8px; font-size: 0.75rem;">
                                                <?php echo htmlspecialchars($lp['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d', strtotime($lp['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($latestPayments)): ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center;">No payments yet.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- News -->
                <section id="news-tab" class="tab-content">
                    <h2>News Management</h2>
                    <div class="card">
                        <form id="news-form" class="admin-form">
                            <h3>Add New Article</h3>
                            <div class="form-group">
                                <label for="title">Title</label>
                                <input type="text" id="title" required>
                            </div>
                            <div class="form-group">
                                <label for="image">Image URL</label>
                                <div class="input-group">
                                    <input type="text" id="image" class="image-input" placeholder="Upload or enter image URL" required>
                                    <button type="button" class="btn btn-upload" data-target="image">Upload</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="summary">Summary</label>
                                <textarea id="summary" rows="3" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="content">Full Content</label>
                                <textarea id="content" rows="8" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" required>
                                    <option value="Transfers">Transfers</option>
                                    <option value="Academy">Academy</option>
                                    <option value="Community">Community</option>
                                    <option value="Interviews">Interviews</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn">Save Article</button>
                                <button type="button" class="btn btn-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Existing Articles</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Matches -->
                <section id="matches-tab" class="tab-content">
                    <h2>Matches Management</h2>
                    <div class="card">
                        <form id="match-form" class="admin-form">
                            <h3>Add New Match</h3>
                            <div class="form-group">
                                <label for="home-team">Home Team</label>
                                <input type="text" id="home-team" required>
                                <div class="form-group-sub" style="margin-top: 10px;">
                                    <label for="home-logo-upload">Home Logo</label>
                                    <div class="input-group">
                                        <input type="text" id="home-logo" class="image-input" placeholder="Logo URL (Auto-set on upload)">
                                        <button type="button" class="btn btn-upload" data-target="home-logo">Upload</button>
                                    </div>
                                    <div id="home-logo-preview" class="image-preview-container" style="margin-top: 5px; display: none;">
                                        <img src="" alt="Home Logo Preview" style="max-height: 50px;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="away-team">Away Team</label>
                                <input type="text" id="away-team" required>
                                <div class="form-group-sub" style="margin-top: 10px;">
                                    <label for="away-logo-upload">Away Logo</label>
                                    <div class="input-group">
                                        <input type="text" id="away-logo" class="image-input" placeholder="Logo URL (Auto-set on upload)">
                                        <button type="button" class="btn btn-upload" data-target="away-logo">Upload</button>
                                    </div>
                                    <div id="away-logo-preview" class="image-preview-container" style="margin-top: 5px; display: none;">
                                        <img src="" alt="Away Logo Preview" style="max-height: 50px;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="competition">Competition</label>
                                <input type="text" id="competition" required>
                            </div>
                            <div class="form-group">
                                <label for="venue">Venue</label>
                                <input type="text" id="venue" value="TBA" required>
                            </div>
                            <div class="form-group">
                                <label for="match-date">Date</label>
                                <input type="datetime-local" id="match-date" required>
                            </div>
                            <div class="form-group">
                                <label for="match-status">Status</label>
                                <select id="match-status">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="finished">Finished</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="home-score">Home Score</label>
                                <input type="number" id="home-score">
                            </div>
                            <div class="form-group">
                                <label for="away-score">Away Score</label>
                                <input type="number" id="away-score">
                            </div>
                            <div class="form-group full-width">
                                <label for="match-report">Match Report/Preview</label>
                                <textarea id="match-report" rows="10" placeholder="Enter the full match report or preview content here. HTML is allowed."></textarea>
                            </div>
                            <div class="form-row-group" style="display: flex; gap: 20px; flex-wrap: wrap;">
                                <div class="form-group" style="flex: 1; min-width: 300px;">
                                    <h4>Home Team Squad</h4>
                                    <label for="home-starting">Starting XI (One player per line)</label>
                                    <textarea id="home-starting" rows="12" placeholder="Player 1&#10;Player 2&#10;..."></textarea>
                                    
                                    <label for="home-subs" style="margin-top: 10px;">Substitutes (One player per line)</label>
                                    <textarea id="home-subs" rows="6" placeholder="Sub 1&#10;Sub 2&#10;..."></textarea>
                                </div>
                                
                                <div class="form-group" style="flex: 1; min-width: 300px;">
                                    <h4>Away Team Squad</h4>
                                    <label for="away-starting">Starting XI (One player per line)</label>
                                    <textarea id="away-starting" rows="12" placeholder="Player 1&#10;Player 2&#10;..."></textarea>
                                    
                                    <label for="away-subs" style="margin-top: 10px;">Substitutes (One player per line)</label>
                                    <textarea id="away-subs" rows="6" placeholder="Sub 1&#10;Sub 2&#10;..."></textarea>
                                </div>
                            </div>
                            <!-- Hidden fields to store the final JSON for API -->
                            <input type="hidden" id="home-lineup">
                            <input type="hidden" id="away-lineup">
                            <div class="form-actions">
                                <button type="submit" class="btn">Save Match</button>
                                <button type="button" class="btn btn-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Existing Matches</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Match</th>
                                    <th>Venue</th>
                                    <th>Competition</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Players -->
                <section id="players-tab" class="tab-content">
                    <h2>Players Management</h2>
                    <div class="card">
                        <form id="player-form" class="admin-form">
                            <h3>Add/Edit Player</h3>
                            <input type="hidden" id="player-id" name="id">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                            <div class="form-group">
                                <label for="player-position">Position</label>
                                <select id="player-position" name="position" required>
                                    <option value="Goalkeeper">Goalkeeper</option>
                                    <option value="Defender">Defender</option>
                                    <option value="Midfielder">Midfielder</option>
                                    <option value="Forward">Forward</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="player-number">Jersey Number</label>
                                <input type="number" id="player-number" name="jersey_number">
                            </div>
                            <div class="form-group">
                                <label for="player-nationality">Nationality</label>
                                <input type="text" id="player-nationality" name="nationality" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="player-dob">Date of Birth</label>
                                <input type="date" id="player-dob" name="dob" required>
                                <div class="error-message"></div>
                            </div>
                            <div class="form-group">
                                <label for="player-joined">Joined Club</label>
                                <input type="date" id="player-joined" name="joined">
                                <div class="error-message"></div>
                            </div>
                            
                            <h4>Season Statistics</h4>
                            <div class="form-row-group">
                                <div class="form-group">
                                    <label for="player-apps">Apps</label>
                                    <input type="number" id="player-apps" name="appearances" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="player-goals">Goals</label>
                                    <input type="number" id="player-goals" name="goals" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="player-assists">Assists</label>
                                    <input type="number" id="player-assists" name="assists" min="0" value="0">
                                </div>
                            </div>
                            <div class="form-row-group">
                                <div class="form-group">
                                    <label for="player-clean-sheets">Clean Sheets</label>
                                    <input type="number" id="player-clean-sheets" name="clean_sheets" min="0" value="0">
                                </div>
                                <div class="form-group">
                                    <label for="player-saves">Saves</label>
                                    <input type="number" id="player-saves" name="saves" min="0" value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="player-image-url">Player Image URL</label>
                                <div class="input-group">
                                    <input type="text" id="player-image-url" name="image_url" class="image-input" placeholder="Upload or enter image URL">
                                    <button type="button" class="btn btn-upload" data-target="player-image-url">Upload</button>
                                </div>
                            </div>
                            <div class="form-group full-width">
                                <label for="player-bio">Biography</label>
                                <textarea id="player-bio" name="bio" rows="5" placeholder="Enter a short biography for the player..."></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn">Save Player</button>
                                <button type="button" class="btn btn-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Existing Players</h3>
                        <table class="admin-table">
                            <thead id="existing-players-table-head">
                                <tr>
                                    <th>Name</th>
                                    <th>Position</th>
                                    <th>#</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Events Tab -->
<section id="events-tab" class="tab-content">
    <h2>Events Management</h2>
    
    <!-- Add Event Form -->
    <div class="card">
        <form id="event-form" class="admin-form">
            <h3 id="event-form-title">Add New Event</h3>
            <input type="hidden" id="event-id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="event-title">Event Title</label>
                    <input type="text" id="event-title" required>
                </div>
                <div class="form-group">
                    <label for="event-category">Category</label>
                    <select id="event-category" required>
                        <option value="match">Match</option>
                        <option value="training">Training</option>
                        <option value="meeting">Meeting</option>
                        <option value="social">Social</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="event-date">Date</label>
                    <input type="date" id="event-date" required>
                </div>
                <div class="form-group">
                    <label for="event-time">Time</label>
                    <input type="time" id="event-time" required>
                </div>
                <div class="form-group">
                    <label for="event-location">Location</label>
                    <input type="text" id="event-location" required>
                </div>
                <div class="form-group">
                    <label for="event-status">Status</label>
                    <select id="event-status" required>
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="event-description">Description</label>
                <textarea id="event-description" rows="4" placeholder="Describe the event..."></textarea>
            </div>
            
            <div class="form-group">
                <label for="event-image">Event Image URL</label>
                <div class="input-group">
                    <input type="text" id="event-image" class="image-input" placeholder="Upload or enter image URL" required>
                    <button type="button" class="btn btn-upload" data-target="event-image">Upload</button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-save">Save Event</button>
                <button type="button" class="btn btn-cancel" id="cancel-event-edit">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Events List -->
    <div class="card">
        <h3>Existing Events</h3>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Rows will be populated by JavaScript -->
            </tbody>
        </table>
    </div>

    <!-- Events Calendar View -->
    <div class="card">
        <h3>Calendar View</h3>
        <div class="calendar-header">
            <button id="prev-month" class="btn btn-icon"><i class="fas fa-chevron-left"></i></button>
            <h4 id="current-month">January 2024</h4>
            <button id="next-month" class="btn btn-icon"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div class="calendar" id="events-calendar">
            <!-- Calendar will be populated by JavaScript -->
        </div>
    </div>
</section>

                <!-- Ticketing Management -->
                <section id="ticketing-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>Ticketing Management</h2>
                        <p>Manage ticket types and pricing for each event.</p>
                    </div>

                    <div class="admin-grid">
                        <div class="card">
                            <h3>Add Ticket Type</h3>
                            <form id="ticket-type-form" class="admin-form">
                                <div class="form-group">
                                    <label for="ticket-event-id">Select Event</label>
                                    <select id="ticket-event-id" required>
                                        <option value="">-- Choose Event --</option>
                                        <!-- Populated by JS -->
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="ticket-name">Ticket Name</label>
                                    <input type="text" id="ticket-name" placeholder="e.g. Regular, VIP, VVIP" required>
                                </div>
                                <div class="form-group">
                                    <label for="ticket-price">Price (KES)</label>
                                    <input type="number" id="ticket-price" step="0.01" placeholder="0.00" required>
                                </div>
                                <div class="form-group">
                                    <label for="ticket-quantity">Max Quantity</label>
                                    <input type="number" id="ticket-quantity" placeholder="0 for unlimited" required>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Add Ticket Type</button>
                                </div>
                            </form>
                        </div>

                        <div class="card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3>Existing Ticket Types</h3>
                                <div class="filter-group" style="margin: 0;">
                                    <select id="filter-ticket-event">
                                        <option value="all">All Events</option>
                                        <!-- Populated by JS -->
                                    </select>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Event</th>
                                            <th>Type</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ticket-types-list">
                                        <!-- Populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Shop -->
                <section id="shop-tab" class="tab-content">
                    <h2>Shop Management</h2>
                   <div class="card">
                        <form id="shop-form" class="admin-form">
                            <h3>Add/Edit Product</h3>
                            <input type="hidden" id="product-id" name="id">
                            <div class="form-group">
                                <label for="product-name">Product Name</label>
                                <input type="text" id="product-name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="product-description">Description</label>
                                <textarea id="product-description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="product-price">Price</label>
                                <input type="number" step="0.01" id="product-price" name="price" required>
                            </div>
                            <div class="form-group">
                                <label for="product-stock">Stock</label>
                                <input type="number" id="product-stock" name="stock_quantity" required>
                            </div>
                            <div class="form-group">
                                <label for="product-category">Category</label>
                                <select id="product-category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="jerseys">Jerseys</option>
                                    <option value="apparel">Apparel</option>
                                    <option value="accessories">Accessories</option>
                                    <option value="sale">Sale</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="product-image-url">Product Image URL</label>
                                <div class="input-group">
                                    <input type="text" id="product-image-url" name="image_url" class="image-input" placeholder="Upload or enter image URL">
                                    <button type="button" class="btn btn-upload" data-target="product-image-url">Upload</button>
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn">Save Product</button>
                                <button type="button" class="btn btn-cancel">Cancel</button>
                            </div>
                        </form>
                    </div>
                    <div class="card">
                        <h3>Existing Products</h3>
                        <table id="existing-products-table" class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Official Partners -->
                <section id="partners-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>Official Partners Management</h2>
                        <p>Manage club sponsors and partners.</p>
                    </div>

                    <div class="admin-grid">
                        <div class="card">
                            <h3 id="partner-form-title">Add New Partner</h3>
                            <form id="partner-form" class="admin-form">
                                <input type="hidden" id="partner-id">
                                <div class="form-group">
                                    <label for="partner-name">Partner Name</label>
                                    <input type="text" id="partner-name" placeholder="e.g. EA Sports" required>
                                </div>
                                <div class="form-group">
                                    <label for="partner-category">Category / Title</label>
                                    <input type="text" id="partner-category" placeholder="e.g. Lead Partner" required>
                                </div>
                                <div class="form-group">
                                    <label for="partner-logo">Logo URL</label>
                                    <div class="input-group">
                                        <input type="text" id="partner-logo" class="image-input" placeholder="Upload or enter logo URL" required>
                                        <button type="button" class="btn btn-upload" data-target="partner-logo">Upload</button>
                                    </div>
                                    <div id="partner-logo-preview" class="image-preview-container" style="margin-top: 10px; display: none;">
                                        <img src="" alt="Preview" style="max-height: 80px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="partner-url">Website URL (Optional)</label>
                                    <input type="url" id="partner-url" placeholder="https://...">
                                </div>
                                <div class="form-group">
                                    <label for="partner-order">Display Order</label>
                                    <input type="number" id="partner-order" value="0" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="partner-status">Status</label>
                                    <select id="partner-status">
                                        <option value="Active">Active</option>
                                        <option value="Inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">Save Partner</button>
                                    <button type="button" class="btn btn-cancel" id="cancel-partner-edit" style="display: none;">Cancel</button>
                                </div>
                            </form>
                        </div>

                        <div class="card">
                            <h3>Existing Partners</h3>
                            <div class="table-container">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th>Logo</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Order</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="partners-list">
                                        <!-- Populated by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Media -->
                <section id="media-tab" class="tab-content">
                    <h2>Media Management</h2>
                    <div class="card">
                        <form id="media-form" class="admin-form">
                            <h3 id="media-form-title">Add New Media</h3>
                            <input type="hidden" id="media-id">
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="media-title">Title</label>
                                    <input type="text" id="media-title" required>
                                </div>
                                <div class="form-group">
                                    <label for="media-category">Category</label>
                                    <select id="media-category" required>
                                        <option value="photos">Photos</option>
                                        <option value="videos">Videos</option>
                                        <option value="matches">Match Highlights</option>
                                        <option value="training">Training</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="media-type">Media Type</label>
                                    <select id="media-type" required>
                                        <option value="photo">Photo</option>
                                        <option value="video">Video</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="media-date">Date</label>
                                    <input type="date" id="media-date" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="media-description">Description</label>
                                <textarea id="media-description" rows="3" placeholder="Describe the media..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="media-image-url">Image/Thumbnail URL</label>
                                <div class="input-group">
                                    <input type="text" id="media-image-url" class="image-input" placeholder="Upload or enter image URL" required>
                                    <button type="button" class="btn btn-upload" data-target="media-image-url">Upload</button>
                                </div>
                            </div>
                            
                            <div class="form-group" id="media-video-url-group" style="display: none;">
                                <label for="media-video-url">Video URL (YouTube, Vimeo, etc.)</label>
                                <input type="text" id="media-video-url" placeholder="Enter video URL (optional for photos)">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-save">Save Media</button>
                                <button type="button" class="btn btn-cancel" id="cancel-media-edit">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <h3>Existing Media</h3>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Messages Management -->
                <section id="messages-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>Contact Messages</h2>
                        <p>View and manage inquiries sent through the contact form.</p>
                    </div>

                    <div class="card">
                        <h3>All Messages</h3>
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Sender</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="admin-messages-list">
                                    <!-- Populated by JS -->
                                    <tr>
                                        <td colspan="5" style="text-align: center;">Loading messages...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- About Us Management -->
                <section id="about-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>About Us Management</h2>
                        <p>Update club history, mission, vision, and management team.</p>
                    </div>

                    <div class="card">
                        <h3>General About Content</h3>
                        <form id="about-general-form" class="admin-form">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="about_hero_title">Hero Title</label>
                                    <input type="text" id="about_hero_title" name="about_hero_title">
                                </div>
                                <div class="form-group">
                                    <label for="about_hero_subtitle">Hero Subtitle</label>
                                    <input type="text" id="about_hero_subtitle" name="about_hero_subtitle">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="about_history">Our History</label>
                                <textarea id="about_history" name="about_history" rows="6"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="about_history_image">History Image</label>
                                <div class="input-group">
                                    <input type="text" id="about_history_image" name="about_history_image" class="image-input" placeholder="Upload or enter image URL">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="about_mission">Our Mission</label>
                                    <textarea id="about_mission" name="about_mission" rows="4"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="about_vision">Our Vision</label>
                                    <textarea id="about_vision" name="about_vision" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="about_values">Our Values</label>
                                <textarea id="about_values" name="about_values" rows="3"></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-save">Save General Content</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin:0;">Club Achievements</h3>
                            <button class="btn btn-add" id="add-achievement-btn"><i class="fas fa-plus"></i> Add Achievement</button>
                        </div>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Icon</th>
                                    <th>Title</th>
                                    <th>Years</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="achievements-list">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>

                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin:0;">Management Team</h3>
                            <button class="btn btn-add" id="add-management-btn"><i class="fas fa-plus"></i> Add Member</button>
                        </div>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="management-list">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Contact Info Management -->
                <section id="contact-tab" class="tab-content">
                    <div class="tab-header">
                        <h2>Contact Info & FAQs</h2>
                        <p>Manage office details and frequently asked questions.</p>
                    </div>

                    <div class="card">
                        <h3>Office Details</h3>
                        <form id="contact-details-form" class="admin-form">
                            <div class="form-group">
                                <label for="contact_stadium_address">Stadium Address</label>
                                <textarea id="contact_stadium_address" name="contact_stadium_address" rows="2"></textarea>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="contact_office_phone">Main Office Phone</label>
                                    <input type="text" id="contact_office_phone" name="contact_office_phone">
                                </div>
                                <div class="form-group">
                                    <label for="contact_general_email">General Email</label>
                                    <input type="email" id="contact_general_email" name="contact_general_email">
                                </div>
                                <div class="form-group">
                                    <label for="contact_academy_phone">Academy Phone</label>
                                    <input type="text" id="contact_academy_phone" name="contact_academy_phone">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="contact_tickets_email">Tickets Email</label>
                                    <input type="email" id="contact_tickets_email" name="contact_tickets_email">
                                </div>
                                <div class="form-group">
                                    <label for="contact_partnerships_email">Partnerships Email</label>
                                    <input type="email" id="contact_partnerships_email" name="contact_partnerships_email">
                                </div>
                            </div>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="contact_office_hours">Office Hours</label>
                                    <textarea id="contact_office_hours" name="contact_office_hours" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label for="contact_ticketing_phone">Ticketing Phone</label>
                                    <input type="text" id="contact_ticketing_phone" name="contact_ticketing_phone">
                                </div>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-save">Save Contact Details</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h3 style="margin:0;">Frequently Asked Questions</h3>
                            <button class="btn btn-add" id="add-faq-btn"><i class="fas fa-plus"></i> Add FAQ</button>
                        </div>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Question</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="faqs-list">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- Settings -->
                <section id="settings-tab" class="tab-content">
                    <h2>Settings</h2>
                    <div class="card">
                        <h3>General Settings</h3>
                        <form class="admin-form">
                            <div class="form-group">
                                <label for="site-name">Site Name</label>
                                <input type="text" id="site-name" value="Mombasa Hamlets FC">
                            </div>
                            <div class="form-group">
                                <label for="site-description">Site Description</label>
                                <textarea id="site-description" rows="3">Professional Football Club</textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- Message Details Modal -->
    <div id="message-modal" class="admin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Message Details</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="message-meta">
                    <p><strong>From:</strong> <span id="msg-sender"></span></p>
                    <p><strong>Email:</strong> <span id="msg-email"></span></p>
                    <p><strong>Date:</strong> <span id="msg-date"></span></p>
                    <p><strong>Subject:</strong> <span id="msg-subject"></span></p>
                </div>
                <div class="message-body">
                    <p id="msg-content"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-cancel close-modal">Close</button>
            </div>
        </div>
    </div>

    <!-- Achievement Modal -->
    <div id="achievement-modal" class="admin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="achievement-modal-title">Add Achievement</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="achievement-form" class="admin-form">
                    <input type="hidden" id="achievement-id">
                    <div class="form-group">
                        <label for="achievement-title">Title</label>
                        <input type="text" id="achievement-title" required>
                    </div>
                    <div class="form-group">
                        <label for="achievement-years">Years (e.g., 2018, 2021)</label>
                        <input type="text" id="achievement-years" required>
                    </div>
                    <div class="form-group">
                        <label for="achievement-icon">FontAwesome Icon (e.g., fas fa-trophy)</label>
                        <input type="text" id="achievement-icon" value="fas fa-trophy">
                    </div>
                    <div class="form-group">
                        <label for="achievement-order">Display Order</label>
                        <input type="number" id="achievement-order" value="0">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">Save Achievement</button>
                        <button type="button" class="btn btn-cancel close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Management Modal -->
    <div id="management-modal" class="admin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="management-modal-title">Add Team Member</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="management-form" class="admin-form">
                    <input type="hidden" id="management-id">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="management-name">Name</label>
                            <input type="text" id="management-name" required>
                        </div>
                        <div class="form-group">
                            <label for="management-role">Role</label>
                            <input type="text" id="management-role" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="management-bio">Short Bio</label>
                        <textarea id="management-bio" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="management-image">Image URL</label>
                        <div class="input-group">
                            <input type="text" id="management-image" class="image-input" placeholder="Upload or enter image URL">
                            <button type="button" class="btn btn-upload" data-target="management-image">Upload</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="management-order">Display Order</label>
                        <input type="number" id="management-order" value="0">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">Save Member</button>
                        <button type="button" class="btn btn-cancel close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- FAQ Modal -->
    <div id="faq-modal" class="admin-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="faq-modal-title">Add FAQ</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="faq-form" class="admin-form">
                    <input type="hidden" id="faq-id">
                    <div class="form-group">
                        <label for="faq-question">Question</label>
                        <input type="text" id="faq-question" required>
                    </div>
                    <div class="form-group">
                        <label for="faq-answer">Answer</label>
                        <textarea id="faq-answer" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="faq-order">Display Order</label>
                        <input type="number" id="faq-order" value="0">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-save">Save FAQ</button>
                        <button type="button" class="btn btn-cancel close-modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/api-config.js"></script>
    <script src="js/admin-image-picker.js"></script>
    <script type="module" src="admin.js"></script>
</body>
</html>