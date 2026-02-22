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
<html>
<head>
    <title>Manage Events</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="mb-3">🧑‍💼 Manage Events</h3>

    <a href="add_event.php" class="btn btn-success mb-3">➕ Add Event</a>

    <table class="table table-bordered table-striped text-center">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Title</th>
                <th>Date</th>
                <th>Location</th>
                <th>Price</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>

        <?php
        if(mysqli_num_rows($result) > 0){
            $i=1;
            while($row = mysqli_fetch_assoc($result)){
        ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo $row['title']; ?></td>
                <td><?php echo date("d M Y", strtotime($row['event_date'])); ?></td>
                <td><?php echo $row['location']; ?></td>
                <td>₹<?php echo $row['price']; ?></td>
                <td><?php echo $row['status']; ?></td>
                <td>
                    <a href="edit_event.php?id=<?php echo $row['event_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <a href="delete_event.php?id=<?php echo $row['event_id']; ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete this event?')">Delete</a>
                </td>
            </tr>
        <?php } } else { ?>
            <tr><td colspan="7">No events found</td></tr>
        <?php } ?>

        </tbody>
    </table>

    <a href="admin_dashboard.php" class="btn btn-secondary">Back</a>
</div>

</body>
</html>
