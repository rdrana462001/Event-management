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

$user_id = $_SESSION['user_id'];

/* Fetch bookings with event details */
$sql = "SELECT 
            bookings.booking_id,
            bookings.qty,
            bookings.total_amount,
            bookings.payment_status,
            bookings.booking_date,
            events.title,
            events.event_date,
            events.location
        FROM bookings
        JOIN events ON bookings.event_id = events.event_id
        WHERE bookings.user_id = '$user_id'
        ORDER BY bookings.booking_date DESC";

$result = mysqli_query($conn,$sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Tickets</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="text-center mb-4">🎫 My Tickets</h3>

    <div class="card p-3">
        <table class="table table-bordered table-striped text-center">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Tickets</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Booked On</th>
                </tr>
            </thead>
            <tbody>

            <?php
            if(mysqli_num_rows($result) > 0){
                $i = 1;
                while($row = mysqli_fetch_assoc($result)){
            ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo $row['title']; ?></td>
                    <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                    <td><?php echo $row['location']; ?></td>
                    <td><?php echo $row['qty']; ?></td>
                    <td>₹<?php echo $row['total_amount']; ?></td>
                    <td>
                        <span class="badge bg-success">
                            <?php echo $row['payment_status']; ?>
                        </span>
                    </td>
                    <td><?php echo date("d M Y", strtotime($row['booking_date'])); ?></td>
                </tr>
            <?php
                }
            } else {
            ?>
                <tr>
                    <td colspan="8">No tickets booked yet.</td>
                </tr>
            <?php } ?>

            </tbody>
        </table>

        <div class="text-center">
            <a href="index.php" class="btn btn-primary">
                Back to Home
            </a>
        </div>
    </div>
</div>

</body>
</html>
