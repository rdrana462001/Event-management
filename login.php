<?php
session_start();
$conn = new mysqli("localhost","root","","event_db");

$msg = "";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    /* ADMIN LOGIN */
    if($email == "admin@gmail.com" && $password == "admin"){
        $_SESSION['admin'] = "yes";
        header("location:admin/admin_stats.php");
        exit();
    }

    /* USER LOGIN */
    $sql = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn,$sql);

    if(mysqli_num_rows($result) == 1){
        $row = mysqli_fetch_assoc($result);

        if(password_verify($password, $row['password'])){
            $_SESSION['user_id'] = $row['id'];
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBook - Login</title>
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

        h3, .brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Card */
        .card {
            position: relative;
            width: 100%;
            max-width: 420px;
            padding: 40px 30px;
            background: var(--bg-card);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6), 0 0 20px rgba(157, 78, 221, 0.1);
            animation: fadeIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* Fade */
        @keyframes fadeIn {
            from { transform: translateY(30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
        }

        .back-btn:hover {
            background: rgba(157, 78, 221, 0.2);
            color: #fff;
            border-color: rgba(157, 78, 221, 0.5);
            transform: translateX(-5px);
        }

        /* Title */
        .brand {
            text-align: center;
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 5px;
            margin-top: 15px;
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand span {
            color: var(--primary-accent);
            -webkit-text-fill-color: var(--primary-accent);
        }

        h3 {
            color: var(--text-muted);
            text-align: center;
            font-weight: 500;
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        /* Inputs */
        .input-group-custom {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-group-custom i {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: 0.3s;
            z-index: 2;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 14px 15px 14px 45px;
            color: #fff;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-accent);
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.2);
            color: #fff;
            outline: none;
        }
        
        .form-control:focus + i, .input-group-custom:focus-within i {
            color: var(--primary-accent);
        }

        /* Button */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.4);
            color: white;
        }

        /* Links */
        .register-link {
            color: var(--text-muted);
            margin-top: 25px;
            font-size: 0.95rem;
        }
        
        .register-link a {
            color: var(--primary-accent);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .register-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .alert {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
            border-radius: 12px;
            padding: 10px 15px;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">
    <!-- Back Button -->
    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left me-2"></i>Back</a>

    <div class="brand mt-3">
        Event<span>Book</span>
    </div>
    <h3>Welcome Back</h3>

    <?php if($msg!="") echo "<div class='alert mb-3'>$msg</div>"; ?>

    <form method="post">
        <div class="input-group-custom">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
        </div>
        
        <div class="input-group-custom">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
        </div>

        <button type="submit" name="login" class="btn btn-login w-100">Login to Account</button>
    </form>

    <p class="text-center register-link">
        New to EventBook? <a href="register.php">Create an account</a>
    </p>
</div>

</body>
</html>