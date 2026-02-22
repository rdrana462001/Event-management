<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("location:../login.php");
    exit();
}

$conn = mysqli_connect("localhost","root","","event_db");
if(!$conn){
    die("Database connection failed");
}

/* TOTAL EVENTS */
$event_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM events");
$event_d = mysqli_fetch_assoc($event_q);

/* TOTAL USERS */
$user_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM users");
$user_d = mysqli_fetch_assoc($user_q);

/* TOTAL BOOKINGS */
$book_q = mysqli_query($conn,"SELECT COUNT(*) AS total FROM bookings");
$book_d = mysqli_fetch_assoc($book_q);

/* TOTAL REVENUE */
$rev_q = mysqli_query($conn,
    "SELECT SUM(total_amount) AS revenue 
     FROM bookings 
     WHERE payment_status='Paid'"
);
$rev_d = mysqli_fetch_assoc($rev_q);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="text-center mb-4">📊 Admin Dashboard</h3>

    <div class="row text-center">

        <div class="col-md-3">
            <div class="card bg-primary text-white p-3">
                <h5>Total Events</h5>
                <h2><?php echo $event_d['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white p-3">
                <h5>Total Users</h5>
                <h2><?php echo $user_d['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark p-3">
                <h5>Total Bookings</h5>
                <h2><?php echo $book_d['total']; ?></h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-danger text-white p-3">
                <h5>Total Revenue</h5>
                <h2>₹<?php echo ($rev_d['revenue'] ?? 0); ?></h2>
            </div>
        </div>

    </div>

    <div class="text-center mt-4">
        <a href="events.php" class="btn btn-secondary">Manage Events</a>
        <a href="admin_dashboard.php" class="btn btn-dark">View Bookings</a>
    </div>
</div>

</body>
</html>
