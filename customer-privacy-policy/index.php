<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: ../admin/dashboard/');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css?v=20261008-2">
</head>
<body class="policy-page">
    <main class="policy-shell">
        <article class="policy-card reveal">
            <p class="policy-kicker">Creaty</p>
            <h1>Privacy Policy</h1>
            <p class="policy-copy">
                This policy explains how customer information is handled within The Nifty Fifty platform during account registration, booking activity, and support communication.
            </p>

            <section class="policy-section">
                <h2>Information We Collect</h2>
                <p>We may collect your name, email address, account credentials, booking details, and support messages when you use the platform.</p>
            </section>

            <section class="policy-section">
                <h2>How We Use Your Data</h2>
                <p>Your information is used to create and manage accounts, process rentals and reservations, communicate service updates, and improve platform reliability.</p>
            </section>

            <section class="policy-section">
                <h2>Security</h2>
                <p>Reasonable administrative and technical safeguards are applied to protect account information and limit unauthorized access.</p>
            </section>

            <section class="policy-section">
                <h2>Contact</h2>
                <p>If you have questions about this policy, contact the platform administrator before creating an account.</p>
            </section>
        </article>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="../js/script.js"></script>
</body>
</html>
