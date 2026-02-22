<?php
session_start();
$conn = new mysqli("localhost","root","","event_db");

$msg = "";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    /* ADMIN LOGIN CHECK */
    if($email == "admin@gmail.com" && $password == "12"){
        $_SESSION['admin'] = "yes";
        header("location:admin/admin_stats.php");
        exit();
    }

    /* USER LOGIN CHECK */
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$sql);

    if(mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);

        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_name'] = $row['name'];
            header("location:index.php");
            exit();
        } else {
            $msg = "Invalid password";
        }
    } else {
        $msg = "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="col-md-5 mx-auto card p-4">
        <h3 class="text-center">Login</h3>

        <?php if($msg!="") echo "<div class='alert alert-danger'>$msg</div>"; ?>

        <form method="post">
            <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>

            <button name="login" class="btn btn-success w-100">Login</button>
        </form>

        <p class="text-center mt-3">
            New user? <a href="register.php">Register</a>
        </p>
    </div>
</div>

</body>
</html>
