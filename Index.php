<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "event_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch events
$sql = "SELECT * FROM events WHERE status='active' ORDER BY event_date ASC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Ticket Booking System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
    
/* Google Font */
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(-45deg, #eef2ff, #fdfbfb, #e0f2fe, #f0f9ff);
    background-size: 400% 400%;
    animation: gradientBG 12s ease infinite;
}

/* Background animation */
@keyframes gradientBG {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Navbar brand animation */
.navbar-brand {
    font-weight: 600;
    letter-spacing: 1px;
    animation: glow 2s infinite alternate;
}

@keyframes glow {
    from { text-shadow: 0 0 5px #fff; }
    to { text-shadow: 0 0 15px #0d6efd; }
}

/* Hero Section */
.hero {
    background: linear-gradient(to right, #6a11cb, #2575fc);
    color: white;
    padding: 80px 20px;
    text-align: center;
    border-radius: 0 0 40px 40px;
}

.hero h1 {
    font-size: 3rem;
    animation: slideDown 1s ease;
}

.hero p {
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Hero animation */
@keyframes slideDown {
    from {
        transform: translateY(-40px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Event Card */
.event-card {
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.4s ease;
    animation: fadeUp 0.8s ease forwards;
    opacity: 0;
}

.event-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 15px 35px rgba(0,0,0,0.2);
}

.event-card img {
    height: 220px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.event-card:hover img {
    transform: scale(1.1);
}

/* Fade up animation */
@keyframes fadeUp {
    from {
        transform: translateY(30px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, #2575fc, #6a11cb);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #6a11cb, #2575fc);
    transform: scale(1.05);
}

/* Footer */
footer {
    letter-spacing: 1px;
}
 

        /* body {
            background-color: #f4f6f9;
        }
        .event-card {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .event-card img {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .hero {
            background: linear-gradient(to right, #6a11cb, #2575fc);
            color: white;
            padding: 60px 20px;
            text-align: center;
        } */
    </style>


</head>
<body>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const cards = document.querySelectorAll(".event-card");
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.15}s`;
    });
});
</script>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">🎟️ EventBook</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <?php if(isset($_SESSION['user_id'])) { ?>
                    <li class="nav-item"><a class="nav-link" href="my_tickets.php">My Tickets</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                <?php } else { ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php } ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<div class="hero">
    <h1>Book Your Favorite Events Online</h1>
    <p>Concerts | Workshops | Seminars | Sports</p>
    <a href="#events" class="btn btn-light mt-3">Browse Events</a>
</div>

<!-- Events Section -->
<div class="container mt-5" id="events">
    <h2 class="text-center mb-4">Upcoming Events</h2>
    <div class="row">

        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
        ?>
        <div class="col-md-4 mb-4">
            <div class="card event-card">
                <img src="assets/images/<?php echo $row['image']; ?>" class="card-img-top">
                <div class="card-body">
                    <h5 class="card-title"><?php echo $row['title']; ?></h5>
                    <p class="card-text">
                        📅 <?php echo date("d M Y", strtotime($row['event_date'])); ?><br>
                        📍 <?php echo $row['location']; ?><br>
                        💰 ₹<?php echo $row['price']; ?>
                    </p>
                    <a href="book_ticket.php?event_id=<?php echo $row['event_id']; ?>" class="btn btn-primary w-100">
                        Book Now
                    </a>
                </div>
            </div>
        </div>
        <?php
            }
        } else {
            echo "<p class='text-center'>No events available</p>";
        }
        ?>

    </div>
</div>

<!-- Footer -->
<footer class="bg-dark text-white text-center p-3 mt-5">
    © 2026 Event Ticket Booking System | MCA Project
</footer>

</body>
</html>
