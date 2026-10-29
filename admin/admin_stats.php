<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("Database connection failed"); }

/* TOTAL EVENTS */
$event_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM events");
$event_d = mysqli_fetch_assoc($event_q);

/* ACTIVE EVENTS */
$active_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM events WHERE status='active'");
$active_d = mysqli_fetch_assoc($active_q);

/* TOTAL USERS */
$user_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM users");
$user_d = mysqli_fetch_assoc($user_q);

/* TOTAL BOOKINGS */
$book_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM bookings");
$book_d = mysqli_fetch_assoc($book_q);

/* TOTAL REVENUE */
$rev_q = mysqli_query($conn,"SELECT SUM(total_amount) AS revenue FROM bookings WHERE payment_status='Paid'");
$rev_d = mysqli_fetch_assoc($rev_q);

/* RECENT BOOKINGS */
$recent_bookings_q = mysqli_query($conn,
    "SELECT b.*, u.name AS user_name, e.title AS event_title 
     FROM bookings b 
     JOIN users u ON b.user_id = u.id 
     JOIN events e ON b.event_id = e.event_id 
     ORDER BY b.booking_id DESC LIMIT 5"
);

/* UPCOMING EVENTS */
$upcoming_q = mysqli_query($conn,
    "SELECT * FROM events WHERE event_date >= CURDATE() AND status='active' ORDER BY event_date ASC LIMIT 4"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | EventBook</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(ellipse at 10% 80%, rgba(157, 78, 221, 0.12), transparent 40%),
                radial-gradient(ellipse at 90% 10%, rgba(79, 172, 254, 0.08), transparent 40%);
            background-attachment: fixed;
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
        }
        
        h1,h2,h3,h4,h5,h6 { font-family: 'Poppins', sans-serif; }

        /* ─── SIDEBAR ─── */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(157, 78, 221, 0.12);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
        }

        .sidebar-logo {
            padding: 28px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-logo h4 {
            font-weight: 800;
            font-size: 1.4rem;
            color: #fff;
            margin: 0;
        }

        .sidebar-logo h4 span { color: var(--primary-accent); }

        .sidebar-logo .sub {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 4px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
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
            gap: 12px;
            padding: 12px 25px;
            color: var(--text-muted);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .nav-item-custom i { font-size: 1.15rem; width: 22px; text-align: center; }

        .nav-item-custom:hover, .nav-item-custom.active {
            color: var(--text-light);
            background: rgba(157, 78, 221, 0.12);
            border-left-color: var(--primary-accent);
        }

        .nav-item-custom.active {
            color: var(--secondary-accent);
        }

        .nav-item-custom.danger:hover {
            color: var(--danger);
            background: rgba(255, 107, 107, 0.08);
            border-left-color: var(--danger);
        }

        .sidebar-footer {
            padding: 20px 25px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .admin-badge {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
        }

        .admin-info .name { font-weight: 700; font-size: 0.9rem; color: #fff; }
        .admin-info .role { font-size: 0.75rem; color: var(--text-muted); }

        /* ─── MAIN CONTENT ─── */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        /* Topbar */
        .topbar {
            padding: 20px 35px;
            background: rgba(11, 5, 18, 0.6);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            backdrop-filter: blur(10px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky; top: 0;
            z-index: 50;
        }

        .topbar-left h2 {
            font-weight: 700; font-size: 1.3rem; color: #fff; margin: 0;
        }

        .topbar-left .breadcrumb-text {
            font-size: 0.8rem; color: var(--text-muted); margin-top: 2px;
        }

        .topbar-actions { display: flex; gap: 10px; align-items: center; }

        .topbar-btn {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: white; border: none;
            border-radius: 10px;
            padding: 9px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s;
            display: flex; align-items: center; gap: 8px;
            box-shadow: 0 5px 15px rgba(157, 78, 221, 0.3);
        }

        .topbar-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.5);
            color: white;
        }

        .topbar-btn.green {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.3);
        }

        .topbar-btn.green:hover { box-shadow: 0 8px 20px rgba(17, 153, 142, 0.5); }

        /* Dashboard body */
        .dashboard-body {
            padding: 30px 35px 50px;
        }

        /* ─── STAT CARDS ─── */
        .stat-card {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            padding: 28px;
            backdrop-filter: blur(12px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease forwards;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -30px; right: -30px;
            width: 120px; height: 120px;
            border-radius: 50%;
            opacity: 0.07;
            transition: all 0.5s ease;
        }
        .stat-card.events::after { background: var(--primary-accent); }
        .stat-card.users::after { background: var(--info); }
        .stat-card.bookings::after { background: var(--warning); }
        .stat-card.revenue::after { background: var(--success); }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(157, 78, 221, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 25px rgba(157, 78, 221, 0.15);
        }

        .stat-card:hover::after { transform: scale(1.5); opacity: 0.12; }

        .stat-icon {
            width: 55px; height: 55px;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .icon-events { background: linear-gradient(135deg, #FF6B6B30, #C0392B30); color: #FF6B6B; border: 1px solid rgba(255,107,107,0.3); }
        .icon-users  { background: linear-gradient(135deg, #4facfe30, #00f2fe30); color: #4facfe; border: 1px solid rgba(79,172,254,0.3); }
        .icon-bookings { background: linear-gradient(135deg, #f6d36530, #fda08530); color: #fda085; border: 1px solid rgba(253,160,133,0.3); }
        .icon-revenue { background: linear-gradient(135deg, #43e97b30, #38f9d730); color: #38ef7d; border: 1px solid rgba(56,239,125,0.3); }

        .stat-value {
            font-size: 2.4rem; font-weight: 800;
            color: #fff; font-family: 'Poppins', sans-serif;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.85rem; color: var(--text-muted);
            margin-top: 8px; font-weight: 500;
            text-transform: uppercase; letter-spacing: 1px;
        }

        .stat-change {
            font-size: 0.8rem; font-weight: 600;
            margin-top: 14px;
            display: flex; align-items: center; gap: 5px;
        }

        .stat-change.up { color: var(--success); }
        .stat-change.neutral { color: var(--text-muted); }

        /* ─── SECTION HEADERS ─── */
        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }

        .section-header h5 {
            font-weight: 700; font-size: 1.1rem; color: #fff; margin: 0;
        }

        .view-all {
            font-size: 0.85rem; color: var(--primary-accent);
            text-decoration: none; font-weight: 600;
            transition: 0.3s;
        }

        .view-all:hover { color: var(--primary-hover); }

        /* ─── GLASS CARD ─── */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            overflow: hidden;
            animation: fadeInUp 0.8s ease forwards;
        }

        /* ─── BOOKINGS TABLE ─── */
        .bookings-table { width: 100%; border-collapse: collapse; }
        .bookings-table th {
            font-size: 0.78rem; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-muted);
            padding: 14px 20px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .bookings-table td {
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            font-size: 0.9rem;
            vertical-align: middle;
        }
        .bookings-table tr:last-child td { border-bottom: none; }
        .bookings-table tr:hover td { background: rgba(157,78,221,0.05); }

        .pay-badge {
            padding: 4px 12px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600;
        }
        .pay-paid { background: rgba(56,239,125,0.15); color: #38ef7d; border: 1px solid rgba(56,239,125,0.3); }
        .pay-pending { background: rgba(253,160,133,0.15); color: #fda085; border: 1px solid rgba(253,160,133,0.3); }

        /* ─── UPCOMING EVENT CARDS ─── */
        .upcoming-event {
            display: flex; align-items: center; gap: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: 0.3s;
        }

        .upcoming-event:last-child { border-bottom: none; }
        .upcoming-event:hover { background: rgba(157,78,221,0.05); }

        .upcoming-thumb {
            width: 60px; height: 45px;
            border-radius: 10px; object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }

        .upcoming-thumb-placeholder {
            width: 60px; height: 45px;
            border-radius: 10px;
            background: rgba(157,78,221,0.15);
            border: 1px solid rgba(157,78,221,0.3);
            display: flex; align-items: center; justify-content: center;
            color: var(--primary-accent); font-size: 1.2rem;
            flex-shrink: 0;
        }

        .upcoming-info .title { font-weight: 600; font-size: 0.9rem; color: #fff; }
        .upcoming-info .meta { font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; }

        .upcoming-date {
            margin-left: auto;
            text-align: right;
            flex-shrink: 0;
        }

        .upcoming-date .day {
            font-size: 1.4rem; font-weight: 800; color: var(--primary-accent);
            font-family: 'Poppins', sans-serif; line-height: 1;
        }

        .upcoming-date .month {
            font-size: 0.75rem; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* ─── QUICK ACTION CARDS ─── */
        .quick-action {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--text-light);
            transition: all 0.3s ease;
            display: block;
        }

        .quick-action:hover {
            transform: translateY(-5px);
            color: var(--text-light);
            border-color: rgba(157,78,221,0.4);
            box-shadow: 0 15px 30px rgba(0,0,0,0.4), 0 0 15px rgba(157,78,221,0.15);
        }

        .quick-action i {
            font-size: 2rem; display: block; margin-bottom: 10px;
        }

        .quick-action span { font-size: 0.85rem; font-weight: 600; letter-spacing: 0.5px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .delay-1 { animation-delay: 0.1s; animation-fill-mode: both; }
        .delay-2 { animation-delay: 0.2s; animation-fill-mode: both; }
        .delay-3 { animation-delay: 0.3s; animation-fill-mode: both; }
        .delay-4 { animation-delay: 0.4s; animation-fill-mode: both; }
        .delay-5 { animation-delay: 0.5s; animation-fill-mode: both; }
        .delay-6 { animation-delay: 0.6s; animation-fill-mode: both; }

        @media (max-width: 991px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <h4>Event<span>Book</span></h4>
        <p class="sub">Admin Panel</p>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main</div>
        <a href="admin_stats.php" class="nav-item-custom active">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>
        
        <div class="nav-section-title">Events</div>
        <a href="events.php" class="nav-item-custom">
            <i class="bi bi-calendar-event-fill"></i> Manage Events
        </a>
        <a href="add_event.php" class="nav-item-custom">
            <i class="bi bi-plus-circle-fill"></i> Add New Event
        </a>

        <div class="nav-section-title">System</div>
        <a href="admin_dashboard.php" class="nav-item-custom">
            <i class="bi bi-receipt-cutoff"></i> All Bookings
        </a>
        <a href="../index.php" target="_blank" class="nav-item-custom">
            <i class="bi bi-box-arrow-up-right"></i> View Website
        </a>
        <a href="../logout.php" class="nav-item-custom danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="admin-badge">
            <div class="admin-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="admin-info">
                <div class="name">Administrator</div>
                <div class="role">Super Admin</div>
            </div>
        </div>
    </div>
</aside>

<!-- ══ MAIN CONTENT ══ -->
<div class="main-content">
    
    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <h2>Dashboard Overview</h2>
            <div class="breadcrumb-text">Welcome back, Admin 👋 Here's what's happening today.</div>
        </div>
        <div class="topbar-actions">
            <a href="events.php" class="topbar-btn">
                <i class="bi bi-gear-fill"></i> Manage Events
            </a>
            <a href="add_event.php" class="topbar-btn green">
                <i class="bi bi-plus-lg"></i> Create Event
            </a>
        </div>
    </div>

    <!-- Dashboard Body -->
    <div class="dashboard-body">

        <!-- ─── STAT CARDS ─── -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card events delay-1">
                    <div class="stat-icon icon-events"><i class="bi bi-calendar-star"></i></div>
                    <div class="stat-value"><?php echo $event_d['total']; ?></div>
                    <div class="stat-label">Total Events</div>
                    <div class="stat-change up">
                        <i class="bi bi-lightning-fill"></i> 
                        <?php echo $active_d['total']; ?> Active Now
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card users delay-2">
                    <div class="stat-icon icon-users"><i class="bi bi-people-fill"></i></div>
                    <div class="stat-value"><?php echo $user_d['total']; ?></div>
                    <div class="stat-label">Registered Users</div>
                    <div class="stat-change up">
                        <i class="bi bi-person-check-fill"></i> Growing Community
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card bookings delay-3">
                    <div class="stat-icon icon-bookings"><i class="bi bi-ticket-detailed-fill"></i></div>
                    <div class="stat-value"><?php echo $book_d['total']; ?></div>
                    <div class="stat-label">Total Bookings</div>
                    <div class="stat-change neutral">
                        <i class="bi bi-arrow-right-circle-fill"></i> All Time
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card revenue delay-4">
                    <div class="stat-icon icon-revenue"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-value">₹<?php echo number_format($rev_d['revenue'] ?? 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-change up">
                        <i class="bi bi-graph-up-arrow"></i> Paid Bookings
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── QUICK ACTIONS ─── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <a href="add_event.php" class="quick-action delay-5">
                    <i class="bi bi-plus-circle-fill" style="color: #38ef7d;"></i>
                    <span>Add Event</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="events.php" class="quick-action delay-5">
                    <i class="bi bi-pencil-square" style="color: var(--primary-accent);"></i>
                    <span>Edit Events</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="admin_dashboard.php" class="quick-action delay-5">
                    <i class="bi bi-receipt-cutoff" style="color: var(--info);"></i>
                    <span>All Bookings</span>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="../index.php" target="_blank" class="quick-action delay-5">
                    <i class="bi bi-globe2" style="color: var(--warning);"></i>
                    <span>View Website</span>
                </a>
            </div>
        </div>

        <!-- ─── BOTTOM SECTION: Recent Bookings + Upcoming Events ─── -->
        <div class="row g-4">
            <!-- Recent Bookings -->
            <div class="col-lg-7 delay-6">
                <div class="section-header">
                    <h5><i class="bi bi-receipt me-2" style="color: var(--primary-accent);"></i>Recent Bookings</h5>
                    <a href="admin_dashboard.php" class="view-all">View All <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="glass-card">
                    <table class="bookings-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Event</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($recent_bookings_q && mysqli_num_rows($recent_bookings_q) > 0) {
                            while($b = mysqli_fetch_assoc($recent_bookings_q)) {
                                $pay_class = ($b['payment_status'] == 'Paid') ? 'pay-paid' : 'pay-pending';
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color:#fff;"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                </td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($b['event_title']); ?></td>
                                <td style="color: #38ef7d; font-weight: 700;">₹<?php echo number_format($b['total_amount'] ?? 0, 2); ?></td>
                                <td><span class="pay-badge <?php echo $pay_class; ?>"><?php echo htmlspecialchars($b['payment_status']); ?></span></td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding: 40px; color: var(--text-muted);">
                                    <i class="bi bi-inbox" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    No bookings yet
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="col-lg-5 delay-6">
                <div class="section-header">
                    <h5><i class="bi bi-calendar-check me-2" style="color: var(--info);"></i>Upcoming Events</h5>
                    <a href="events.php" class="view-all">Manage <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="glass-card">
                <?php
                if ($upcoming_q && mysqli_num_rows($upcoming_q) > 0) {
                    while($ev = mysqli_fetch_assoc($upcoming_q)) {
                        $day = date('d', strtotime($ev['event_date']));
                        $month = date('M', strtotime($ev['event_date']));
                ?>
                    <div class="upcoming-event">
                        <?php if (!empty($ev['image'])): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($ev['image']); ?>" 
                                 class="upcoming-thumb" alt="Event"
                                 onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'upcoming-thumb-placeholder\'><i class=\'bi bi-image\'></i></div>' + this.parentElement.innerHTML.replace(this.outerHTML, '');">
                        <?php else: ?>
                            <div class="upcoming-thumb-placeholder"><i class="bi bi-calendar-event"></i></div>
                        <?php endif; ?>
                        <div class="upcoming-info">
                            <div class="title"><?php echo htmlspecialchars($ev['title']); ?></div>
                            <div class="meta"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($ev['location']); ?></div>
                        </div>
                        <div class="upcoming-date">
                            <div class="day"><?php echo $day; ?></div>
                            <div class="month"><?php echo $month; ?></div>
                        </div>
                    </div>
                <?php
                    }
                } else {
                ?>
                    <div style="text-align:center; padding: 50px; color: var(--text-muted);">
                        <i class="bi bi-calendar-x" style="font-size:2.5rem; display:block; margin-bottom:10px;"></i>
                        No upcoming events
                    </div>
                <?php } ?>
                </div>
            </div>
        </div>

    </div><!-- /dashboard-body -->
</div><!-- /main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
