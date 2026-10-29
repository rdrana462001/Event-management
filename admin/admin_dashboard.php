<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("DB error"); }

$sql = "SELECT b.*, u.name AS user_name, u.email AS user_email, e.title AS event_title 
        FROM bookings b 
        JOIN users u ON b.user_id = u.id 
        JOIN events e ON b.event_id = e.event_id 
        ORDER BY b.booking_id DESC";

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Bookings | Admin Panel</title>
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
            width: 260px; min-height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(157, 78, 221, 0.12);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0;
            z-index: 100; backdrop-filter: blur(20px);
        }

        .sidebar-logo { padding: 28px 25px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .sidebar-logo h4 { font-weight: 800; font-size: 1.4rem; color: #fff; margin: 0; }
        .sidebar-logo h4 span { color: var(--primary-accent); }
        .sidebar-logo .sub { font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; letter-spacing: 1px; text-transform: uppercase; }

        .sidebar-nav { padding: 20px 0; flex: 1; }

        .nav-section-title { font-size: 0.7rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: var(--text-muted); padding: 15px 25px 8px; }

        .nav-item-custom {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 25px; color: var(--text-muted);
            text-decoration: none; transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-size: 0.95rem; font-weight: 500;
        }

        .nav-item-custom i { font-size: 1.15rem; width: 22px; text-align: center; }

        .nav-item-custom:hover, .nav-item-custom.active {
            color: var(--text-light);
            background: rgba(157, 78, 221, 0.12);
            border-left-color: var(--primary-accent);
        }

        .nav-item-custom.active { color: var(--secondary-accent); }

        .nav-item-custom.danger:hover {
            color: var(--danger);
            background: rgba(255, 107, 107, 0.08);
            border-left-color: var(--danger);
        }

        .sidebar-footer { padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.05); }
        .admin-badge { display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-accent), #7b2cbf); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .admin-info .name { font-weight: 700; font-size: 0.9rem; color: #fff; }
        .admin-info .role { font-size: 0.75rem; color: var(--text-muted); }

        /* ─── MAIN CONTENT ─── */
        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; }

        .topbar {
            padding: 20px 35px;
            background: rgba(11, 5, 18, 0.6);
            border-bottom: 1px solid rgba(255,255,255,0.04);
            backdrop-filter: blur(10px);
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 50;
        }

        .topbar-left h2 { font-weight: 700; font-size: 1.3rem; color: #fff; margin: 0; }
        .topbar-left .breadcrumb-text { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }

        .dashboard-body { padding: 30px 35px 50px; animation: fadeInUp 0.7s ease forwards; }

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            overflow: hidden;
        }

        .table-card-header {
            padding: 20px 25px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex; justify-content: space-between; align-items: center;
        }

        .table-card-header h5 { font-weight: 700; color: #fff; margin: 0; }

        .bookings-table { width: 100%; border-collapse: collapse; }
        .bookings-table th {
            font-size: 0.78rem; font-weight: 600; letter-spacing: 1px;
            text-transform: uppercase; color: var(--text-muted);
            padding: 14px 20px;
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

        .pay-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .pay-paid { background: rgba(56,239,125,0.15); color: #38ef7d; border: 1px solid rgba(56,239,125,0.3); }
        .pay-pending { background: rgba(253,160,133,0.15); color: #fda085; border: 1px solid rgba(253,160,133,0.3); }

        .user-cell { display: flex; align-items: center; gap: 10px; }
        .user-dot {
            width: 34px; height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }
        .user-name { font-weight: 600; color: #fff; }
        .user-email { font-size: 0.78rem; color: var(--text-muted); }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }

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
        <a href="admin_stats.php" class="nav-item-custom">
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
        <a href="admin_dashboard.php" class="nav-item-custom active">
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

    <div class="topbar">
        <div class="topbar-left">
            <h2><i class="bi bi-receipt-cutoff me-2" style="color: var(--primary-accent);"></i>All Bookings</h2>
            <div class="breadcrumb-text">Full list of all ticket bookings on the platform.</div>
        </div>
    </div>

    <div class="dashboard-body">
        <div class="table-card">
            <div class="table-card-header">
                <h5>Booking Records</h5>
                <span style="font-size:0.85rem; color: var(--text-muted);">
                    <?php echo ($result) ? mysqli_num_rows($result) : 0; ?> total entries
                </span>
            </div>
            <div style="overflow-x: auto;">
                <table class="bookings-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Event</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php

                    if ($result && mysqli_num_rows($result) > 0) {
                        $i = 1;
                        while($b = mysqli_fetch_assoc($result)) {
                            $pay_class = ($b['payment_status'] == 'Paid') ? 'pay-paid' : 'pay-pending';
                            $initial = strtoupper(substr($b['user_name'], 0, 1));
                    ?>
                        <tr>
                            <td style="color: var(--text-muted); font-weight: 600;"><?php echo $i++; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-dot"><?php echo $initial; ?></div>
                                    <div>
                                        <div class="user-name"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                        <div class="user-email"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--text-muted);"><?php echo htmlspecialchars($b['event_title']); ?></td>
                            <td>
                                <span style="background: rgba(157,78,221,0.15); color: var(--secondary-accent); padding: 4px 10px; border-radius: 20px; font-size:0.85rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($b['num_tickets'] ?? $b['quantity'] ?? 1); ?> Tickets
                                </span>
                            </td>
                            <td style="color: #38ef7d; font-weight: 700; font-size: 1rem;">₹<?php echo number_format($b['total_amount'] ?? 0, 2); ?></td>
                            <td><span class="pay-badge <?php echo $pay_class; ?>"><?php echo htmlspecialchars($b['payment_status']); ?></span></td>
                        </tr>
                    <?php
                        
                        }
                    } else {

                    ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 60px; color: var(--text-muted);">
                                <i class="bi bi-inbox" style="font-size:3rem; display:block; margin-bottom:15px;"></i>
                                <h5 style="color:#fff;">No bookings found</h5>
                                <p style="margin-top:5px;">Bookings will appear here when users buy tickets.</p>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
