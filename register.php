<?php
session_start();
$conn = new mysqli("localhost","root","","event_db");

$msg = "";

if(isset($_POST['register'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (name,email,password)
            VALUES ('$name','$email','$password')";

    if($conn->query($sql)) {
        $msg = "Registration successful! Please login.";
    } else {
        $msg = "Email already exists!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-5 mx-auto card p-4">
        <h3 class="text-center">Register</h3>

        <?php if($msg!="") echo "<div class='alert alert-info'>$msg</div>"; ?>

        <form method="post">
            <input type="text" name="name" class="form-control mb-2" placeholder="Full Name" required>
            <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

            <button name="register" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="text-center mt-3">
            Already have account? <a href="login.php">Login</a>
        </p>
    </div>
</div>

</body>
</html>
