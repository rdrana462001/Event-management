<?php
session_start();
$conn = new mysqli("localhost","root","","event_db");

$msg = "";

if(isset($_POST['register'])) {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $raw_pass = $_POST['password'];

    // Server-side password validation
    $pass_valid = true;
    $msg_type   = 'error';

    if(strlen($raw_pass) < 8) {
        $msg = "Password must be at least 8 characters long.";
        $pass_valid = false;
    } elseif(!preg_match('/[A-Z]/', $raw_pass)) {
        $msg = "Password must contain at least 1 Capital letter.";
        $pass_valid = false;
    } elseif(!preg_match('/[a-z]/', $raw_pass)) {
        $msg = "Password must contain at least 1 Small letter.";
        $pass_valid = false;
    } elseif(!preg_match('/[0-9]/', $raw_pass)) {
        $msg = "Password must contain at least 1 Number.";
        $pass_valid = false;
    } elseif(!preg_match('/[^A-Za-z0-9]/', $raw_pass)) {
        $msg = "Password must contain at least 1 Symbol (@, #, $, ! etc.).";
        $pass_valid = false;
    }

    if($pass_valid) {
        $password = password_hash($raw_pass, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name,email,password) VALUES ('$name','$email','$password')";
        try {
            if($conn->query($sql)) {
                $msg      = "Registration successful! Please login.";
                $msg_type = 'success';
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $msg = "Email already exists! Please try logging in.";
            } else {
                $msg = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBook - Register</title>
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
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px;
        }

        h3, .brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Card */
        .card {
            position: relative;
            width: 100%;
            max-width: 520px;
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
        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            box-shadow: 0 8px 20px rgba(157, 78, 221, 0.4);
            color: white;
        }

        /* Links */
        .login-link {
            color: var(--text-muted);
            margin-top: 25px;
            font-size: 0.95rem;
        }
        
        .login-link a {
            color: var(--primary-accent);
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .login-link a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            padding: 10px 15px;
            font-size: 0.9rem;
            text-align: center;
        }
        .alert-success {
            background: rgba(56, 239, 125, 0.1);
            border: 1px solid rgba(56, 239, 125, 0.3);
            color: #38ef7d;
        }
        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

        /* Password Strength Checklist */
        .pw-checklist {
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: -10px;
            margin-bottom: 18px;
            display: none;
        }
        .pw-checklist.show { display: block; }
        .pw-rule {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--text-muted);
            padding: 3px 0;
            transition: color 0.3s;
        }
        .pw-rule i {
            font-size: 0.85rem;
            transition: color 0.3s;
        }
        .pw-rule.ok   { color: #38ef7d; }
        .pw-rule.ok i { color: #38ef7d; }
        .pw-rule.fail i { color: #ff6b6b; }

        /* Password eye toggle */
        .pw-wrapper {
            position: relative;
        }
        .pw-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            z-index: 3;
            padding: 0;
            font-size: 1rem;
            transition: color 0.3s;
        }
        .pw-toggle:hover { color: var(--primary-accent); }
    </style>
</head>
<body>

<div class="card">
    <!-- Back Button -->
    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left me-2"></i>Back</a>

    <div class="brand mt-3">
        Event<span>Book</span>
    </div>
    <h3>Create an Account</h3>

    <?php
    if($msg != "") {
        $cls = (isset($msg_type) && $msg_type === 'success') ? 'alert-success' : 'alert-error';
        echo "<div class='alert $cls mb-3'>$msg</div>";
    }
    ?>

    <form method="post">
        <div class="input-group-custom">
            <i class="bi bi-person"></i>
            <input type="text" name="name" class="form-control" placeholder="Full Name" required>
        </div>
        
        <div class="input-group-custom">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="Email Address" required>
        </div>
        
        <div class="input-group-custom pw-wrapper">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" id="pwInput"
                   class="form-control" placeholder="Password (min. 8 chars)" required
                   style="padding-right: 48px;">
            <button type="button" class="pw-toggle" id="pwToggle" tabindex="-1">
                <i class="bi bi-eye" id="pwEyeIcon"></i>
            </button>
        </div>

        <!-- Live Checklist -->
        <div class="pw-checklist" id="pwChecklist">
            <div class="pw-rule" id="r-len"><i class="bi bi-x-circle-fill"></i> At least 8 characters</div>
            <div class="pw-rule" id="r-upper"><i class="bi bi-x-circle-fill"></i> 1 Capital letter (A-Z)</div>
            <div class="pw-rule" id="r-lower"><i class="bi bi-x-circle-fill"></i> 1 Small letter (a-z)</div>
            <div class="pw-rule" id="r-num"><i class="bi bi-x-circle-fill"></i> 1 Number (0-9)</div>
            <div class="pw-rule" id="r-sym"><i class="bi bi-x-circle-fill"></i> 1 Symbol (@, #, $, ! ...)</div>
        </div>

        <button type="submit" name="register" id="registerBtn" class="btn btn-register w-100">Register</button>
    </form>

    <p class="text-center login-link">
        Already have an account? <a href="login.php">Login here</a>
    </p>
</div>

<script>
    const pwInput    = document.getElementById('pwInput');
    const checklist  = document.getElementById('pwChecklist');
    const pwToggle   = document.getElementById('pwToggle');
    const pwEyeIcon  = document.getElementById('pwEyeIcon');
    const registerBtn = document.getElementById('registerBtn');

    const rules = {
        'r-len':   v => v.length >= 8,
        'r-upper': v => /[A-Z]/.test(v),
        'r-lower': v => /[a-z]/.test(v),
        'r-num':   v => /[0-9]/.test(v),
        'r-sym':   v => /[^A-Za-z0-9]/.test(v)
    };

    pwInput.addEventListener('focus', () => checklist.classList.add('show'));

    pwInput.addEventListener('input', function() {
        const val = this.value;
        let allOk = true;

        for (const [id, fn] of Object.entries(rules)) {
            const el   = document.getElementById(id);
            const icon = el.querySelector('i');
            if (fn(val)) {
                el.classList.add('ok');
                el.classList.remove('fail');
                icon.className = 'bi bi-check-circle-fill';
            } else {
                el.classList.remove('ok');
                el.classList.add('fail');
                icon.className = 'bi bi-x-circle-fill';
                allOk = false;
            }
        }

        // Register button glow green when all pass
        registerBtn.style.background = allOk
            ? 'linear-gradient(135deg, #38ef7d, #11998e)'
            : '';
    });

    // Show/Hide Password
    pwToggle.addEventListener('click', () => {
        if (pwInput.type === 'password') {
            pwInput.type = 'text';
            pwEyeIcon.className = 'bi bi-eye-slash';
        } else {
            pwInput.type = 'password';
            pwEyeIcon.className = 'bi bi-eye';
        }
    });

    // Block submit if validation fails
    document.querySelector('form').addEventListener('submit', function(e) {
        const val = pwInput.value;
        const allOk = Object.values(rules).every(fn => fn(val));
        if (!allOk) {
            e.preventDefault();
            checklist.classList.add('show');
            pwInput.focus();
        }
    });
</script>
</body>
</html>