<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    header('Location: ../admin/');
    exit;
}

require __DIR__ . '/../config/products_repository.php';
require __DIR__ . '/../config/event_packages_repository.php';
require __DIR__ . '/../config/event_collections_archive_repository.php';

$productArchiveCount = count(load_archived_products_repository());
$howArchiveCount = count(load_archived_how_it_works_repository());
$promoBannerArchiveCount = count(load_archived_promo_banners_repository());
$eventCollectionArchiveCount = count(load_archived_event_collections_repository());
$eventPackageArchiveCount = 0;

foreach (load_event_packages_repository() as $eventPackageRecord) {
    if (!is_array($eventPackageRecord) || empty($eventPackageRecord['archived'])) {
        continue;
    }

    $eventPackageArchiveCount++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin-refinements.css?v=20261008-1">
    <style>
        body { background: #0c0e12; color: #f4f4f4; font-family: 'Montserrat', sans-serif; margin: 0; }
        .archive-shell { width: min(100%, 1040px); margin: 0 auto; padding: 1.25rem 1rem 2.5rem; }
        .archive-top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .archive-top a { color: #dde531; text-decoration: none; font-weight: 700; }
        .archive-intro { margin: 0 0 1rem; color: #adb3bf; }
        .archive-links { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 0.95rem; }
        .archive-link-card {
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px;
            padding: .95rem;
            background: rgba(255,255,255,.03);
            text-decoration: none;
            color: inherit;
            display: grid;
            gap: .55rem;
            transition: transform .16s ease, border-color .16s ease, background-color .16s ease;
        }
        .archive-link-card:hover,
        .archive-link-card:focus-visible {
            transform: translateY(-1px);
            border-color: rgba(221, 229, 49, .6);
            background: rgba(221, 229, 49, .08);
        }
        .archive-link-card h2 { margin: 0; font-size: 1.05rem; }
        .archive-link-card p { margin: 0; color: #c8ced8; line-height: 1.45; font-size: .9rem; }
        .archive-pill {
            justify-self: start;
            font-size: .8rem;
            font-weight: 700;
            color: #11131a;
            background: #dde531;
            border-radius: 999px;
            padding: .18rem .6rem;
        }
    </style>
</head>
<body>
    <main class="archive-shell">
        <div class="archive-top">
            <h1 style="margin:0; font-size:1.35rem;">Archived</h1>
            <a href="../admin/dashboard/">Back to Dashboard</a>
        </div>

        <p class="archive-intro">Archives are split by type for easier restore flow.</p>

        <section class="archive-links" aria-label="Archive sections">
            <a class="archive-link-card" href="products/">
                <span class="archive-pill"><?php echo htmlspecialchars((string) $productArchiveCount, ENT_QUOTES, 'UTF-8'); ?> item(s)</span>
                <h2>Archived Products</h2>
                <p>View and restore archived product cards and images.</p>
            </a>

            <a class="archive-link-card" href="how-it-works/">
                <span class="archive-pill"><?php echo htmlspecialchars((string) $howArchiveCount, ENT_QUOTES, 'UTF-8'); ?> item(s)</span>
                <h2>Archived How It Works Images</h2>
                <p>View and restore archived How It Works slot images.</p>
            </a>

            <a class="archive-link-card" href="promo-banners/">
                <span class="archive-pill"><?php echo htmlspecialchars((string) $promoBannerArchiveCount, ENT_QUOTES, 'UTF-8'); ?> item(s)</span>
                <h2>Archived Promo Banners</h2>
                <p>View and restore archived promo banner slots.</p>
            </a>

            <a class="archive-link-card" href="events-packages/">
                <span class="archive-pill"><?php echo htmlspecialchars((string) $eventPackageArchiveCount, ENT_QUOTES, 'UTF-8'); ?> item(s)</span>
                <h2>Archived Event Packages</h2>
                <p>View and restore archived event packages without moving media files.</p>
            </a>

            <a class="archive-link-card" href="events-collections/">
                <span class="archive-pill"><?php echo htmlspecialchars((string) $eventCollectionArchiveCount, ENT_QUOTES, 'UTF-8'); ?> item(s)</span>
                <h2>Archived Event Collections</h2>
                <p>View and restore archived event collections inside each package gallery.</p>
            </a>
        </section>
    </main>
</body>
</html>
