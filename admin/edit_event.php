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
    $date  = $_POST['event_date'];
    $loc   = $_POST['location'];
    $price = $_POST['price'];
    $status= $_POST['status'];

    $sql = "UPDATE events SET
            title='$title',
            event_date='$date',
            location='$loc',
            price='$price',
            status='$status'
            WHERE event_id='$id'";

    mysqli_query($conn,$sql);
    header("location:events.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Event</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5 col-md-6 card p-4">
    <h4>Edit Event</h4>

    <form method="post">
        <input type="text" name="title" value="<?php echo $event['title']; ?>" class="form-control mb-2">
        <input type="date" name="event_date" value="<?php echo $event['event_date']; ?>" class="form-control mb-2">
        <input type="text" name="location" value="<?php echo $event['location']; ?>" class="form-control mb-2">
        <input type="number" name="price" value="<?php echo $event['price']; ?>" class="form-control mb-2">

        <select name="status" class="form-control mb-3">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <button name="update" class="btn btn-primary w-100">Update</button>
    </form>
</div>

</body>
</html>
