<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){ die("DB error"); }

if(isset($_POST['add'])){
    $title = $_POST['title'];
    $desc  = $_POST['description'];
    $date  = $_POST['event_date'];
    $time  = $_POST['event_time'];
    $loc   = $_POST['location'];
    $price = $_POST['price'];
    $status= $_POST['status'];

    $img = $_FILES['image']['name'];
    move_uploaded_file($_FILES['image']['tmp_name'], "../assets/images/".$img);

    $sql = "INSERT INTO events 
            (title,description,event_date,event_time,location,price,image,status)
            VALUES
            ('$title','$desc','$date','$time','$loc','$price','$img','$status')";

    mysqli_query($conn,$sql);
    header("location:events.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event | Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap');

        :root {
            --bg-dark: #0b0512;
            --bg-card: rgba(35, 17, 56, 0.4);
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

        /* Form Container */
        .form-container {
            max-width: 800px;
            margin: 50px auto;
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 40px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            animation: fadeInUp 0.8s ease forwards;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h3 {
            font-weight: 800;
            font-size: 2rem;
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .form-header p {
            color: var(--text-muted);
        }

        /* Custom Inputs */
        .form-label {
            font-weight: 600;
            color: var(--secondary-accent);
            margin-bottom: 8px;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-accent);
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.25);
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }
        
        /* Fix for date picker icon color */
        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.6;
            cursor: pointer;
        }

        /* Image Preview Box */
        .image-preview-container {
            background: rgba(0,0,0,0.2);
            border: 1px dashed rgba(157, 78, 221, 0.5);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 10px;
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        #imagePreview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            display: none;
        }
        
        .placeholder-text {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 30px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(157, 78, 221, 0.4);
            color: white;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
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
                <li class="nav-item"><a class="nav-link" href="events.php"><i class="bi bi-list-task me-1"></i> Manage Events</a></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="form-container">
        <div class="form-header">
            <h3><i class="bi bi-plus-circle-fill me-2" style="color: var(--primary-accent);"></i>Create New Event</h3>
            <p>Fill in the details below to add a new event to the platform.</p>
        </div>

        <form method="post" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-type me-1"></i> Event Title</label>
                    <input type="text" name="title" class="form-control" placeholder="E.g., Grand Garba Night 2026" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-text-paragraph me-1"></i> Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Write a catchy description about the event..."></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-calendar-date me-1"></i> Event Date</label>
                    <input type="date" name="event_date" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-clock me-1"></i> Event Time</label>
                    <input type="text" name="event_time" class="form-control" placeholder="E.g., 07:00 PM onwards">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-geo-alt me-1"></i> Location</label>
                    <input type="text" name="location" class="form-control" placeholder="E.g., GMDC Ground, Ahmedabad" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label"><i class="bi bi-currency-rupee me-1"></i> Ticket Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" placeholder="E.g., 499.00" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-image me-1"></i> Event Image Cover</label>
                    <input type="file" name="image" id="imageInput" class="form-control" accept="image/*" required>
                    
                    <div class="image-preview-container">
                        <span class="placeholder-text" id="previewPlaceholder"><i class="bi bi-image me-1"></i> Select an image to see preview</span>
                        <img id="imagePreview" src="#" alt="Event Image Preview">
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label"><i class="bi bi-toggle-on me-1"></i> Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active (Visible to users)</option>
                        <option value="inactive">Inactive (Hidden)</option>
                    </select>
                </div>
            </div>

            <button type="submit" name="add" class="btn-submit">
                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Publish Event
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('imageInput').onchange = function(evt) {
        const [file] = this.files;
        if (file) {
            document.getElementById('imagePreview').src = URL.createObjectURL(file);
            document.getElementById('imagePreview').style.display = 'block';
            document.getElementById('previewPlaceholder').style.display = 'none';
        } else {
            document.getElementById('imagePreview').style.display = 'none';
            document.getElementById('previewPlaceholder').style.display = 'block';
        }
    };
</script>

</body>
</html>
