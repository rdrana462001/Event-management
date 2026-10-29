<?php
error_reporting(0);
session_start();

// Prevent mysqli from throwing fatal exceptions on connection failure
mysqli_report(MYSQLI_REPORT_OFF);

// Database connection with graceful error handling
$conn = null;
$db_error = false;

try {
    $conn = new mysqli("localhost", "root", "", "event_db");
    if ($conn->connect_error) {
        $db_error = true;
    }
} catch (Exception $e) {
    $db_error = true;
}

// Show user-friendly offline page if DB is not reachable
if ($db_error) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Offline | EventBook</title>
    <style>
        body {
            margin: 0;
            background: #0b0512;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: sans-serif;
            color: #fff;
        }
        .box {
            border: 1px solid rgba(157, 78, 221, 0.4);
            border-radius: 16px;
            padding: 40px 50px;
            text-align: center;
            background: rgba(35, 17, 56, 0.5);
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .box h3 { margin: 0 0 10px; color: #e0aaff; }
        .box p { margin: 0; color: #8a8fa8; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="box">
        <h3>Database Offline</h3>
        <p>Please ensure MySQL is running in your XAMPP Control Panel.</p>
    </div>
</body>
</html>
<?php
    exit();
}

// Search and Category filtering
$search_query = "";
$filter_tag = isset($_GET['filter']) ? trim($_GET['filter']) : 'all';

$where_clauses = ["status='active'"];

if (isset($_GET['search']) && trim($_GET['search']) !== "") {
    $search_query = $conn->real_escape_string(trim($_GET['search']));
    $where_clauses[] = "(title LIKE '%$search_query%' OR location LIKE '%$search_query%' OR description LIKE '%$search_query%')";
}

if ($filter_tag !== 'all' && $filter_tag !== '') {
    $clean_filter = $conn->real_escape_string($filter_tag);
    $where_clauses[] = "(title LIKE '%$clean_filter%' OR description LIKE '%$clean_filter%' OR location LIKE '%$clean_filter%')";
}

$where_sql = implode(" AND ", $where_clauses);
$sql = "SELECT * FROM events WHERE $where_sql ORDER BY event_date ASC";
$result = $conn->query($sql);

// Quick platform metrics
$total_active_events = 0;
$count_res = $conn->query("SELECT COUNT(*) AS total FROM events WHERE status='active'");
if ($count_res) {
    $total_active_events = $count_res->fetch_assoc()['total'];
}

// User session info
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $_SESSION['user_name'] ?? 'Account';
$user_initial = strtoupper(substr($user_name, 0, 1));
$is_admin = isset($_SESSION['admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBook — Discover & Book Premier Live Events</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap');

        :root {
            --bg-dark: #0b0512;
            --bg-card: rgba(35, 17, 56, 0.45);
            --primary-accent: #9d4edd;
            --primary-hover: #c77dff;
            --secondary-accent: #e0aaff;
            --text-light: #f8f9fa;
            --text-muted: #8a8fa8;
            --success: #38ef7d;
            --border-glass: rgba(255, 255, 255, 0.07);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(ellipse at 15% 20%, rgba(157, 78, 221, 0.12), transparent 40%),
                radial-gradient(ellipse at 85% 60%, rgba(79, 172, 254, 0.08), transparent 40%),
                radial-gradient(ellipse at 50% 90%, rgba(199, 125, 255, 0.05), transparent 50%);
            background-attachment: fixed;
            color: var(--text-light);
            min-height: 100vh;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .brand-font {
            font-family: 'Poppins', sans-serif;
        }

        /* ─── NAVBAR ─── */
        .navbar {
            backdrop-filter: blur(15px);
            background: rgba(11, 5, 18, 0.82) !important;
            border-bottom: 1px solid rgba(157, 78, 221, 0.15);
            padding: 0.9rem 0;
            transition: all 0.3s ease;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.45rem;
            color: #fff;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-brand span { color: var(--primary-accent); }

        .nav-link {
            color: var(--text-muted) !important;
            font-weight: 500;
            font-size: 0.93rem;
            margin: 0 6px;
            padding: 6px 14px !important;
            border-radius: 10px;
            transition: all 0.25s ease;
        }

        .nav-link:hover, .nav-link.active {
            color: #fff !important;
            background: rgba(157, 78, 221, 0.12);
        }

        .btn-nav-auth {
            border-radius: 10px;
            padding: 7px 18px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-nav-login {
            color: #fff;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .btn-nav-login:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }

        .btn-nav-primary {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: #fff;
            border: 1px solid rgba(157, 78, 221, 0.3);
            box-shadow: 0 4px 12px rgba(157, 78, 221, 0.3);
        }

        .btn-nav-primary:hover {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(157, 78, 221, 0.5);
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(157, 78, 221, 0.15);
            border: 1px solid rgba(157, 78, 221, 0.3);
            padding: 4px 12px 4px 5px;
            border-radius: 30px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.88rem;
            transition: all 0.25s ease;
        }

        .user-chip:hover {
            background: rgba(157, 78, 221, 0.25);
            color: #fff;
            transform: translateY(-1px);
        }

        .user-avatar-mini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.82rem;
            font-weight: 700;
        }

        /* ─── HERO SECTION ─── */
        .hero {
            padding: 85px 20px 60px;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(157, 78, 221, 0.15);
            border: 1px solid rgba(157, 78, 221, 0.3);
            color: var(--secondary-accent);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 24px;
            animation: fadeIn 0.6s ease forwards;
        }

        .hero h1 {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #ffffff 40%, var(--secondary-accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1.22;
            letter-spacing: -0.5px;
            animation: fadeIn 0.7s ease forwards;
        }

        .hero p.lead-text {
            font-size: 1.1rem;
            color: var(--text-muted);
            max-width: 660px;
            margin: 0 auto 36px;
            font-weight: 400;
            line-height: 1.6;
            animation: fadeIn 0.8s ease forwards;
        }

        /* ─── SEARCH BOX ─── */
        .search-box-wrap {
            max-width: 680px;
            margin: 0 auto 28px;
            animation: fadeIn 0.85s ease forwards;
        }

        .search-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 50px;
            padding: 6px;
            display: flex;
            align-items: center;
            backdrop-filter: blur(12px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), 0 0 20px rgba(157, 78, 221, 0.1);
            transition: all 0.3s ease;
        }

        .search-card:focus-within {
            border-color: var(--primary-accent);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 25px rgba(157, 78, 221, 0.25);
            background: rgba(255, 255, 255, 0.07);
        }

        .search-icon-box {
            padding-left: 20px;
            color: var(--secondary-accent);
            font-size: 1.15rem;
        }

        .search-input-field {
            flex: 1;
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            padding: 12px 16px;
            font-size: 0.98rem;
        }

        .search-input-field::placeholder {
            color: rgba(255, 255, 255, 0.45);
        }

        .search-submit-btn {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: #fff;
            border: none;
            border-radius: 40px;
            padding: 11px 26px;
            font-weight: 600;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(157, 78, 221, 0.3);
        }

        .search-submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(157, 78, 221, 0.5);
        }

        /* ─── CATEGORY FILTER PILLS ─── */
        .category-pills {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .filter-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: var(--text-muted);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .filter-pill:hover, .filter-pill.active {
            background: rgba(157, 78, 221, 0.2);
            border-color: rgba(157, 78, 221, 0.4);
            color: #fff;
        }

        .filter-pill.active {
            color: var(--secondary-accent);
            font-weight: 600;
        }

        /* ─── TRUST & VALUE STATS BAR ─── */
        .features-bar {
            margin-top: 40px;
            margin-bottom: 60px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 18px;
            padding: 22px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(12px);
            transition: transform 0.3s ease, border-color 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: rgba(157, 78, 221, 0.3);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        .feature-text h6 {
            font-size: 0.95rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 3px;
        }

        .feature-text p {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* ─── SECTION HEADER ─── */
        .section-header-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 35px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin: 0;
        }

        /* ─── EVENT CARDS ─── */
        .event-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(12px);
            transition: transform 0.35s cubic-bezier(0.165, 0.84, 0.44, 1), box-shadow 0.35s ease, border-color 0.35s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .event-card:hover {
            transform: translateY(-6px);
            border-color: rgba(157, 78, 221, 0.35);
            box-shadow: 0 16px 35px rgba(0, 0, 0, 0.5), 0 0 20px rgba(157, 78, 221, 0.15);
        }

        .card-img-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
            background: rgba(157, 78, 221, 0.1);
        }

        .card-img-wrapper img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .event-card:hover img {
            transform: scale(1.05);
        }

        .event-date-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            background: rgba(11, 5, 22, 0.85);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 6px 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
            z-index: 2;
        }

        .event-date-badge .day {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            line-height: 1;
            font-family: 'Poppins', sans-serif;
        }

        .event-date-badge .month {
            font-size: 0.68rem;
            color: var(--secondary-accent);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .event-live-pill {
            position: absolute;
            top: 14px;
            right: 14px;
            background: rgba(56, 239, 125, 0.18);
            border: 1px solid rgba(56, 239, 125, 0.35);
            color: #38ef7d;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
            backdrop-filter: blur(8px);
            z-index: 2;
        }

        .live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #38ef7d;
            box-shadow: 0 0 8px #38ef7d;
        }

        .card-body-custom {
            padding: 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .event-card-title {
            font-weight: 700;
            font-size: 1.18rem;
            color: #fff;
            margin-bottom: 12px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 2.8em;
        }

        .event-meta-list {
            margin-bottom: 20px;
        }

        .event-meta-row {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .event-meta-row i {
            color: var(--primary-accent);
            width: 20px;
            font-size: 0.95rem;
            margin-right: 6px;
        }

        .card-bottom-row {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .price-label-box .prefix {
            font-size: 0.72rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
        }

        .price-label-box .price-amount {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }

        .price-label-box .price-amount span {
            color: var(--primary-accent);
            font-weight: 700;
            font-size: 0.95rem;
            margin-right: 2px;
        }

        .btn-card-book {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 9px 18px;
            font-weight: 600;
            font-size: 0.88rem;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(157, 78, 221, 0.3);
        }

        .btn-card-book:hover {
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            box-shadow: 0 6px 18px rgba(157, 78, 221, 0.5);
            color: white;
            transform: translateY(-2px);
        }

        /* ─── HOW IT WORKS SECTION ─── */
        .how-section {
            padding: 80px 0;
            margin-top: 40px;
            border-top: 1px solid var(--border-glass);
        }

        .step-card {
            background: var(--bg-card);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 30px 25px;
            text-align: center;
            backdrop-filter: blur(12px);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .step-card:hover {
            transform: translateY(-5px);
            border-color: rgba(157, 78, 221, 0.3);
        }

        .step-num {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: #fff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
            margin: 0 auto 18px;
            box-shadow: 0 4px 15px rgba(157, 78, 221, 0.35);
        }

        .step-card h5 {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .step-card p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.55;
            margin: 0;
        }

        /* ─── CTA BANNER ─── */
        .cta-banner {
            background: linear-gradient(135deg, rgba(157, 78, 221, 0.25) 0%, rgba(35, 17, 56, 0.55) 50%, rgba(79, 172, 254, 0.15) 100%);
            border: 1px solid rgba(157, 78, 221, 0.3);
            border-radius: 24px;
            padding: 45px 35px;
            margin: 50px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(15px);
        }

        .cta-banner h2 {
            font-weight: 800;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 12px;
        }

        .cta-banner p {
            color: var(--text-muted);
            max-width: 580px;
            margin: 0 auto 25px;
            font-size: 0.98rem;
        }

        /* ─── FOOTER ─── */
        footer {
            background: rgba(9, 4, 15, 0.95);
            border-top: 1px solid var(--border-glass);
            padding: 60px 0 30px;
        }

        .footer-brand h4 {
            font-weight: 800;
            font-size: 1.4rem;
            color: #fff;
            margin-bottom: 12px;
        }

        .footer-brand p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.6;
            max-width: 320px;
        }

        .footer-col h6 {
            color: #fff;
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.88rem;
            transition: all 0.2s ease;
        }

        .footer-links a:hover {
            color: var(--secondary-accent);
            padding-left: 3px;
        }

        .footer-bottom {
            padding-top: 30px;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* ─── SUBTLE / MEDIUM ANIMATIONS ─── */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }

        @media (max-width: 991px) {
            .hero h1 { font-size: 2.3rem; }
            .hero p.lead-text { font-size: 1rem; }
            .cta-banner { padding: 35px 20px; }
            .cta-banner h2 { font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-ticket-perforated-fill" style="color: var(--primary-accent);"></i>
            Event<span>Book</span>
        </a>

        <button class="navbar-toggler border-0 shadow-none text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-label="Toggle navigation">
            <i class="bi bi-list fs-1 text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#events">Explore Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#how-it-works">How It Works</a>
                </li>
                <?php if ($is_logged_in) { ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user_dashboard.php">
                            <i class="bi bi-grid-1x2 me-1" style="color: var(--secondary-accent);"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my_tickets.php">My Tickets</a>
                    </li>
                <?php } ?>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if ($is_logged_in) { ?>
                    <a href="user_dashboard.php" class="user-chip">
                        <div class="user-avatar-mini"><?php echo $user_initial; ?></div>
                        <span><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="bi bi-speedometer2 ms-1 text-muted"></i>
                    </a>
                    <a href="logout.php" class="btn-nav-auth btn-nav-login text-danger" title="Logout">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                <?php } else { ?>
                    <a href="login.php" class="btn-nav-auth btn-nav-login">
                        <i class="bi bi-person"></i> Login
                    </a>
                    <a href="register.php" class="btn-nav-auth btn-nav-primary">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                <?php } ?>

                <?php if ($is_admin) { ?>
                    <a href="admin/admin_stats.php" class="btn-nav-auth btn-nav-login ms-1" style="border-color: rgba(56, 239, 125, 0.4); color: #38ef7d;" title="Admin Dashboard">
                        <i class="bi bi-shield-lock-fill"></i> Admin
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
</nav>

<!-- ══ HERO SECTION ══ -->
<section class="hero">
    <div class="container">
        <div class="hero-badge">
            <i class="bi bi-stars"></i> Experience Unforgettable Moments
        </div>

        <h1>Discover & Book Premier Live Events</h1>

        <p class="lead-text">
            Explore live concerts, tech seminars, sports spectacles, and cultural festivals with official passes and instant digital entry.
        </p>

        <!-- Search Bar -->
        <div class="search-box-wrap" id="search-section">
            <form action="index.php#events" method="GET" class="search-card">
                <div class="search-icon-box">
                    <i class="bi bi-search"></i>
                </div>
                <input type="text" 
                       name="search" 
                       class="search-input-field" 
                       placeholder="Search by event title, artist, city..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" 
                       autocomplete="off">
                <button type="submit" class="search-submit-btn">
                    <span>Find Events</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Quick Category Filter Pills -->
            <div class="category-pills">
                <a href="index.php#events" class="filter-pill <?php echo ($filter_tag === 'all' && empty($search_query)) ? 'active' : ''; ?>">
                    <i class="bi bi-grid-fill"></i> All Events
                </a>
                <a href="index.php?filter=Music#events" class="filter-pill <?php echo ($filter_tag === 'Music') ? 'active' : ''; ?>">
                    <i class="bi bi-music-note-beamed"></i> Music & Concerts
                </a>
                <a href="index.php?filter=Seminar#events" class="filter-pill <?php echo ($filter_tag === 'Seminar') ? 'active' : ''; ?>">
                    <i class="bi bi-mic-fill"></i> Seminars & Talks
                </a>
                <a href="index.php?filter=Workshop#events" class="filter-pill <?php echo ($filter_tag === 'Workshop') ? 'active' : ''; ?>">
                    <i class="bi bi-laptop"></i> Workshops
                </a>
                <a href="index.php?filter=Festival#events" class="filter-pill <?php echo ($filter_tag === 'Festival') ? 'active' : ''; ?>">
                    <i class="bi bi-fire"></i> Festivals
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ══ FEATURES & TRUST STATS BAR ══ -->
<div class="container features-bar">
    <div class="row g-3">
        <div class="col-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(157, 78, 221, 0.2); color: var(--secondary-accent);">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <div class="feature-text">
                    <h6>Instant QR Passes</h6>
                    <p>Digital tickets straight to your phone</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(56, 239, 125, 0.2); color: #38ef7d;">
                    <i class="bi bi-shield-check"></i>
                </div>
                <div class="feature-text">
                    <h6>100% Verified</h6>
                    <p>Direct entry from trusted organizers</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(79, 172, 254, 0.2); color: #4facfe;">
                    <i class="bi bi-lightning-charge"></i>
                </div>
                <div class="feature-text">
                    <h6>Instant Booking</h6>
                    <p>Zero wait time, smooth checkout</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="feature-card">
                <div class="feature-icon" style="background: rgba(253, 160, 133, 0.2); color: #fda085;">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="feature-text">
                    <h6>24/7 Support</h6>
                    <p>Always here to help attendees</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══ EVENTS LISTING SECTION ══ -->
<section class="container" id="events" style="scroll-margin-top: 100px;">
    
    <div class="section-header-wrap">
        <div>
            <h2 class="section-title">
                <?php 
                    if (!empty($search_query)) {
                        echo "Search Results for \"" . htmlspecialchars($search_query) . "\"";
                    } elseif ($filter_tag !== 'all') {
                        echo htmlspecialchars($filter_tag) . " Events";
                    } else {
                        echo "Featured & Upcoming Events";
                    }
                ?>
            </h2>
            <p class="section-subtitle">
                <?php echo ($result) ? $result->num_rows : 0; ?> live events available right now
            </p>
        </div>

        <?php if (!empty($search_query) || $filter_tag !== 'all') { ?>
            <a href="index.php#events" class="btn-nav-auth btn-nav-login" style="font-size: 0.85rem;">
                <i class="bi bi-x-circle"></i> Clear Filters
            </a>
        <?php } ?>
    </div>

    <!-- Events Grid -->
    <div class="row g-4">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $ev_date = !empty($row['event_date']) ? strtotime($row['event_date']) : time();
                $day = date('d', $ev_date);
                $month = date('M', $ev_date);
                $full_date = date('d M Y', $ev_date);
                $event_time = !empty($row['event_time']) ? htmlspecialchars($row['event_time']) : '07:00 PM';
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="event-card">
                
                <!-- Card Image wrapper with aspect ratio -->
                <div class="card-img-wrapper">
                    <img src="assets/images/<?php echo htmlspecialchars($row['image']); ?>" 
                         alt="<?php echo htmlspecialchars($row['title']); ?>"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='https://placehold.co/600x400/231138/c77dff?text=Event+Pass';">
                    
                    <div class="event-date-badge">
                        <div class="day"><?php echo $day; ?></div>
                        <div class="month"><?php echo $month; ?></div>
                    </div>

                    <div class="event-live-pill">
                        <span class="live-dot"></span> Booking Open
                    </div>
                </div>

                <!-- Card Body -->
                <div class="card-body-custom">
                    <h5 class="event-card-title" title="<?php echo htmlspecialchars($row['title']); ?>">
                        <?php echo htmlspecialchars($row['title']); ?>
                    </h5>

                    <div class="event-meta-list">
                        <div class="event-meta-row">
                            <i class="bi bi-calendar-event"></i>
                            <span><?php echo $full_date; ?> • <?php echo $event_time; ?></span>
                        </div>
                        <div class="event-meta-row">
                            <i class="bi bi-geo-alt"></i>
                            <span><?php echo htmlspecialchars($row['location'] ?? 'Venue Arena'); ?></span>
                        </div>
                    </div>

                    <div class="card-bottom-row">
                        <div class="price-label-box">
                            <div class="prefix">Starting from</div>
                            <div class="price-amount">
                                <span>₹</span><?php echo number_format($row['price'], 2); ?>
                            </div>
                        </div>

                        <a href="book_ticket.php?event_id=<?php echo $row['event_id']; ?>" class="btn-card-book">
                            <span>Book Now</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
        <?php
            }
        } else {
        ?>
        <div class="col-12 text-center py-5">
            <div style="background: rgba(255, 255, 255, 0.02); border-radius: 20px; border: 1px dashed rgba(255, 255, 255, 0.1); padding: 50px 20px;">
                <i class="bi bi-calendar-x" style="font-size: 3rem; color: var(--text-muted); opacity: 0.5;"></i>
                <h4 class="mt-3 text-white fw-bold">No Events Found</h4>
                <p class="text-muted" style="max-width: 450px; margin: 0 auto 20px; font-size: 0.92rem;">
                    We couldn't find any events matching your current search or filter. Try a different keyword or reset filters.
                </p>
                <a href="index.php#events" class="btn-card-book">
                    <i class="bi bi-arrow-clockwise"></i> View All Events
                </a>
            </div>
        </div>
        <?php } ?>
    </div>

</section>

<!-- ══ HOW IT WORKS SECTION ══ -->
<section class="how-section" id="how-it-works">
    <div class="container">
        <div class="text-center mb-5">
            <div class="hero-badge mb-2">
                <i class="bi bi-lightning-fill"></i> Simple & Fast
            </div>
            <h2 class="section-title">How EventBook Works</h2>
            <p class="section-subtitle">Book your favorite experience in three straightforward steps</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-num">1</div>
                    <h5>Find Your Event</h5>
                    <p>Browse through music concerts, comedy nights, technical workshops, and conferences across top venues.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-num">2</div>
                    <h5>Reserve Your Passes</h5>
                    <p>Choose your ticket quantity, review real-time pricing, and complete your reservation securely in seconds.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-num">3</div>
                    <h5>Get Digital Pass</h5>
                    <p>Access your digital ticket from your User Dashboard with live barcode & QR code for quick gate admission.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ══ CALL TO ACTION BANNER ══ -->
<div class="container">
    <div class="cta-banner">
        <h2>Ready to Experience Something Incredible?</h2>
        <p>
            Join thousands of event enthusiasts. Book your passes today or manage your existing tickets from your dashboard.
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="#events" class="btn-nav-auth btn-nav-primary" style="padding: 12px 28px; font-size: 0.95rem;">
                <i class="bi bi-compass"></i> Explore All Events
            </a>
            <?php if ($is_logged_in) { ?>
                <a href="user_dashboard.php" class="btn-nav-auth btn-nav-login" style="padding: 12px 24px; font-size: 0.95rem;">
                    <i class="bi bi-speedometer2"></i> Open Dashboard
                </a>
            <?php } else { ?>
                <a href="register.php" class="btn-nav-auth btn-nav-login" style="padding: 12px 24px; font-size: 0.95rem;">
                    <i class="bi bi-person-plus"></i> Create Free Account
                </a>
            <?php } ?>
        </div>
    </div>
</div>

<!-- ══ FOOTER ══ -->
<footer>
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="footer-brand">
                    <h4>
                        <i class="bi bi-ticket-perforated-fill" style="color: var(--primary-accent);"></i>
                        Event<span>Book</span>
                    </h4>
                    <p>
                        EventBook is a next-generation ticketing platform empowering attendees to discover live concerts, seminars, and festivals effortlessly.
                    </p>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <div class="footer-col">
                    <h6>Navigation</h6>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="#events">All Events</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <?php if ($is_logged_in) { ?>
                            <li><a href="user_dashboard.php">User Dashboard</a></li>
                            <li><a href="my_tickets.php">My Tickets</a></li>
                        <?php } else { ?>
                            <li><a href="login.php">Sign In</a></li>
                            <li><a href="register.php">Sign Up</a></li>
                        <?php } ?>
                    </ul>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="footer-col">
                    <h6>Event Types</h6>
                    <ul class="footer-links">
                        <li><a href="index.php?filter=Concert#events">Live Concerts</a></li>
                        <li><a href="index.php?filter=Seminar#events">Tech Seminars</a></li>
                        <li><a href="index.php?filter=Workshop#events">Interactive Workshops</a></li>
                        <li><a href="index.php?filter=Festival#events">Cultural Festivals</a></li>
                        <li><a href="index.php?filter=Sports#events">Sports Tournaments</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="footer-col">
                    <h6>Support & Safety</h6>
                    <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6;">
                        Have questions about a booking? Reach out to our team or verify tickets instantly.
                    </p>
                    <div style="color: var(--secondary-accent); font-weight: 600; font-size: 0.9rem;">
                        <i class="bi bi-envelope-at me-2"></i>support@eventbook.local
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                © <?php echo date("Y"); ?> EventBook Platform. All rights reserved.
            </div>
            <div class="d-flex gap-3">
                <a href="#" style="color: var(--text-muted); text-decoration: none;"><i class="bi bi-facebook"></i></a>
                <a href="#" style="color: var(--text-muted); text-decoration: none;"><i class="bi bi-instagram"></i></a>
                <a href="#" style="color: var(--text-muted); text-decoration: none;"><i class="bi bi-twitter-x"></i></a>
                <a href="#" style="color: var(--text-muted); text-decoration: none;"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
