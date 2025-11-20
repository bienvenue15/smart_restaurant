<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance in Progress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            font-family: "Inter", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, #eef2ff, #e0e7ff 40%, #eef2ff);
            color: #1f2937;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 520px;
            width: 90%;
            box-shadow: 0 25px 70px rgba(79,70,229,0.15);
            text-align: center;
        }
        .card i {
            font-size: 3rem;
            color: #4f46e5;
            margin-bottom: 1rem;
        }
        h1 {
            margin: 0.5rem 0 1rem;
            font-size: 2rem;
            color: #111827;
        }
        p {
            margin: 0.5rem 0;
            color: #4b5563;
            line-height: 1.6;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.9rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 600;
            margin-top: 1.25rem;
        }
        .contact {
            margin-top: 1.75rem;
            font-size: 0.95rem;
        }
        .contact a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <i class="fas fa-screwdriver-wrench"></i>
        <h1>Scheduled Maintenance</h1>
        <p>Our platform is undergoing planned upkeep to keep your operations running smoothly. During this window all portals are temporarily unavailable.</p>
        <p class="badge"><i class="fas fa-clock"></i> We’ll be back shortly</p>
        <div class="contact">
            Need urgent assistance?<br>
            Email us at <a href="mailto:<?php echo MAIL_SUPPORT_ADDRESS; ?>"><?php echo MAIL_SUPPORT_ADDRESS; ?></a>
        </div>
    </div>
</body>
</html>

