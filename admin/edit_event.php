<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("DB error"); }

$id = $_GET['id'];

$event = mysqli_fetch_assoc(
    mysqli_query($conn,"SELECT * FROM events WHERE event_id='$id'")
);

if(isset($_POST['update'])){
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $date  = $_POST['event_date'];
    $time  = $_POST['event_time'];
    $loc   = $_POST['location'];
    $price = $_POST['price'];
    $status= $_POST['status'];

    $image_query_part = "";
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $img = $_FILES['image']['name'];
        $tmp_name = $_FILES['image']['tmp_name'];
        if (move_uploaded_file($tmp_name, "../assets/images/".$img)) {
            $image_query_part = ", image='$img'";
        }
    }

    $sql = "UPDATE events SET
            title='$title',
            description='$desc',
            event_date='$date',
            event_time='$time',
            location='$loc',
            price='$price',
            status='$status'
            $image_query_part
            WHERE event_id='$id'";

    mysqli_query($conn,$sql);
    header("location:events.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event | Admin Panel</title>
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

        /* Sidebar */
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
        .nav-item-custom.danger:hover { color: var(--danger); background: rgba(255,107,107,0.08); border-left-color: var(--danger); }
        .sidebar-footer { padding: 20px 25px; border-top: 1px solid rgba(255,255,255,0.05); }
        .admin-badge { display: flex; align-items: center; gap: 12px; }
        .admin-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary-accent), #7b2cbf); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; }
        .admin-info .name { font-weight: 700; font-size: 0.9rem; color: #fff; }
        .admin-info .role { font-size: 0.75rem; color: var(--text-muted); }

        /* Main */
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

        .dashboard-body { padding: 35px; }

        /* Form Card */
        .form-card {
            max-width: 820px;
            margin: 0 auto;
            background: var(--bg-card);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            animation: fadeInUp 0.7s ease forwards;
        }

        .form-header { margin-bottom: 35px; }
        .form-header h4 {
            font-weight: 800; font-size: 1.6rem;
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        .form-header p { color: var(--text-muted); font-size: 0.9rem; }

        .form-label {
            font-weight: 600; color: var(--secondary-accent);
            margin-bottom: 8px; font-size: 0.9rem; letter-spacing: 0.5px;
            display: flex; align-items: center; gap: 6px;
        }

        .form-control, .form-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff; border-radius: 12px;
            padding: 12px 15px; transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--primary-accent);
            box-shadow: 0 0 15px rgba(157,78,221,0.25);
            color: #fff;
        }
        .form-control::placeholder { color: rgba(255,255,255,0.35); }
        ::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; cursor: pointer; }

        /* Image Preview */
        .image-preview-wrap {
            background: rgba(0,0,0,0.2);
            border: 1px dashed rgba(157,78,221,0.5);
            border-radius: 12px; padding: 15px;
            text-align: center; min-height: 120px;
            display: flex; align-items: center;
            justify-content: center; flex-direction: column;
            margin-top: 10px;
        }

        .image-preview-wrap img {
            max-width: 100%; max-height: 280px;
            border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.4);
        }

        .placeholder-text { color: var(--text-muted); font-size: 0.9rem; }

        /* Buttons */
        .btn-update {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: white; border: none; border-radius: 12px;
            padding: 14px; font-weight: 700; font-size: 1rem;
            text-transform: uppercase; letter-spacing: 1.5px;
            transition: all 0.3s ease; width: 100%;
        }
        .btn-update:hover {
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(157,78,221,0.4);
            color: white;
        }

        .btn-back {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-muted); border-radius: 12px;
            padding: 14px; font-weight: 600; width: 100%;
            transition: 0.3s; text-decoration: none;
            display: block; text-align: center;
        }
        .btn-back:hover { color: #fff; border-color: rgba(255,255,255,0.3); background: rgba(255,255,255,0.08); }

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
        <a href="events.php" class="nav-item-custom active">
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

    <div class="topbar">
        <div class="topbar-left">
            <h2><i class="bi bi-pencil-square me-2" style="color: var(--primary-accent);"></i>Edit Event</h2>
            <div class="breadcrumb-text">Dashboard &rsaquo; Manage Events &rsaquo; Edit</div>
        </div>
    </div>

    <div class="dashboard-body">
        <div class="form-card">
            <div class="form-header">
                <h4><i class="bi bi-pencil-fill me-2" style="color: var(--primary-accent);"></i> Update Event Details</h4>
                <p>Modify the event information below and save your changes.</p>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label"><i class="bi bi-type"></i> Event Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($event['title'] ?? ''); ?>" class="form-control" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="bi bi-text-paragraph"></i> Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Event description..."><?php echo htmlspecialchars($event['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-calendar-date"></i> Event Date</label>
                        <input type="date" name="event_date" value="<?php echo htmlspecialchars($event['event_date'] ?? ''); ?>" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-clock"></i> Event Time</label>
                        <input type="text" name="event_time" value="<?php echo htmlspecialchars($event['event_time'] ?? ''); ?>" class="form-control" placeholder="E.g., 07:00 PM onwards">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-geo-alt"></i> Location</label>
                        <input type="text" name="location" value="<?php echo htmlspecialchars($event['location'] ?? ''); ?>" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label"><i class="bi bi-currency-rupee"></i> Price</label>
                        <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($event['price'] ?? ''); ?>" class="form-control" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="bi bi-image"></i> Event Image</label>
                        <div class="image-preview-wrap" id="currentImageWrap">
                            <?php if (!empty($event['image'])): ?>
                                <img id="currentImage" 
                                     src="../assets/images/<?php echo htmlspecialchars($event['image']); ?>" 
                                     alt="Current Event Image"
                                     onerror="this.style.display='none'; document.getElementById('noImgTxt').style.display='block';">
                                <p id="noImgTxt" class="placeholder-text mt-2" style="display:none;">Image not found. Upload a new one below.</p>
                            <?php else: ?>
                                <p class="placeholder-text"><i class="bi bi-image me-1"></i> No image uploaded</p>
                            <?php endif; ?>
                        </div>

                        <label class="form-label mt-3"><i class="bi bi-upload"></i> Upload New Image <small style="color: var(--text-muted); font-weight: 400;">(leave blank to keep current)</small></label>
                        <input type="file" name="image" id="imageInput" class="form-control" accept="image/*">

                        <div id="newPreviewWrap" class="image-preview-wrap mt-3" style="display:none;">
                            <img id="imagePreview" src="#" alt="New Image Preview">
                        </div>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label"><i class="bi bi-toggle-on"></i> Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (isset($event['status']) && $event['status'] == 'active') ? 'selected' : ''; ?>>Active (Visible to users)</option>
                            <option value="inactive" <?php echo (isset($event['status']) && $event['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive (Hidden)</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <button type="submit" name="update" class="btn-update">
                            <i class="bi bi-cloud-check-fill me-2"></i> Save Changes
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="events.php" class="btn-back">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('imageInput').onchange = function() {
        const [file] = this.files;
        if (file) {
            document.getElementById('imagePreview').src = URL.createObjectURL(file);
            document.getElementById('newPreviewWrap').style.display = 'flex';
            document.getElementById('currentImageWrap').style.display = 'none';
        }
    };
</script>
</body>
</html>
