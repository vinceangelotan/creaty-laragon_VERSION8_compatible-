<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || isset($_SESSION['customer_id'])) {
    header('Location: ../../admin/');
    exit;
}

require __DIR__ . '/../../config/event_collections_archive_repository.php';

$archivedItems = load_archived_event_collections_repository();

usort($archivedItems, static function ($left, $right) {
    $leftTime = strtotime((string) ($left['archivedAt'] ?? ''));
    $rightTime = strtotime((string) ($right['archivedAt'] ?? ''));

    return $rightTime <=> $leftTime;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Event Collections | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/admin-refinements.css?v=20261008-1">
    <style>
        body { background: #0c0e12; color: #f4f4f4; font-family: 'Montserrat', sans-serif; margin: 0; }
        .archive-shell { width: min(100%, 1120px); margin: 0 auto; padding: 1.25rem 1rem 2.5rem; }
        .archive-top { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .archive-top a { color: #dde531; text-decoration: none; font-weight: 700; }
        .archive-count { color: #adb3bf; margin: 0 0 1rem; }
        .archive-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 0.95rem; }
        .archive-card { border: 1px solid rgba(255,255,255,.2); border-radius: 12px; padding: .9rem; background: rgba(255,255,255,.03); display: grid; gap: .55rem; }
        .archive-card h2 { margin: 0; font-size: 1rem; }
        .archive-meta { margin: 0; color: #b5bcc9; font-size: .84rem; }
        .archive-error { margin: .35rem 0 0; color: #ffb4b4; font-size: .82rem; }
        .archive-actions { display: flex; justify-content: flex-end; margin-top: .15rem; }
        .archive-restore-button {
            border: 1px solid #4ba050;
            border-radius: 8px;
            background: #57b95c;
            color: #fff;
            font-size: .78rem;
            font-weight: 700;
            padding: .36rem .7rem;
            cursor: pointer;
            transition: transform .16s ease, background-color .16s ease;
        }
        .archive-restore-button:hover,
        .archive-restore-button:focus-visible { transform: translateY(-1px); background: #4ca752; }
        .archive-restore-button:disabled { opacity: .68; cursor: default; transform: none; }
    </style>
</head>
<body>
    <main class="archive-shell" data-event-collection-archive-shell data-event-collection-archive-restore-endpoint="../../admin/dashboard/restore_archived_event_collection.php">
        <div class="archive-top">
            <h1 style="margin:0; font-size:1.35rem;">Archived Event Collections</h1>
            <a href="../">Back to Archived</a>
        </div>

        <p class="archive-count" data-event-collection-archive-count><?php echo htmlspecialchars((string) count($archivedItems), ENT_QUOTES, 'UTF-8'); ?> item(s) in archive.</p>
        <p class="archive-error" data-event-collection-archive-error hidden></p>

        <?php if (!$archivedItems): ?>
            <p class="archive-meta" data-event-collection-archive-empty>No archived event collections yet.</p>
        <?php else: ?>
            <section class="archive-grid" aria-label="Archived event collections list">
                <?php foreach ($archivedItems as $item): ?>
                    <?php
                        $archivedAt = trim((string) ($item['archivedAt'] ?? ''));
                        $archivedAtTimestamp = $archivedAt !== '' ? strtotime($archivedAt) : false;
                        $archivedAtLabel = $archivedAtTimestamp
                            ? date('M d, Y h:i A', $archivedAtTimestamp)
                            : ($archivedAt !== '' ? $archivedAt : 'Unknown');
                    ?>
                    <article class="archive-card" data-event-collection-archive-card>
                        <h2><?php echo htmlspecialchars((string) ($item['collectionLabel'] ?? 'Collection'), ENT_QUOTES, 'UTF-8'); ?></h2>
                        <p class="archive-meta">Category: <?php echo htmlspecialchars((string) ($item['categoryLabel'] ?? 'Untitled'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Package: <?php echo htmlspecialchars((string) ($item['packageTitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Package key: <?php echo htmlspecialchars((string) ($item['packageKey'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Package folder: <?php echo htmlspecialchars((string) ($item['packageFolder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Collection folder: <?php echo htmlspecialchars((string) ($item['collectionFolder'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Archive key: <?php echo htmlspecialchars((string) ($item['archiveKey'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        <p class="archive-meta">Archived: <?php echo htmlspecialchars($archivedAtLabel, ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="archive-actions">
                            <button class="archive-restore-button" type="button" data-event-collection-archive-restore data-archive-key="<?php echo htmlspecialchars((string) ($item['archiveKey'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">Restore</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>

    <script>
    (function () {
        var shell = document.querySelector('[data-event-collection-archive-shell]');

        if (!shell) {
            return;
        }

        var endpoint = shell.getAttribute('data-event-collection-archive-restore-endpoint') || '';
        var countLabel = shell.querySelector('[data-event-collection-archive-count]');
        var emptyLabel = shell.querySelector('[data-event-collection-archive-empty]');
        var errorLabel = shell.querySelector('[data-event-collection-archive-error]');
        var grid = shell.querySelector('.archive-grid');

        function setError(message) {
            if (!errorLabel) {
                return;
            }

            var text = String(message || '').trim();
            errorLabel.textContent = text;
            errorLabel.hidden = text === '';
        }

        function updateArchiveCount() {
            if (!countLabel) {
                return;
            }

            var count = shell.querySelectorAll('[data-event-collection-archive-card]').length;
            countLabel.textContent = String(count) + ' item(s) in archive.';

            if (count === 0) {
                if (grid) {
                    grid.remove();
                }

                if (!emptyLabel) {
                    emptyLabel = document.createElement('p');
                    emptyLabel.className = 'archive-meta';
                    emptyLabel.setAttribute('data-event-collection-archive-empty', 'true');
                    emptyLabel.textContent = 'No archived event collections yet.';
                    shell.appendChild(emptyLabel);
                }
            }
        }

        shell.addEventListener('click', function (event) {
            var button = event.target.closest('[data-event-collection-archive-restore]');

            if (!button || !endpoint) {
                return;
            }

            var archiveKey = (button.getAttribute('data-archive-key') || '').trim();
            if (!archiveKey) {
                return;
            }

            setError('');
            button.disabled = true;
            button.textContent = 'Restoring...';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ archiveKey: archiveKey })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        return {
                            ok: response.ok,
                            payload: payload
                        };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.payload || !result.payload.ok) {
                        var message = result.payload && result.payload.message ? result.payload.message : 'Unable to restore event collection.';
                        throw new Error(message);
                    }

                    var card = button.closest('[data-event-collection-archive-card]');
                    if (card) {
                        card.remove();
                    }

                    updateArchiveCount();
                })
                .catch(function (error) {
                    setError(error.message || 'Unable to restore event collection.');
                    button.disabled = false;
                    button.textContent = 'Restore';
                });
        });
    })();
    </script>
</body>
</html>
