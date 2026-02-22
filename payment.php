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
<html>
<head>
    <title>Payment</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-5 mx-auto card p-4 text-center">

        <h3 class="text-success">💳 Payment Successful</h3>
        <hr>

        <p><b>Event ID:</b> <?php echo $event_id; ?></p>
        <p><b>Tickets:</b> <?php echo $qty; ?></p>
        <p><b>Total Paid:</b> ₹<?php echo $total; ?></p>

        <p class="text-muted mt-3">
            (This is a demo payment for academic project)
        </p>

        <a href="index.php" class="btn btn-primary mt-3">
            Back to Home
        </a>

    </div>
</div>

</body>
</html>
