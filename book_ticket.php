<?php
session_start();
$conn = new mysqli("localhost","root","","event_db");

if(!isset($_SESSION['user_id'])) {
    header("location:login.php");
    exit();
}

if(!isset($_GET['event_id'])) {
    header("location:index.php");
    exit();
}

$event_id = $_GET['event_id'];

$sql = "SELECT * FROM events WHERE event_id='$event_id'";
$result = $conn->query($sql);

if($result->num_rows != 1) {
    header("location:index.php");
    exit();
}

$event = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Ticket</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <script>
        function calculateTotal() {
            let price = document.getElementById("price").value;
            let qty = document.getElementById("qty").value;
            document.getElementById("total").innerHTML = "₹ " + (price * qty);
        }
    </script>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto card p-4">

            <h3 class="text-center mb-3">🎟️ Book Ticket</h3>

            <img src="assets/images/<?php echo $event['image']; ?>" class="img-fluid mb-3 rounded">

            <h5><?php echo $event['title']; ?></h5>
            <p>
                📅 <?php echo date("d M Y", strtotime($event['event_date'])); ?><br>
                ⏰ <?php echo $event['event_time']; ?><br>
                📍 <?php echo $event['location']; ?><br>
                💰 Price per Ticket: ₹<?php echo $event['price']; ?>
            </p>

            <form method="post" action="payment.php">
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                <input type="hidden" name="price" id="price" value="<?php echo $event['price']; ?>">

                <label><b>Number of Tickets</b></label>
                <select name="qty" id="qty" class="form-control mb-3" onchange="calculateTotal()" required>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>

                <h5>Total Amount: <span id="total">₹ <?php echo $event['price']; ?></span></h5>

                <button type="submit" class="btn btn-success w-100 mt-3">
                    Proceed to Payment
                </button>
            </form>

        </div>
    </div>
</div>

</body>
</html>
