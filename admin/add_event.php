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
<html>
<head>
    <title>Add Event</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5 col-md-6 card p-4">
    <h4>Add Event</h4>

    <form method="post" enctype="multipart/form-data">
        <input type="text" name="title" class="form-control mb-2" placeholder="Event Title" required>
        <textarea name="description" class="form-control mb-2" placeholder="Description"></textarea>
        <input type="date" name="event_date" class="form-control mb-2" required>
        <input type="text" name="event_time" class="form-control mb-2" placeholder="Time">
        <input type="text" name="location" class="form-control mb-2" placeholder="Location">
        <input type="number" name="price" class="form-control mb-2" placeholder="Price">
        <input type="file" name="image" class="form-control mb-2" required>

        <select name="status" class="form-control mb-3">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <button name="add" class="btn btn-success w-100">Add Event</button>
    </form>
</div>

</body>
</html>
