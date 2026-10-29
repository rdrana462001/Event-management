<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("DB error"); }

$sql = "SELECT * FROM events ORDER BY event_date DESC";
$result = mysqli_query($conn,$sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap');

        :root {
            --bg-dark: #0b0512;
            --bg-card: rgba(35, 17, 56, 0.4);
            --bg-table: rgba(20, 10, 30, 0.6);
            --primary-accent: #9d4edd;
            --primary-hover: #c77dff;
            --secondary-accent: #e0aaff;
            --text-light: #f8f9fa;
            --text-muted: #adb5bd;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(157, 78, 221, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(199, 125, 255, 0.15), transparent 25%);
            background-attachment: fixed;
            color: var(--text-light);
            min-height: 100vh;
        }
        
        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Navbar */
        .navbar {
            backdrop-filter: blur(12px);
            background: rgba(11, 5, 18, 0.85) !important;
            border-bottom: 1px solid rgba(157, 78, 221, 0.15);
            padding: 1rem 0;
            margin-bottom: 40px;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .navbar-brand span {
            color: var(--primary-accent);
        }

        .nav-link {
            color: var(--text-light) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: 0.3s;
        }

        .nav-link:hover {
            color: var(--primary-accent) !important;
            transform: translateY(-2px);
        }

        /* Container & Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease forwards;
        }

        .page-header h3 {
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-add {
            background: linear-gradient(135deg, #11998e, #38ef7d);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 600;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(17, 153, 142, 0.5);
            color: white;
        }

        /* Table Card */
        .table-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 25px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.8s ease forwards;
            overflow-x: auto;
        }

        /* Custom Table */
        .table {
            color: var(--text-light);
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .table thead th {
            border: none;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            font-weight: 600;
            padding: 15px;
            background: transparent;
        }

        .table tbody tr {
            background: var(--bg-table);
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(157, 78, 221, 0.15);
            transform: scale(1.01);
        }

        .table tbody td {
            border: none;
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.02);
        }

        .table tbody td:first-child {
            border-left: 1px solid rgba(255,255,255,0.02);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table tbody td:last-child {
            border-right: 1px solid rgba(255,255,255,0.02);
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        /* Table elements */
        .event-thumbnail {
            width: 70px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .no-image {
            display: inline-block;
            width: 70px;
            height: 45px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            text-align: center;
            line-height: 45px;
            color: rgba(255,255,255,0.3);
            font-size: 0.75rem;
            border: 1px dashed rgba(255,255,255,0.2);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(56, 239, 125, 0.15);
            color: #38ef7d;
            border: 1px solid rgba(56, 239, 125, 0.3);
        }

        .status-inactive {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.3);
        }

        /* Action Buttons in Table */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            transition: all 0.2s;
            border: none;
        }

        .btn-edit {
            background: rgba(157, 78, 221, 0.2);
            color: var(--secondary-accent);
        }
        .btn-edit:hover {
            background: var(--primary-accent);
            color: white;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: rgba(255, 107, 107, 0.15);
            color: #ff6b6b;
        }
        .btn-delete:hover {
            background: #ff6b6b;
            color: white;
            transform: translateY(-2px);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="admin_stats.php">
            <i class="bi bi-shield-lock-fill me-2" style="color: var(--primary-accent);"></i>
            Admin<span>Panel</span>
        </a>
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <i class="bi bi-list fs-1 text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="admin_stats.php"><i class="bi bi-speedometer2 me-1"></i> Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="../index.php" target="_blank"><i class="bi bi-box-arrow-up-right me-1"></i> View Site</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container pb-5">
    
    <div class="page-header">
        <h3><i class="bi bi-calendar-event me-2"></i>Manage Events</h3>
        <a href="add_event.php" class="btn-add">
            <i class="bi bi-plus-lg me-1"></i> Add New Event
        </a>
    </div>

    <div class="table-card">
        <table class="table text-center table-borderless">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th class="text-start">Event Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php
            if(mysqli_num_rows($result) > 0){
                $i=1;
                while($row = mysqli_fetch_assoc($result)){
                    $status_class = ($row['status'] == 'active') ? 'status-active' : 'status-inactive';
            ?>
                <tr>
                    <td class="fw-bold" style="color: var(--text-muted);"><?php echo $i++; ?></td>
                    <td>
                        <?php if(!empty($row['image'])): ?>
                            <img src="../assets/images/<?php echo htmlspecialchars($row['image']); ?>" alt="Event" class="event-thumbnail" onerror="this.onerror=null; this.src='https://placehold.co/70x45/231138/c77dff?text=No+Img';">
                        <?php else: ?>
                            <span class="no-image">None</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-start fw-bold text-white"><?php echo htmlspecialchars($row['title']); ?></td>
                    <td>
                        <i class="bi bi-calendar me-1" style="color: var(--primary-accent); font-size: 0.9em;"></i> 
                        <?php echo date("d M Y", strtotime($row['event_date'])); ?>
                    </td>
                    <td>
                        <i class="bi bi-geo-alt me-1" style="color: #4facfe; font-size: 0.9em;"></i> 
                        <?php echo htmlspecialchars($row['location']); ?>
                    </td>
                    <td class="fw-bold" style="color: #38ef7d;">
                        ₹<?php echo number_format($row['price'], 2); ?>
                    </td>
                    <td>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_event.php?id=<?php echo $row['event_id']; ?>" class="btn-action btn-edit" title="Edit Event">
                            <i class="bi bi-pencil-square"></i>
                        </a>
                        <a href="delete_event.php?id=<?php echo $row['event_id']; ?>" 
                           class="btn-action btn-delete" 
                           title="Delete Event"
                           onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')">
                            <i class="bi bi-trash3-fill"></i>
                        </a>
                    </td>
                </tr>
            <?php } } else { ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                        <h5 class="text-white">No events found</h5>
                        <p class="text-muted">You haven't created any events yet.</p>
                        <a href="add_event.php" class="btn btn-outline-light btn-sm mt-2 rounded-pill px-4">Create First Event</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
