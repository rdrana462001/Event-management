<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("location:login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "event_db");
if (!$conn) {
    die("Database connection failed");
}

$user_id = $_SESSION['user_id'];

// Fetch user profile
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_res);
$user_name = $user_data['name'] ?? ($_SESSION['user_name'] ?? 'Attendee');
$user_email = $user_data['email'] ?? '';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Stats for user
$stats_q = mysqli_query($conn, "SELECT 
    COALESCE(SUM(qty), 0) AS total_tickets, 
    COUNT(booking_id) AS total_orders, 
    COALESCE(SUM(total_amount), 0) AS total_spent 
    FROM bookings WHERE user_id = '$user_id'");
$stats_d = mysqli_fetch_assoc($stats_q);

// Search & Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_search = "";
if (!empty($search)) {
    $where_search = " AND (events.title LIKE '%$search%' OR events.location LIKE '%$search%')";
}

/* Fetch bookings with event details */
$sql = "SELECT 
            bookings.booking_id,
            bookings.qty,
            bookings.total_amount,
            bookings.payment_status,
            bookings.booking_date,
            events.title,
            events.event_date,
            events.event_time,
            events.location,
            events.image
        FROM bookings
        JOIN events ON bookings.event_id = events.event_id
        WHERE bookings.user_id = '$user_id' $where_search
        ORDER BY bookings.booking_date DESC";

$result = mysqli_query($conn, $sql);
$total_rows = $result ? mysqli_num_rows($result) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets & Bookings | EventBook</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap');

        :root {
            --bg-dark: #0b0512;
            --bg-card: rgba(35, 17, 56, 0.45);
            --sidebar-bg: rgba(11, 5, 22, 0.95);
            --primary-accent: #9d4edd;
            --primary-hover: #c77dff;
            --secondary-accent: #e0aaff;
            --text-light: #f8f9fa;
            --text-muted: #8a8fa8;
            --success: #38ef7d;
            --danger: #ff6b6b;
            --info: #4facfe;
            --warning: #fda085;
            --border-glass: rgba(255, 255, 255, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(ellipse at 10% 80%, rgba(157, 78, 221, 0.15), transparent 45%),
                radial-gradient(ellipse at 90% 10%, rgba(79, 172, 254, 0.1), transparent 40%),
                radial-gradient(ellipse at 50% 50%, rgba(199, 125, 255, 0.04), transparent 50%);
            background-attachment: fixed;
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6 { font-family: 'Poppins', sans-serif; }

        /* ─── SIDEBAR ─── */
        .sidebar {
            width: 270px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(157, 78, 221, 0.15);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 1000;
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }

        .sidebar-logo {
            padding: 26px 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar-logo a { text-decoration: none; }

        .sidebar-logo h4 {
            font-weight: 800;
            font-size: 1.4rem;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-logo h4 span { color: var(--primary-accent); }

        .sidebar-logo .badge-role {
            font-size: 0.68rem;
            background: rgba(157, 78, 221, 0.2);
            color: var(--secondary-accent);
            border: 1px solid rgba(157, 78, 221, 0.4);
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 15px 25px 8px;
        }

        .nav-item-custom {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 13px 25px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-item-custom i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .nav-item-custom:hover, .nav-item-custom.active {
            color: var(--text-light);
            background: rgba(157, 78, 221, 0.12);
            border-left-color: var(--primary-accent);
        }

        .nav-item-custom:hover i, .nav-item-custom.active i {
            transform: scale(1.15);
            color: var(--secondary-accent);
        }

        .nav-item-custom.active {
            color: var(--secondary-accent);
            font-weight: 600;
        }

        .nav-item-custom.danger:hover {
            color: var(--danger);
            background: rgba(255, 107, 107, 0.08);
            border-left-color: var(--danger);
        }

        .nav-badge {
            margin-left: auto;
            background: rgba(157, 78, 221, 0.25);
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: 600;
        }

        .sidebar-footer {
            padding: 20px 22px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.2);
        }

        .user-badge-sidebar {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar-main {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            box-shadow: 0 4px 15px rgba(157, 78, 221, 0.4);
            flex-shrink: 0;
        }

        .user-info-text { overflow: hidden; }
        .user-info-text .name {
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-info-text .email {
            font-size: 0.75rem;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ─── MAIN CONTENT ─── */
        .main-content {
            margin-left: 270px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .topbar {
            padding: 18px 35px;
            background: rgba(11, 5, 18, 0.65);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mobile-toggle {
            display: none;
            background: rgba(157, 78, 221, 0.15);
            border: 1px solid rgba(157, 78, 221, 0.3);
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
        }

        .topbar-left h2 {
            font-weight: 800;
            font-size: 1.35rem;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .topbar-left .breadcrumb-text {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .topbar-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .topbar-btn {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 20px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(157, 78, 221, 0.3);
        }

        .topbar-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(157, 78, 221, 0.5);
            color: white;
        }

        .dashboard-body {
            padding: 30px 35px 60px;
        }

        /* ─── SUMMARY CHIPS ─── */
        .summary-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .summary-chip {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 14px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(12px);
        }

        .summary-chip i {
            font-size: 1.4rem;
        }

        .summary-chip .chip-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .summary-chip .chip-val {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        /* ─── GLASS TABLE CARD ─── */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 22px;
            backdrop-filter: blur(15px);
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.7s ease forwards;
        }

        .glass-card-header {
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.25);
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .glass-card-header h5 {
            font-weight: 700;
            font-size: 1.15rem;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 5px 15px;
            width: 280px;
        }

        .search-form input {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 0.88rem;
            outline: none;
            width: 100%;
        }

        .search-form input::placeholder { color: var(--text-muted); }
        .search-form button {
            background: none;
            border: none;
            color: var(--secondary-accent);
            cursor: pointer;
            padding: 0;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table th {
            font-size: 0.78rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 15px 22px;
            background: rgba(0, 0, 0, 0.15);
            border-bottom: 1px solid var(--border-glass);
        }

        .custom-table td {
            padding: 16px 22px;
            border-bottom: 1px solid var(--border-glass);
            font-size: 0.9rem;
            vertical-align: middle;
        }

        .custom-table tr:last-child td { border-bottom: none; }

        .custom-table tr {
            transition: background 0.2s ease;
        }

        .custom-table tr:hover td {
            background: rgba(157, 78, 221, 0.08);
        }

        .badge-tickets {
            background: rgba(157, 78, 221, 0.15);
            color: var(--secondary-accent);
            border: 1px solid rgba(157, 78, 221, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-pay {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-pay.paid {
            background: rgba(56, 239, 125, 0.15);
            color: #38ef7d;
            border: 1px solid rgba(56, 239, 125, 0.3);
        }

        .badge-pay.pending {
            background: rgba(253, 160, 133, 0.15);
            color: #fda085;
            border: 1px solid rgba(253, 160, 133, 0.3);
        }

        .btn-ticket-view {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(157, 78, 221, 0.3);
        }

        .btn-ticket-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(157, 78, 221, 0.5);
            color: #fff;
        }

        /* ─── DIGITAL TICKET MODAL STYLING ─── */
        .modal-content.ticket-pass {
            background: #140a22;
            border: 1px solid rgba(157, 78, 221, 0.4);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8), 0 0 40px rgba(157, 78, 221, 0.25);
            color: #fff;
            overflow: hidden;
        }

        .ticket-header-gradient {
            background: linear-gradient(135deg, #7b2cbf, #9d4edd, #4facfe);
            padding: 24px 30px;
            position: relative;
        }

        .ticket-header-gradient h4 {
            font-weight: 800;
            margin: 0;
            color: #fff;
            font-size: 1.4rem;
        }

        .ticket-header-gradient p {
            margin: 4px 0 0;
            font-size: 0.82rem;
            color: rgba(255, 255, 255, 0.85);
            letter-spacing: 0.5px;
        }

        .ticket-body-content {
            padding: 30px;
        }

        .ticket-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 16px;
            padding: 18px;
        }

        .ticket-meta-item .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 3px;
        }

        .ticket-meta-item .val {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
        }

        .ticket-perforation {
            height: 2px;
            border-top: 2px dashed rgba(255, 255, 255, 0.15);
            position: relative;
            margin: 25px 0;
        }

        .ticket-perforation::before, .ticket-perforation::after {
            content: '';
            position: absolute;
            top: -12px;
            width: 24px;
            height: 24px;
            background: #0b0512;
            border-radius: 50%;
        }

        .ticket-perforation::before { left: -42px; }
        .ticket-perforation::after { right: -42px; }

        .ticket-barcode-box {
            text-align: center;
            padding: 15px;
            background: #fff;
            border-radius: 14px;
            color: #0b0512;
        }

        .barcode-graphic {
            height: 48px;
            background: repeating-linear-gradient(
                90deg,
                #000,
                #000 3px,
                transparent 3px,
                transparent 6px,
                #000 6px,
                #000 10px,
                transparent 10px,
                transparent 12px
            );
            width: 100%;
            margin-bottom: 8px;
        }

        .barcode-digits {
            font-family: monospace;
            letter-spacing: 4px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle { display: flex; }
            .topbar { padding: 15px 20px; }
            .dashboard-body { padding: 20px 15px 50px; }
        }

        @media print {
            body * { visibility: hidden; }
            #ticketModalContent, #ticketModalContent * { visibility: visible; }
            #ticketModalContent { position: absolute; left: 0; top: 0; width: 100%; background: #fff !important; color: #000 !important; }
            .ticket-header-gradient { background: #333 !important; }
            .modal-footer { display: none !important; }
        }
    </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar" id="userSidebar">
    <div class="sidebar-logo">
        <a href="index.php">
            <h4><i class="bi bi-ticket-perforated-fill" style="color: var(--primary-accent);"></i> Event<span>Book</span></h4>
        </a>
        <span class="badge-role">User</span>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a href="user_dashboard.php" class="nav-item-custom">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="nav-section-title">Events & Tickets</div>
        <a href="my_tickets.php" class="nav-item-custom active">
            <i class="bi bi-ticket-detailed-fill"></i> My Tickets
            <span class="nav-badge"><?php echo (int)$stats_d['total_tickets']; ?></span>
        </a>
        <a href="index.php" class="nav-item-custom">
            <i class="bi bi-compass-fill"></i> Explore Events
        </a>

        <div class="nav-section-title">Account</div>
        <a href="user_dashboard.php" class="nav-item-custom">
            <i class="bi bi-speedometer2"></i> User Overview
        </a>
        <a href="index.php" class="nav-item-custom">
            <i class="bi bi-box-arrow-up-right"></i> View Website
        </a>
        <a href="logout.php" class="nav-item-custom danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-badge-sidebar">
            <div class="user-avatar-main"><?php echo $user_initial; ?></div>
            <div class="user-info-text">
                <div class="name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
            </div>
        </div>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<div class="main-content">

    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h2>
                    <i class="bi bi-ticket-detailed-fill" style="color: var(--primary-accent);"></i>
                    My Booked Tickets
                </h2>
                <div class="breadcrumb-text">Review booking records and download digital access passes</div>
            </div>
        </div>

        <div class="topbar-actions">
            <a href="user_dashboard.php" class="topbar-btn">
                <i class="bi bi-grid-1x2"></i>
                <span class="d-none d-sm-inline">Dashboard</span>
            </a>
            <a href="index.php" class="topbar-btn" style="background: linear-gradient(135deg, #11998e, #38ef7d); color: #0b0512;">
                <i class="bi bi-plus-lg"></i>
                <span>Book Events</span>
            </a>
        </div>
    </div>

    <div class="dashboard-body">

        <!-- Summary Bar -->
        <div class="summary-bar">
            <div class="summary-chip">
                <i class="bi bi-ticket-perforated-fill" style="color: var(--secondary-accent);"></i>
                <div>
                    <div class="chip-label">Total Tickets</div>
                    <div class="chip-val"><?php echo (int)$stats_d['total_tickets']; ?></div>
                </div>
            </div>
            <div class="summary-chip">
                <i class="bi bi-receipt" style="color: var(--info);"></i>
                <div>
                    <div class="chip-label">Total Orders</div>
                    <div class="chip-val"><?php echo (int)$stats_d['total_orders']; ?></div>
                </div>
            </div>
            <div class="summary-chip">
                <i class="bi bi-wallet2" style="color: var(--success);"></i>
                <div>
                    <div class="chip-label">Total Invested</div>
                    <div class="chip-val">₹<?php echo number_format($stats_d['total_spent'] ?? 0); ?></div>
                </div>
            </div>
        </div>

        <!-- Glass Table Card -->
        <div class="glass-card">
            <div class="glass-card-header">
                <h5>
                    <i class="bi bi-card-checklist" style="color: var(--primary-accent);"></i>
                    Booking History (<?php echo $total_rows; ?>)
                </h5>

                <form method="GET" action="my_tickets.php" class="search-form">
                    <input type="text" name="search" placeholder="Search by title or venue..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="bi bi-search"></i></button>
                    <?php if (!empty($search)): ?>
                        <a href="my_tickets.php" style="color: var(--danger); margin-left: 8px; text-decoration: none;"><i class="bi bi-x-circle"></i></a>
                    <?php endif; ?>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Event Details</th>
                            <th>Date & Time</th>
                            <th>Venue</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Booked Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($result && mysqli_num_rows($result) > 0) {
                        $i = 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                            $is_paid = (strtolower($row['payment_status'] ?? '') == 'paid');
                            $pay_class = $is_paid ? 'paid' : 'pending';
                            $pay_icon = $is_paid ? 'bi-check-circle-fill' : 'bi-clock-fill';
                            $event_date_formatted = !empty($row['event_date']) ? date("d M Y", strtotime($row['event_date'])) : 'TBA';
                            $booked_on = !empty($row['booking_date']) ? date("d M Y", strtotime($row['booking_date'])) : '-';
                    ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $i++; ?></td>
                            <td>
                                <div style="font-weight: 700; color: #fff; font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                </div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);">
                                    Order ID: #BK-<?php echo htmlspecialchars($row['booking_id']); ?>
                                </div>
                            </td>
                            <td>
                                <div style="color: #fff; font-weight: 500;"><?php echo $event_date_formatted; ?></div>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['event_time'] ?? '07:00 PM'); ?></div>
                            </td>
                            <td>
                                <div style="color: var(--text-muted); font-size: 0.88rem;">
                                    <i class="bi bi-geo-alt me-1 text-danger"></i><?php echo htmlspecialchars($row['location']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge-tickets">
                                    <?php echo htmlspecialchars($row['qty']); ?> Tickets
                                </span>
                            </td>
                            <td style="color: #38ef7d; font-weight: 700; font-size: 1rem;">
                                ₹<?php echo number_format($row['total_amount'], 2); ?>
                            </td>
                            <td>
                                <span class="badge-pay <?php echo $pay_class; ?>">
                                    <i class="bi <?php echo $pay_icon; ?>"></i>
                                    <?php echo htmlspecialchars($row['payment_status']); ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.82rem;">
                                <?php echo $booked_on; ?>
                            </td>
                            <td>
                                <button class="btn-ticket-view" onclick="showTicketModal(<?php 
                                    echo htmlspecialchars(json_encode([
                                        'booking_id' => $row['booking_id'],
                                        'title' => $row['title'],
                                        'date' => $event_date_formatted,
                                        'time' => $row['event_time'] ?? '07:00 PM',
                                        'location' => $row['location'],
                                        'qty' => $row['qty'],
                                        'amount' => number_format($row['total_amount'], 2),
                                        'status' => $row['payment_status'],
                                        'user_name' => $user_name
                                    ]), ENT_QUOTES, 'UTF-8');
                                ?>)">
                                    <i class="bi bi-qr-code"></i> View Pass
                                </button>
                            </td>
                        </tr>
                    <?php
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 60px 20px; color: var(--text-muted);">
                                <i class="bi bi-ticket-perforated" style="font-size: 3rem; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                                <h5 style="color: #fff; margin-bottom: 6px;">No bookings found</h5>
                                <p style="font-size: 0.9rem; margin-bottom: 20px;">
                                    <?php echo (!empty($search)) ? "No results matching '".htmlspecialchars($search)."'" : "You haven't booked any event tickets yet."; ?>
                                </p>
                                <a href="index.php" class="topbar-btn d-inline-flex">
                                    <i class="bi bi-compass"></i> Discover Live Events
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /dashboard-body -->
</div><!-- /main-content -->

<!-- ══ DIGITAL TICKET MODAL ══ -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ticket-pass" id="ticketModalContent">
            <div class="ticket-header-gradient">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="badge bg-white text-dark mb-2 px-2 py-1 font-monospace" id="modalBookingId">#BOOK-000</div>
                        <h4 id="modalEventTitle">Event Title</h4>
                        <p><i class="bi bi-check-circle-fill me-1 text-light"></i> Official Admission Ticket</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            
            <div class="ticket-body-content">
                <div class="ticket-meta-grid">
                    <div class="ticket-meta-item">
                        <div class="label">Attendee</div>
                        <div class="val" id="modalAttendee"><?php echo htmlspecialchars($user_name); ?></div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Quantity</div>
                        <div class="val" id="modalQty">1 Pass</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Event Date</div>
                        <div class="val" id="modalDate">-</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Event Time</div>
                        <div class="val" id="modalTime">-</div>
                    </div>
                    <div class="ticket-meta-item" style="grid-column: span 2;">
                        <div class="label">Venue Location</div>
                        <div class="val" id="modalLocation">-</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Total Paid</div>
                        <div class="val" style="color: #38ef7d;" id="modalAmount">₹0.00</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Payment Status</div>
                        <div class="val" id="modalStatus"><span class="badge bg-success">Paid</span></div>
                    </div>
                </div>

                <div class="ticket-perforation"></div>

                <div class="ticket-barcode-box">
                    <div class="barcode-graphic"></div>
                    <div class="barcode-digits" id="modalBarcode">9842 1093 4812</div>
                    <div style="font-size: 0.72rem; color: #666; margin-top: 4px;">Present this digital barcode or QR at the gate</div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="button" class="topbar-btn flex-fill justify-content-center" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print / Save PDF
                    </button>
                    <button type="button" class="btn btn-outline-light rounded-3 px-4" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('userSidebar');
        sidebar.classList.toggle('show');
    }

    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('userSidebar');
        const toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    const ticketModalInstance = new bootstrap.Modal(document.getElementById('ticketModal'));
    function showTicketModal(data) {
        document.getElementById('modalBookingId').innerText = '#BOOK-' + data.booking_id;
        document.getElementById('modalEventTitle').innerText = data.title;
        document.getElementById('modalAttendee').innerText = data.user_name;
        document.getElementById('modalQty').innerText = data.qty + ' Ticket(s)';
        document.getElementById('modalDate').innerText = data.date;
        document.getElementById('modalTime').innerText = data.time;
        document.getElementById('modalLocation').innerText = data.location;
        document.getElementById('modalAmount').innerText = '₹' + data.amount;
        document.getElementById('modalStatus').innerHTML = '<span class="badge bg-success">' + data.status + '</span>';
        document.getElementById('modalBarcode').innerText = 'EB-' + data.booking_id + '-' + Math.floor(1000 + Math.random() * 9000);
        ticketModalInstance.show();
    }
</script>
</body>
</html>
