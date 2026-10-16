<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
    header('Location: ../../');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../');
    exit;
}

require dirname(__DIR__, 2) . '/config/meeting_places_repository.php';

if (empty($_SESSION['meeting_places_csrf']) || !is_string($_SESSION['meeting_places_csrf'])) {
    $_SESSION['meeting_places_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['meeting_places_csrf'];

function redirect_admin_meeting_places_page($status, $message)
{
    $query = http_build_query([
        'status' => $status,
        'message' => $message
    ]);

    header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        redirect_admin_meeting_places_page('error', 'Your session expired. Please try again.');
    }

    $action = trim((string) ($_POST['place_action'] ?? ''));
    $places = load_meeting_places_repository();

    try {
        if ($action === 'add_place') {
            $rawName = trim((string) ($_POST['new_place'] ?? ''));
            $newPlace = sanitize_meeting_place_label($rawName);

            if ($newPlace === '') {
                throw new RuntimeException('Meeting place name is required.');
            }

            if (meeting_place_text_length($rawName) > meeting_place_max_length()) {
                throw new RuntimeException('Meeting place name must be ' . meeting_place_max_length() . ' characters or fewer.');
            }

            if (find_meeting_place_index($places, $newPlace) !== -1) {
                throw new RuntimeException('That meeting place already exists.');
            }

            $places[] = $newPlace;

            if (!save_meeting_places_repository($places)) {
                throw new RuntimeException('Unable to save the new meeting place.');
            }

            redirect_admin_meeting_places_page('success', 'Meeting place added. Customers can now choose it.');
        }

        if ($action === 'rename_place') {
            $oldIndex = find_meeting_place_index($places, (string) ($_POST['place_value'] ?? ''));

            if ($oldIndex === -1) {
                throw new RuntimeException('Meeting place to edit was not found.');
            }

            $rawName = trim((string) ($_POST['new_place'] ?? ''));
            $newPlace = sanitize_meeting_place_label($rawName);

            if ($newPlace === '') {
                throw new RuntimeException('New meeting place name is required.');
            }

            if (meeting_place_text_length($rawName) > meeting_place_max_length()) {
                throw new RuntimeException('Meeting place name must be ' . meeting_place_max_length() . ' characters or fewer.');
            }

            $duplicateIndex = find_meeting_place_index($places, $newPlace);

            if ($duplicateIndex !== -1 && $duplicateIndex !== $oldIndex) {
                throw new RuntimeException('Another meeting place already uses that name.');
            }

            $places[$oldIndex] = $newPlace;

            if (!save_meeting_places_repository($places)) {
                throw new RuntimeException('Unable to save the edited meeting place.');
            }

            redirect_admin_meeting_places_page('success', 'Meeting place updated. Existing reservations keep the place they were booked with.');
        }

        if ($action === 'remove_place') {
            $removeIndex = find_meeting_place_index($places, (string) ($_POST['place_value'] ?? ''));

            if ($removeIndex === -1) {
                throw new RuntimeException('Meeting place to remove was not found.');
            }

            if (count($places) <= 1) {
                throw new RuntimeException('At least one meeting place must remain available.');
            }

            array_splice($places, $removeIndex, 1);

            if (!save_meeting_places_repository($places)) {
                throw new RuntimeException('Unable to remove the meeting place.');
            }

            redirect_admin_meeting_places_page('success', 'Meeting place removed. Existing reservations keep the place they were booked with.');
        }

        throw new RuntimeException('Unknown action.');
    } catch (Throwable $error) {
        redirect_admin_meeting_places_page('error', $error->getMessage());
    }
}

$flashType = strtolower(trim((string) ($_GET['status'] ?? '')));
if (!in_array($flashType, ['success', 'error', 'warning'], true)) {
    $flashType = '';
}

$flashMessage = trim((string) ($_GET['message'] ?? ''));
$meetingPlaces = load_meeting_places_repository();
$canRemovePlaces = count($meetingPlaces) > 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meeting Places | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/admin-refinements.css?v=20261008-1">
    <style>
        body {
            margin: 0;
            background: #0c0e12;
            color: #f4f4f4;
            font-family: 'Montserrat', sans-serif;
        }

        .places-shell {
            width: min(100%, 1100px);
            margin: 0 auto;
            padding: 1.25rem 1rem 2.25rem;
            display: grid;
            gap: 1rem;
        }

        .places-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .places-top h1 {
            margin: 0;
            font-size: 1.35rem;
            letter-spacing: 0.01em;
        }

        .places-top a {
            color: #dde531;
            text-decoration: none;
            font-weight: 700;
        }

        .places-note {
            margin: 0;
            color: #b7becb;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .places-flash {
            border-radius: 10px;
            border: 1px solid transparent;
            padding: 0.72rem 0.82rem;
            font-size: 0.9rem;
            line-height: 1.35;
        }

        .places-flash-success {
            background: rgba(75, 160, 80, 0.18);
            border-color: rgba(75, 160, 80, 0.6);
            color: #d8ffd9;
        }

        .places-flash-error {
            background: rgba(190, 67, 67, 0.2);
            border-color: rgba(190, 67, 67, 0.62);
            color: #ffd9d9;
        }

        .places-add-form {
            display: flex;
            gap: 0.6rem;
            align-items: end;
            flex-wrap: wrap;
            padding: 0.85rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
        }

        .places-field {
            display: grid;
            gap: 0.35rem;
            min-width: min(320px, 100%);
            flex: 1 1 320px;
        }

        .places-field label {
            font-size: 0.82rem;
            color: #cdd3de;
            font-weight: 600;
        }

        .places-field input {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 9px;
            padding: 0.55rem 0.62rem;
            font: inherit;
        }

        .places-button {
            border: 1px solid #dde531;
            border-radius: 9px;
            background: #dde531;
            color: #11131a;
            font: inherit;
            font-weight: 700;
            padding: 0.58rem 0.86rem;
            cursor: pointer;
            min-height: 40px;
        }

        .places-button:hover,
        .places-button:focus-visible {
            filter: brightness(0.96);
        }

        .places-button-danger {
            border-color: rgba(219, 89, 89, 0.75);
            background: rgba(219, 89, 89, 0.15);
            color: #ffd3d3;
        }

        .places-button[disabled] {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .places-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 0.8rem;
        }

        .place-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.85rem;
            display: grid;
            gap: 0.65rem;
        }

        .place-card h2 {
            margin: 0;
            font-size: 1.02rem;
            overflow-wrap: anywhere;
        }

        .place-inline-form {
            display: grid;
            gap: 0.45rem;
        }

        .place-inline-form input[type="text"] {
            border: 1px solid rgba(255, 255, 255, 0.23);
            background: #121620;
            color: #f4f4f4;
            border-radius: 8px;
            padding: 0.48rem 0.55rem;
            font: inherit;
            font-size: 0.88rem;
        }

        .place-card-actions {
            display: flex;
            gap: 0.55rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .place-card-actions .places-button {
            min-height: 36px;
            font-size: 0.82rem;
        }

        .place-remove-note {
            margin: 0;
            color: #adb4c0;
            font-size: 0.76rem;
        }

        .places-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1200;
            display: grid;
            place-items: center;
            padding: 1rem;
            background: rgba(5, 7, 10, 0.76);
        }

        .places-modal-backdrop[hidden] {
            display: none;
        }

        .places-modal {
            width: min(100%, 460px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: #121620;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
            padding: 0.9rem;
            display: grid;
            gap: 0.8rem;
        }

        .places-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.6rem;
        }

        .places-modal-head h2 {
            margin: 0;
            font-size: 1rem;
        }

        .places-modal-close {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: transparent;
            color: #f4f4f4;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        .places-modal-close:hover,
        .places-modal-close:focus-visible {
            background: rgba(255, 255, 255, 0.1);
        }

        .places-modal-message {
            margin: 0;
            color: #d7dbe3;
            line-height: 1.45;
            font-size: 0.9rem;
        }

        .places-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .places-modal-cancel {
            border-color: rgba(255, 255, 255, 0.24);
            background: transparent;
            color: #f4f4f4;
        }

        body.places-modal-open {
            overflow: hidden;
        }
    </style>
</head>
<body>
    <main class="places-shell">
        <div class="places-top">
            <h1>Manage Meeting Places</h1>
            <a href="../dashboard/">Back to Dashboard</a>
        </div>

        <p class="places-note">These places appear in the customer's Meeting Place dropdown on the Reservation page. One list is used for both pick-up and meet-up. Reservations that were already placed keep the place they were booked with.</p>

        <?php if ($flashType !== '' && $flashMessage !== ''): ?>
            <div class="places-flash <?php echo htmlspecialchars('places-flash-' . $flashType, ENT_QUOTES, 'UTF-8'); ?>" role="status">
                <?php echo htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="places-add-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="place_action" value="add_place">
            <div class="places-field">
                <label for="new-place-name">Add New Meeting Place</label>
                <input id="new-place-name" name="new_place" type="text" maxlength="120" placeholder="e.g. SM City Carmona Entrance" required>
            </div>
            <button class="places-button" type="submit">Add Place</button>
        </form>

        <section class="places-grid" aria-label="Meeting places">
            <?php foreach ($meetingPlaces as $meetingPlace): ?>
                <article class="place-card">
                    <h2><?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?></h2>

                    <form method="post" action="" class="place-inline-form" data-place-edit-form data-place-label="<?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="place_action" value="rename_place">
                        <input type="hidden" name="place_value" value="<?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?>">
                        <input name="new_place" type="text" maxlength="120" value="<?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?>" required>
                        <div class="place-card-actions">
                            <button class="places-button" type="submit">Save Changes</button>
                        </div>
                    </form>

                    <form method="post" action="" data-place-remove-form data-place-label="<?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="place_action" value="remove_place">
                        <input type="hidden" name="place_value" value="<?php echo htmlspecialchars($meetingPlace, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="place-card-actions">
                            <button class="places-button places-button-danger" type="submit"<?php echo $canRemovePlaces ? '' : ' disabled'; ?>>Remove Place</button>
                            <?php if (!$canRemovePlaces): ?>
                                <p class="place-remove-note">At least one meeting place must remain.</p>
                            <?php endif; ?>
                        </div>
                    </form>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <div class="places-modal-backdrop" data-places-modal-backdrop hidden>
        <section class="places-modal" role="dialog" aria-modal="true" aria-labelledby="places-modal-title" aria-describedby="places-modal-message">
            <div class="places-modal-head">
                <h2 id="places-modal-title" data-places-modal-title>Confirm Action</h2>
                <button class="places-modal-close" type="button" data-places-modal-cancel aria-label="Close">&times;</button>
            </div>

            <p class="places-modal-message" id="places-modal-message" data-places-modal-message>Are you sure you want to continue?</p>

            <div class="places-modal-actions">
                <button class="places-button places-modal-cancel" type="button" data-places-modal-cancel>Cancel</button>
                <button class="places-button places-button-danger" type="button" data-places-modal-confirm>Proceed</button>
            </div>
        </section>
    </div>

    <script>
    (function () {
        var modalBackdrop = document.querySelector('[data-places-modal-backdrop]');
        var modalTitle = document.querySelector('[data-places-modal-title]');
        var modalMessage = document.querySelector('[data-places-modal-message]');
        var modalConfirm = document.querySelector('[data-places-modal-confirm]');
        var modalCancelButtons = document.querySelectorAll('[data-places-modal-cancel]');
        var pendingForm = null;

        function closeModal() {
            pendingForm = null;

            if (!modalBackdrop) {
                return;
            }

            modalBackdrop.hidden = true;
            document.body.classList.remove('places-modal-open');
        }

        function openModal(config) {
            if (!modalBackdrop || !modalTitle || !modalMessage || !modalConfirm) {
                return;
            }

            pendingForm = config && config.form ? config.form : null;
            modalTitle.textContent = config && config.title ? config.title : 'Confirm Action';
            modalMessage.textContent = config && config.message ? config.message : 'Are you sure you want to continue?';
            modalConfirm.textContent = config && config.confirmLabel ? config.confirmLabel : 'Proceed';

            modalBackdrop.hidden = false;
            document.body.classList.add('places-modal-open');
        }

        if (modalConfirm) {
            modalConfirm.addEventListener('click', function () {
                var formToSubmit = pendingForm;
                closeModal();

                if (formToSubmit) {
                    formToSubmit.submit();
                }
            });
        }

        modalCancelButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function (event) {
                if (event.target === modalBackdrop) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modalBackdrop && !modalBackdrop.hidden) {
                closeModal();
            }
        });

        document.querySelectorAll('[data-place-remove-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var placeLabel = form.getAttribute('data-place-label') || 'this meeting place';
                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Remove Meeting Place',
                    message: 'Remove "' + placeLabel + '"? Customers will no longer be able to choose it. Existing reservations keep this place. Proceed or Cancel?',
                    confirmLabel: 'Proceed'
                });
            });
        });

        document.querySelectorAll('[data-place-edit-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var placeLabel = form.getAttribute('data-place-label') || 'this meeting place';
                var nameInput = form.querySelector('input[name="new_place"]');
                var nextLabel = nameInput ? String(nameInput.value || '').trim() : '';

                if (nextLabel === placeLabel) {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();

                openModal({
                    form: form,
                    title: 'Edit Meeting Place',
                    message: 'Change "' + placeLabel + '" to "' + nextLabel + '"? Customers will see the new name. Existing reservations keep the old name. Proceed or Cancel?',
                    confirmLabel: 'Proceed'
                });
            });
        });
    })();
    </script>
</body>
</html>
