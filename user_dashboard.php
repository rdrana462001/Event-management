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

// Fetch user profile info
$u_res = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($u_res);
$user_name = $user_data['name'] ?? ($_SESSION['user_name'] ?? 'Attendee');
$user_email = $user_data['email'] ?? '';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Stats Queries
// 1. Total Tickets & Total Spent & Total Bookings
$stats_q = mysqli_query($conn, "SELECT 
    COALESCE(SUM(qty), 0) AS total_tickets, 
    COUNT(booking_id) AS total_orders, 
    COALESCE(SUM(total_amount), 0) AS total_spent 
    FROM bookings WHERE user_id = '$user_id'");
$stats_d = mysqli_fetch_assoc($stats_q);

// 2. Events Attending (Unique events booked)
$events_count_q = mysqli_query($conn, "SELECT COUNT(DISTINCT event_id) AS total_events FROM bookings WHERE user_id = '$user_id'");
$events_count_d = mysqli_fetch_assoc($events_count_q);

// 3. Upcoming Events Attending (date >= today)
$upcoming_count_q = mysqli_query($conn, "SELECT COUNT(DISTINCT b.event_id) AS upcoming_total 
    FROM bookings b 
    JOIN events e ON b.event_id = e.event_id 
    WHERE b.user_id = '$user_id' AND e.event_date >= CURDATE()");
$upcoming_count_d = mysqli_fetch_assoc($upcoming_count_q);

// 4. Upcoming events list
$upcoming_events_q = mysqli_query($conn, "SELECT b.*, e.title, e.event_date, e.event_time, e.location, e.image, e.price 
    FROM bookings b 
    JOIN events e ON b.event_id = e.event_id 
    WHERE b.user_id = '$user_id' AND e.event_date >= CURDATE() 
    ORDER BY e.event_date ASC LIMIT 4");

// 5. Recent Bookings for table
$recent_bookings_q = mysqli_query($conn, "SELECT b.*, e.title AS event_title, e.event_date, e.event_time, e.location, e.image 
    FROM bookings b 
    JOIN events e ON b.event_id = e.event_id 
    WHERE b.user_id = '$user_id' 
    ORDER BY b.booking_id DESC LIMIT 6");


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard | EventBook</title>
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

        .sidebar-logo a {
            text-decoration: none;
        }

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
            position: relative;
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

        .user-info-text {
            overflow: hidden;
        }

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
            transition: all 0.3s ease;
        }

        /* Topbar */
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

        .topbar-btn.green {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            box-shadow: 0 4px 15px rgba(17, 153, 142, 0.3);
            color: #0b0512;
            font-weight: 700;
        }

        .topbar-btn.green:hover {
            box-shadow: 0 8px 25px rgba(56, 239, 125, 0.4);
            color: #0b0512;
        }

        /* Dashboard Body */
        .dashboard-body {
            padding: 30px 35px 60px;
        }

        /* ─── WELCOME HERO BANNER ─── */
        .welcome-banner {
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.25) 0%, rgba(35, 17, 56, 0.5) 50%, rgba(79, 172, 254, 0.15) 100%);
            border: 1px solid rgba(157, 78, 221, 0.3);
            border-radius: 22px;
            padding: 30px 35px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(15px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            animation: fadeInUp 0.6s ease forwards;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(157, 78, 221, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
            pointer-events: none;
        }

        .welcome-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }

        .welcome-title span {
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            max-width: 650px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .banner-tags {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .banner-tag {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 0.8rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .banner-tag i { color: var(--secondary-accent); }

        /* ─── STAT CARDS ─── */
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 26px;
            backdrop-filter: blur(15px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease forwards;
            height: 100%;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -30px;
            right: -30px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            opacity: 0.08;
            transition: all 0.5s ease;
        }

        .stat-card.tickets::after { background: var(--primary-accent); }
        .stat-card.events::after { background: var(--info); }
        .stat-card.upcoming::after { background: var(--warning); }
        .stat-card.spent::after { background: var(--success); }

        .stat-card:hover {
            transform: translateY(-8px);
            border-color: rgba(157, 78, 221, 0.35);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 25px rgba(157, 78, 221, 0.18);
        }

        .stat-card:hover::after {
            transform: scale(1.6);
            opacity: 0.15;
        }

        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 18px;
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .icon-tickets {
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.25), rgba(123, 44, 191, 0.35));
            color: var(--secondary-accent);
            border: 1px solid rgba(157, 78, 221, 0.4);
        }

        .icon-events {
            background: linear-gradient(135deg, rgba(79, 172, 254, 0.2), rgba(0, 242, 254, 0.3));
            color: #4facfe;
            border: 1px solid rgba(79, 172, 254, 0.4);
        }

        .icon-upcoming {
            background: linear-gradient(135deg, rgba(246, 211, 101, 0.2), rgba(253, 160, 133, 0.3));
            color: #fda085;
            border: 1px solid rgba(253, 160, 133, 0.4);
        }

        .icon-spent {
            background: linear-gradient(135deg, rgba(67, 233, 123, 0.2), rgba(56, 249, 215, 0.3));
            color: #38ef7d;
            border: 1px solid rgba(56, 239, 125, 0.4);
        }

        .stat-value {
            font-size: 2.3rem;
            font-weight: 800;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            line-height: 1.1;
        }

        .stat-label {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 6px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-change {
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-change.up { color: var(--success); }
        .stat-change.info { color: var(--info); }
        .stat-change.warning { color: var(--warning); }
        .stat-change.accent { color: var(--secondary-accent); }

        /* ─── QUICK SHORTCUT CARDS ─── */
        .quick-action-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--text-light);
            transition: all 0.3s ease;
            backdrop-filter: blur(12px);
        }

        .quick-action-card:hover {
            transform: translateY(-4px);
            color: #fff;
            border-color: rgba(157, 78, 221, 0.4);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4), 0 0 15px rgba(157, 78, 221, 0.15);
        }

        .qa-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .quick-action-card:hover .qa-icon {
            transform: scale(1.12);
        }

        .qa-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: #fff;
            margin-bottom: 2px;
        }

        .qa-subtitle {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ─── GLASS CARDS & TABLES ─── */
        .glass-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 22px;
            backdrop-filter: blur(15px);
            overflow: hidden;
            animation: fadeInUp 0.8s ease forwards;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .glass-card-header {
            padding: 20px 25px;
            background: rgba(0, 0, 0, 0.25);
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .glass-card-header h5 {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .view-all-link {
            font-size: 0.85rem;
            color: var(--secondary-accent);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .view-all-link:hover {
            color: #fff;
            transform: translateX(3px);
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

        .event-meta-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .event-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.2), rgba(123, 44, 191, 0.4));
            border: 1px solid rgba(157, 78, 221, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary-accent);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .event-title-text {
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }

        .event-sub-text {
            font-size: 0.78rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
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
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-ticket-view:hover {
            background: var(--primary-accent);
            border-color: var(--primary-accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(157, 78, 221, 0.4);
            transform: translateY(-2px);
        }

        /* ─── UPCOMING EVENT LIST ─── */
        .upcoming-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-glass);
            transition: background 0.3s ease;
        }

        .upcoming-item:last-child { border-bottom: none; }
        .upcoming-item:hover { background: rgba(157, 78, 221, 0.08); }

        .upcoming-img-box {
            width: 62px;
            height: 52px;
            border-radius: 12px;
            overflow: hidden;
            flex-shrink: 0;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(157, 78, 221, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upcoming-img-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upcoming-content {
            flex: 1;
            min-width: 0;
        }

        .upcoming-title {
            font-weight: 700;
            font-size: 0.92rem;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .upcoming-meta {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .upcoming-date-box {
            text-align: right;
            flex-shrink: 0;
        }

        .upcoming-date-box .day {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--secondary-accent);
            font-family: 'Poppins', sans-serif;
            line-height: 1;
        }

        .upcoming-date-box .month {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
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

        /* ─── ANIMATIONS ─── */
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

        /* ─── RESPONSIVENESS ─── */
        @media (max-width: 991px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
                box-shadow: 0 0 50px rgba(0, 0, 0, 0.8);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-toggle {
                display: flex;
            }
            .topbar {
                padding: 15px 20px;
            }
            .dashboard-body {
                padding: 20px 15px 50px;
            }
            .welcome-title {
                font-size: 1.4rem;
            }
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
        <a href="user_dashboard.php" class="nav-item-custom active">
            <i class="bi bi-grid-1x2-fill"></i> Dashboard
        </a>

        <div class="nav-section-title">Events & Tickets</div>
        <a href="my_tickets.php" class="nav-item-custom">
            <i class="bi bi-ticket-detailed-fill"></i> My Tickets
            <span class="nav-badge"><?php echo (int)$stats_d['total_tickets']; ?></span>
        </a>
        <a href="index.php" class="nav-item-custom">
            <i class="bi bi-compass-fill"></i> Explore Events
        </a>

        <div class="nav-section-title">Account</div>
        <a href="javascript:void(0)" onclick="openProfileModal()" class="nav-item-custom">
            <i class="bi bi-person-badge-fill"></i> My Profile
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

    <!-- Topbar -->
    <div class="topbar">
        <div class="topbar-left">
            <button class="mobile-toggle" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div>
                <h2>
                    <i class="bi bi-speedometer2" style="color: var(--primary-accent);"></i>
                    User Dashboard
                </h2>
                <div class="breadcrumb-text">Manage your passes, discover concerts & conventions</div>
            </div>
        </div>

        <div class="topbar-actions">
            <a href="index.php" class="topbar-btn">
                <i class="bi bi-search"></i>
                <span class="d-none d-sm-inline">Find Events</span>
            </a>
            <a href="my_tickets.php" class="topbar-btn green">
                <i class="bi bi-ticket-detailed"></i>
                <span>My Tickets</span>
            </a>
        </div>
    </div>

    <!-- Dashboard Body -->
    <div class="dashboard-body">

        <!-- ─── WELCOME HERO BANNER ─── -->
        <div class="welcome-banner">
            <h1 class="welcome-title">
                Hello, <span><?php echo htmlspecialchars($user_name); ?></span>! 👋
            </h1>
            <p class="welcome-desc">
                Welcome to your interactive event dashboard. View your booked passes, track upcoming live events, and download digital tickets instantly anytime.
            </p>
            <div class="banner-tags">
                <div class="banner-tag">
                    <i class="bi bi-patch-check-fill"></i> Verified Member
                </div>
                <div class="banner-tag">
                    <i class="bi bi-shield-lock-fill"></i> Secure Digital Wallet
                </div>
                <div class="banner-tag">
                    <i class="bi bi-qr-code"></i> Instant QR Entry
                </div>
            </div>
        </div>

        <!-- ─── 4 STAT CARDS ─── -->
        <div class="row g-4 mb-4">
            <!-- 1. Total Tickets -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card tickets delay-1">
                    <div class="stat-icon icon-tickets">
                        <i class="bi bi-ticket-perforated-fill"></i>
                    </div>
                    <div class="stat-value"><?php echo (int)$stats_d['total_tickets']; ?></div>
                    <div class="stat-label">Total Tickets</div>
                    <div class="stat-change accent">
                        <i class="bi bi-check-circle-fill"></i> <?php echo (int)$stats_d['total_orders']; ?> Completed Orders
                    </div>
                </div>
            </div>

            <!-- 2. Events Attending -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card events delay-2">
                    <div class="stat-icon icon-events">
                        <i class="bi bi-calendar-check-fill"></i>
                    </div>
                    <div class="stat-value"><?php echo (int)$events_count_d['total_events']; ?></div>
                    <div class="stat-label">Events Booked</div>
                    <div class="stat-change info">
                        <i class="bi bi-stars"></i> Curated Experiences
                    </div>
                </div>
            </div>

            <!-- 3. Upcoming Events -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card upcoming delay-3">
                    <div class="stat-icon icon-upcoming">
                        <i class="bi bi-alarm-fill"></i>
                    </div>
                    <div class="stat-value"><?php echo (int)$upcoming_count_d['upcoming_total']; ?></div>
                    <div class="stat-label">Upcoming Events</div>
                    <div class="stat-change warning">
                        <i class="bi bi-calendar-event"></i> Soon on Schedule
                    </div>
                </div>
            </div>

            <!-- 4. Total Spent -->
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card spent delay-4">
                    <div class="stat-icon icon-spent">
                        <i class="bi bi-wallet-fill"></i>
                    </div>
                    <div class="stat-value">₹<?php echo number_format($stats_d['total_spent'] ?? 0); ?></div>
                    <div class="stat-label">Total Spent</div>
                    <div class="stat-change up">
                        <i class="bi bi-shield-check"></i> Safe Transactions
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── QUICK SHORTCUT ACTIONS ─── -->
        <div class="row g-3 mb-4 delay-5">
            <div class="col-6 col-lg-3">
                <a href="index.php" class="quick-action-card">
                    <div class="qa-icon" style="background: rgba(157, 78, 221, 0.2); color: var(--secondary-accent);">
                        <i class="bi bi-compass"></i>
                    </div>
                    <div>
                        <div class="qa-title">Browse Events</div>
                        <div class="qa-subtitle">Find what's next</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="my_tickets.php" class="quick-action-card">
                    <div class="qa-icon" style="background: rgba(56, 239, 125, 0.2); color: #38ef7d;">
                        <i class="bi bi-ticket-detailed"></i>
                    </div>
                    <div>
                        <div class="qa-title">All Tickets</div>
                        <div class="qa-subtitle">View booking table</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="javascript:void(0)" onclick="openProfileModal()" class="quick-action-card">
                    <div class="qa-icon" style="background: rgba(79, 172, 254, 0.2); color: #4facfe;">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <div class="qa-title">My Account</div>
                        <div class="qa-subtitle">Profile & security</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-lg-3">
                <a href="index.php#search-section" class="quick-action-card">
                    <div class="qa-icon" style="background: rgba(253, 160, 133, 0.2); color: #fda085;">
                        <i class="bi bi-search-heart"></i>
                    </div>
                    <div>
                        <div class="qa-title">Search Shows</div>
                        <div class="qa-subtitle">Filter by city/genre</div>
                    </div>
                </a>
            </div>
        </div>



        <!-- ─── RECENT BOOKINGS + UPCOMING EVENTS ─── -->
        <div class="row g-4 delay-6">

            <!-- Left Col: Recent Bookings Table -->
            <div class="col-lg-8">
                <div class="glass-card mb-0">
                    <div class="glass-card-header">
                        <h5>
                            <i class="bi bi-receipt" style="color: var(--primary-accent);"></i>
                            Recent Bookings
                        </h5>
                        <a href="my_tickets.php" class="view-all-link">
                            View All Tickets <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($recent_bookings_q && mysqli_num_rows($recent_bookings_q) > 0) {
                                while ($b = mysqli_fetch_assoc($recent_bookings_q)) {
                                    $is_paid = (strtolower($b['payment_status'] ?? '') == 'paid');
                                    $status_class = $is_paid ? 'paid' : 'pending';
                                    $status_icon = $is_paid ? 'bi-check-circle-fill' : 'bi-clock-fill';
                                    $event_date_formatted = !empty($b['event_date']) ? date("d M Y", strtotime($b['event_date'])) : 'TBA';
                            ?>
                                <tr>
                                    <td>
                                        <div class="event-meta-cell">
                                            <div class="event-avatar">
                                                <i class="bi bi-calendar-event"></i>
                                            </div>
                                            <div>
                                                <div class="event-title-text"><?php echo htmlspecialchars($b['event_title']); ?></div>
                                                <div class="event-sub-text">
                                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($b['location'] ?? 'Venue'); ?> • <?php echo $event_date_formatted; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-tickets">
                                            <?php echo htmlspecialchars($b['qty'] ?? 1); ?> Pass
                                        </span>
                                    </td>
                                    <td style="color: #38ef7d; font-weight: 700; font-size: 1rem;">
                                        ₹<?php echo number_format($b['total_amount'] ?? 0, 2); ?>
                                    </td>
                                    <td>
                                        <span class="badge-pay <?php echo $status_class; ?>">
                                            <i class="bi <?php echo $status_icon; ?>"></i>
                                            <?php echo htmlspecialchars($b['payment_status'] ?? 'Paid'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-ticket-view" onclick="showTicketModal(<?php 
                                            echo htmlspecialchars(json_encode([
                                                'booking_id' => $b['booking_id'],
                                                'title' => $b['event_title'],
                                                'date' => $event_date_formatted,
                                                'time' => $b['event_time'] ?? '07:00 PM',
                                                'location' => $b['location'] ?? 'Main Arena',
                                                'qty' => $b['qty'] ?? 1,
                                                'amount' => number_format($b['total_amount'] ?? 0, 2),
                                                'status' => $b['payment_status'] ?? 'Paid',
                                                'user_name' => $user_name
                                            ]), ENT_QUOTES, 'UTF-8');
                                        ?>)">
                                            <i class="bi bi-qr-code-scan"></i> Pass
                                        </button>
                                    </td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 50px 20px; color: var(--text-muted);">
                                        <i class="bi bi-ticket-perforated" style="font-size: 2.8rem; display: block; margin-bottom: 12px; opacity: 0.4;"></i>
                                        <h5 style="color: #fff; margin-bottom: 6px;">No bookings found yet</h5>
                                        <p style="font-size: 0.88rem; margin-bottom: 18px;">Ready for your next adventure? Find top events now!</p>
                                        <a href="index.php" class="topbar-btn d-inline-flex" style="padding: 8px 22px;">
                                            <i class="bi bi-compass"></i> Discover Events
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Col: Upcoming Attending Events -->
            <div class="col-lg-4">
                <div class="glass-card mb-0">
                    <div class="glass-card-header">
                        <h5>
                            <i class="bi bi-alarm" style="color: var(--warning);"></i>
                            Upcoming Schedule
                        </h5>
                        <a href="index.php" class="view-all-link">
                            More <i class="bi bi-plus-lg"></i>
                        </a>
                    </div>
                    <div>
                    <?php
                    if ($upcoming_events_q && mysqli_num_rows($upcoming_events_q) > 0) {
                        while ($ev = mysqli_fetch_assoc($upcoming_events_q)) {
                            $day = date('d', strtotime($ev['event_date']));
                            $month = date('M', strtotime($ev['event_date']));
                    ?>
                        <div class="upcoming-item">
                            <div class="upcoming-img-box">
                                <?php if (!empty($ev['image'])): ?>
                                    <img src="assets/images/<?php echo htmlspecialchars($ev['image']); ?>" 
                                         alt="Event"
                                         onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-calendar-star\' style=\'color: var(--secondary-accent); font-size: 1.4rem;\'></i>';">
                                <?php else: ?>
                                    <i class="bi bi-calendar-star" style="color: var(--secondary-accent); font-size: 1.4rem;"></i>
                                <?php endif; ?>
                            </div>
                            <div class="upcoming-content">
                                <div class="upcoming-title" title="<?php echo htmlspecialchars($ev['title']); ?>">
                                    <?php echo htmlspecialchars($ev['title']); ?>
                                </div>
                                <div class="upcoming-meta">
                                    <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($ev['location'] ?? 'Venue'); ?></span>
                                </div>
                            </div>
                            <div class="upcoming-date-box">
                                <div class="day"><?php echo $day; ?></div>
                                <div class="month"><?php echo $month; ?></div>
                            </div>
                        </div>
                    <?php
                        }
                    } else {
                    ?>
                        <div style="text-align: center; padding: 45px 20px; color: var(--text-muted);">
                            <i class="bi bi-calendar-x" style="font-size: 2.5rem; display: block; margin-bottom: 10px; opacity: 0.4;"></i>
                            <h6 style="color: #fff; margin-bottom: 4px;">No upcoming shows</h6>
                            <p style="font-size: 0.85rem; margin-bottom: 15px;">Book a ticket for an upcoming event!</p>
                            <a href="index.php" class="topbar-btn d-inline-flex" style="font-size: 0.82rem; padding: 6px 16px;">
                                Explore Shows
                            </a>
                        </div>
                    <?php } ?>
                    </div>
                </div>
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

<!-- ══ USER PROFILE MODAL ══ -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ticket-pass">
            <div class="ticket-header-gradient" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 style="color: #0b0512;"><i class="bi bi-person-circle me-2"></i>My Profile</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="ticket-body-content">
                <div class="text-center mb-4">
                    <div class="user-avatar-main mx-auto mb-3" style="width: 70px; height: 70px; font-size: 1.8rem;">
                        <?php echo $user_initial; ?>
                    </div>
                    <h4 class="text-white mb-1"><?php echo htmlspecialchars($user_name); ?></h4>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user_email); ?></p>
                </div>

                <div class="ticket-meta-grid">
                    <div class="ticket-meta-item">
                        <div class="label">User ID</div>
                        <div class="val">#USER-<?php echo htmlspecialchars($user_id); ?></div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Account Status</div>
                        <div class="val text-success">Active Member</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Total Passes</div>
                        <div class="val"><?php echo (int)$stats_d['total_tickets']; ?> Tickets</div>
                    </div>
                    <div class="ticket-meta-item">
                        <div class="label">Events Attended</div>
                        <div class="val"><?php echo (int)$events_count_d['total_events']; ?> Shows</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="my_tickets.php" class="topbar-btn justify-content-center">
                        <i class="bi bi-ticket-perforated"></i> View All My Tickets
                    </a>
                    <a href="logout.php" class="btn btn-outline-danger rounded-3 py-2">
                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mobile Sidebar Toggle
    function toggleSidebar() {
        const sidebar = document.getElementById('userSidebar');
        sidebar.classList.toggle('show');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.getElementById('userSidebar');
        const toggle = document.querySelector('.mobile-toggle');
        if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Digital Ticket Modal Handler
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

    // Profile Modal
    const profileModalInstance = new bootstrap.Modal(document.getElementById('profileModal'));
    function openProfileModal() {
        profileModalInstance.show();
    }
</script>
</body>
</html>
