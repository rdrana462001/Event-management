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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventBook - Book Ticket</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        h3, h5, .brand {
            font-family: 'Poppins', sans-serif;
        }

        /* Card */
        .card {
            position: relative;
            width: 100%;
            max-width: 550px;
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
            z-index: 10;
        }

        .back-btn:hover {
            background: rgba(157, 78, 221, 0.2);
            color: #fff;
            border-color: rgba(157, 78, 221, 0.5);
            transform: translateX(-5px);
        }

        h3 {
            text-align: center;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 25px;
            margin-top: 10px;
            background: linear-gradient(135deg, #fff, var(--secondary-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .event-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }

        .event-title {
            font-weight: 700;
            font-size: 1.5rem;
            color: #fff;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 15px;
        }

        .event-details {
            margin-bottom: 25px;
        }
        
        .event-detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            color: var(--text-light);
            font-size: 1rem;
        }

        .event-detail-item i {
            color: var(--primary-accent);
            width: 30px;
            font-size: 1.2rem;
            display: flex;
            justify-content: center;
            margin-right: 12px;
            background: rgba(157, 78, 221, 0.15);
            padding: 8px;
            border-radius: 10px;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
        }

        .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 15px;
            color: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .form-select option {
            background: var(--bg-dark);
            color: #fff;
        }

        .form-select:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--primary-accent);
            box-shadow: 0 0 15px rgba(157, 78, 221, 0.2);
            color: #fff;
            outline: none;
        }
        
        .total-box {
            background: rgba(157, 78, 221, 0.1);
            border: 1px solid rgba(157, 78, 221, 0.3);
            border-radius: 12px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 25px 0;
        }
        
        .total-box span:first-child {
            color: var(--text-muted);
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .total-amount {
            color: #fff;
            font-weight: 800;
            font-size: 1.5rem;
            font-family: 'Poppins', sans-serif;
        }
        
        .total-amount span {
            color: var(--primary-accent);
            margin-right: 2px;
        }

        /* Button */
        .btn-proceed {
            background: linear-gradient(135deg, var(--primary-accent), #7b2cbf);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-proceed:hover {
            transform: translateY(-3px);
            background: linear-gradient(135deg, var(--primary-hover), #9d4edd);
            box-shadow: 0 10px 25px rgba(157, 78, 221, 0.4);
            color: white;
        }
    </style>
    
    <script>
        function calculateTotal() {
            let price = parseFloat(document.getElementById("price").value);
            let qty = parseInt(document.getElementById("qty").value);
            let total = (price * qty).toFixed(2);
            document.getElementById("totalVal").innerHTML = total;
        }
    </script>
</head>
<body>

<div class="card">
    <!-- Back Button -->
    <a href="index.php" class="back-btn"><i class="bi bi-arrow-left me-2"></i>Back</a>

    <h3>🎟️ Book Your Ticket</h3>

    <img src="assets/images/<?php echo htmlspecialchars($event['image']); ?>" 
         class="event-img" 
         alt="<?php echo htmlspecialchars($event['title']); ?>"
         onerror="this.onerror=null; this.src='https://placehold.co/600x400/231138/c77dff?text=Event+Image';">

    <h5 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h5>
    
    <div class="event-details">
        <div class="event-detail-item">
            <i class="bi bi-calendar-event"></i>
            <span><?php echo date("d M Y", strtotime($event['event_date'])); ?></span>
        </div>
        <div class="event-detail-item">
            <i class="bi bi-clock"></i>
            <span><?php echo htmlspecialchars($event['event_time'] ?? 'Time not specified'); ?></span>
        </div>
        <div class="event-detail-item">
            <i class="bi bi-geo-alt"></i>
            <span><?php echo htmlspecialchars($event['location']); ?></span>
        </div>
        <div class="event-detail-item">
            <i class="bi bi-tag"></i>
            <span>Price per Ticket: <strong>₹<?php echo number_format($event['price'], 2); ?></strong></span>
        </div>
    </div>

    <form method="post" action="payment.php">
        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
        <input type="hidden" name="price" id="price" value="<?php echo htmlspecialchars($event['price']); ?>">

        <label for="qty" class="form-label">Number of Tickets</label>
        <select name="qty" id="qty" class="form-select" onchange="calculateTotal()" required>
            <option value="1">1 Ticket</option>
            <option value="2">2 Tickets</option>
            <option value="3">3 Tickets</option>
            <option value="4">4 Tickets</option>
            <option value="5">5 Tickets</option>
        </select>

        <div class="total-box">
            <span>Total Amount</span>
            <div class="total-amount">
                <span>₹</span> <span id="totalVal"><?php echo number_format($event['price'], 2); ?></span>
            </div>
        </div>

        <button type="submit" class="btn btn-proceed">
            Proceed to Payment <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </form>
</div>

</body>
</html>
