<?php
session_start();

$conn = mysqli_connect("localhost","root","","event_db");

if(!$conn){
    die("Database connection failed");
}

if(!isset($_SESSION['user_id'])){
    header("location:login.php");
    exit();
}

if(!isset($_POST['event_id'])){
    header("location:index.php");
    exit();
}

$user_id  = $_SESSION['user_id'];
$event_id = $_POST['event_id'];
$qty      = $_POST['qty'];
$price    = $_POST['price'];

$total = $qty * $price;

/* Save booking as PAID (dummy payment) */
$sql = "INSERT INTO bookings 
        (user_id, event_id, qty, total_amount, payment_status)
        VALUES 
        ('$user_id','$event_id','$qty','$total','Paid')";

mysqli_query($conn,$sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBook - Payment Successful</title>
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
            --success-color: #00f2fe;
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
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        h3, h5, .brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Card */
        .card {
            position: relative;
            width: 100%;
            max-width: 450px;
            padding: 40px 30px;
            background: var(--bg-card);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6), 0 0 20px rgba(157, 78, 221, 0.1);
            animation: fadeIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
        }

        /* Fade */
        @keyframes fadeIn {
            from { transform: translateY(30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 15px;
            animation: pulse 2s infinite alternate;
            text-shadow: 0 0 20px rgba(0, 242, 254, 0.5);
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.1); }
        }

        h3 {
            color: #fff;
            font-weight: 800;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        .details-box {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }

        .details-box p {
            margin-bottom: 10px;
            font-size: 1.05rem;
            color: var(--text-light);
            display: flex;
            justify-content: space-between;
        }

        .details-box p:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
            font-size: 1.2rem;
            font-weight: 700;
            color: #fff;
        }
        
        .details-box p span:first-child {
            color: var(--text-muted);
            font-weight: 500;
        }

        .btn-home {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.4);
            color: white;
        }
        
        .demo-text {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 20px;
            opacity: 0.7;
        }
    </style>
</head>
<body>

<div class="card">
    <i class="bi bi-check-circle-fill success-icon"></i>
    <h3>Payment Successful!</h3>
    
    <div class="details-box">
        <p><span>Event ID</span> <span>#<?php echo htmlspecialchars($event_id); ?></span></p>
        <p><span>Tickets</span> <span><?php echo htmlspecialchars($qty); ?></span></p>
        <p><span>Total Paid</span> <span style="color: var(--success-color);">₹<?php echo number_format($total, 2); ?></span></p>
    </div>

    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <a href="user_dashboard.php" class="btn-home">
            <i class="bi bi-speedometer2 me-1"></i> My Dashboard
        </a>
        <a href="my_tickets.php" class="btn-home" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.2);">
            <i class="bi bi-ticket-detailed me-1"></i> My Tickets
        </a>
    </div>
    
    <p class="demo-text">
        (This is a demo payment for academic project)
    </p>
</div>

</body>
</html>
