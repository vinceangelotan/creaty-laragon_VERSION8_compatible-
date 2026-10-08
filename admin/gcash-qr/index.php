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

require_once dirname(__DIR__, 2) . '/config/gcash_qr_repository.php';

$assetBase = '../../';
$adminHomePath = '../dashboard/';
$manageBrandsPath = '../brands/';
$manageCategoriesPath = '../categories/';
$archivedPath = '../../archive/';
$setGcashQrPath = './';
$editTocPath = '../toc/';
$logoutPath = '../logout.php';
$updateEndpoint = '../dashboard/update_gcash_qr.php';

$gcashSettings = load_gcash_qr_repository();
$qrImagePath = trim((string) ($gcashSettings['qrImagePath'] ?? ''));
$qrImageUrl = $qrImagePath !== '' ? $assetBase . ltrim($qrImagePath, '/') : '';
$accountName = (string) ($gcashSettings['accountName'] ?? '');
$accountNumber = (string) ($gcashSettings['accountNumber'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set GCash QR | Creaty</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>css/style.css?v=20261008-2">
    <style>
        body {
            background: #0c0e12;
            color: #f4f4f4;
            font-family: 'Montserrat', sans-serif;
            margin: 0;
        }

        .gcash-shell {
            width: min(100%, 1120px);
            margin: 0 auto;
            padding: 1.2rem 1rem 2.5rem;
        }

        .gcash-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            padding: 1.05rem;
            display: grid;
            gap: 1rem;
        }

        .gcash-head h1 {
            margin: 0;
            font-size: 1.2rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .gcash-head p {
            margin: 0.35rem 0 0;
            color: #d2d7de;
            font-size: 0.9rem;
            max-width: 860px;
        }

        .gcash-editor-grid {
            display: grid;
            grid-template-columns: minmax(0, 360px) minmax(0, 1fr);
            gap: 1.1rem;
            align-items: start;
        }

        .gcash-editor-left {
            display: grid;
            gap: 0.7rem;
            align-content: start;
        }

        .gcash-preview-wrap {
            width: 100%;
            aspect-ratio: 1 / 1;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 14px;
            background: rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
            touch-action: none;
            user-select: none;
        }

        .gcash-preview-wrap.is-cropping {
            cursor: grab;
            box-shadow: inset 0 0 0 2px rgba(221, 229, 49, 0.4);
        }

        .gcash-preview-wrap.is-cropping:active {
            cursor: grabbing;
        }

        .gcash-preview-wrap img {
            position: absolute;
            max-width: none;
            will-change: left, top, width, height;
            pointer-events: none;
            transform-origin: center center;
        }

        .gcash-preview-empty {
            position: absolute;
            inset: 0;
            margin: auto;
            width: calc(100% - 1.5rem);
            height: fit-content;
            text-align: center;
            color: #adb4c0;
            font-size: 0.85rem;
            line-height: 1.45;
        }

        .gcash-preview-overlay {
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 140ms ease;
            border: 2px dashed rgba(221, 229, 49, 0.55);
            border-radius: 14px;
            background: radial-gradient(circle at center, rgba(221, 229, 49, 0.08), transparent 70%);
        }

        .gcash-preview-wrap.is-cropping .gcash-preview-overlay {
            opacity: 1;
        }

        .gcash-controls {
            display: grid;
            gap: 0.65rem;
        }

        .gcash-form-panel {
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            padding: 0.9rem;
            display: grid;
            gap: 0.78rem;
        }

        .gcash-action-row {
            display: flex;
            gap: 0.55rem;
            flex-wrap: wrap;
        }

        .gcash-action-btn {
            min-height: 38px;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.06);
            color: #f4f4f4;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0 0.95rem;
            cursor: pointer;
        }

        .gcash-action-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }

        .gcash-crop-panel {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.03);
            padding: 0.75rem;
            display: grid;
            gap: 0.6rem;
        }

        .gcash-crop-panel p {
            margin: 0;
            color: #d2d7de;
            font-size: 0.8rem;
        }

        .gcash-crop-panel input[type='range'] {
            width: 100%;
        }

        .gcash-fields {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.8rem;
        }

        .gcash-fields label {
            display: grid;
            gap: 0.4rem;
            font-size: 0.82rem;
            color: #d2d7de;
        }

        .gcash-fields input {
            min-height: 44px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.03);
            color: #f4f4f4;
            padding: 0 0.8rem;
        }

        .gcash-submit-row {
            display: flex;
            justify-content: flex-end;
            gap: 0.6rem;
            flex-wrap: wrap;
            margin-top: 0.35rem;
        }

        .gcash-feedback {
            margin: 0;
            font-size: 0.84rem;
            line-height: 1.45;
            min-height: 1.2rem;
        }

        .gcash-feedback.is-success {
            color: #bff0cd;
        }

        .gcash-feedback.is-error {
            color: #ffbebe;
        }

        @media (max-width: 860px) {
            .gcash-editor-grid {
                grid-template-columns: 1fr;
            }

            .gcash-submit-row {
                justify-content: stretch;
            }

            .gcash-submit-row .gcash-action-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="topbar topbar-admin">
            <a class="brand-badge" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">
                <img src="<?php echo htmlspecialchars($assetBase, ENT_QUOTES, 'UTF-8'); ?>assets/images/main_logo.png" alt="The Nifty Fifty">
            </a>

            <div class="topbar-admin-actions">
                <div class="dropdown topbar-account-menu">
                    <button class="account-pill account-pill-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Admin
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end account-dropdown-menu">
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($adminHomePath, ENT_QUOTES, 'UTF-8'); ?>">Admin Home</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageBrandsPath, ENT_QUOTES, 'UTF-8'); ?>">Manage Brands</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($manageCategoriesPath, ENT_QUOTES, 'UTF-8'); ?>">Manage Categories</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($archivedPath, ENT_QUOTES, 'UTF-8'); ?>">Archived</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($setGcashQrPath, ENT_QUOTES, 'UTF-8'); ?>">Set GCash QR</a></li>
                        <li><a class="dropdown-item" href="<?php echo htmlspecialchars($editTocPath, ENT_QUOTES, 'UTF-8'); ?>">Edit TOC</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item account-logout-item" href="<?php echo htmlspecialchars($logoutPath, ENT_QUOTES, 'UTF-8'); ?>">Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <main class="gcash-shell">
        <section class="gcash-card">
            <header class="gcash-head">
                <h1>Set GCash QR</h1>
                <p>Upload and crop a square GCash QR code. Customers will see this image, account name, and account number during GCash booking confirmation.</p>
            </header>

            <div class="gcash-editor-grid">
                <section class="gcash-editor-left">
                    <div class="gcash-preview-wrap" data-gcash-preview-wrap>
                        <img src="<?php echo htmlspecialchars($qrImageUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="GCash QR preview" data-gcash-preview-img<?php echo $qrImageUrl === '' ? ' hidden' : ''; ?>>
                        <p class="gcash-preview-empty" data-gcash-preview-empty<?php echo $qrImageUrl !== '' ? ' hidden' : ''; ?>>No QR image set yet. Click Browse to upload.</p>
                        <div class="gcash-preview-overlay" aria-hidden="true"></div>
                    </div>

                    <div class="gcash-controls">
                        <div class="gcash-action-row">
                            <input type="file" accept="image/*" data-gcash-file hidden>
                            <button class="gcash-action-btn" type="button" data-gcash-browse>Browse</button>
                            <button class="gcash-action-btn" type="button" data-gcash-recrop>Recrop</button>
                        </div>

                        <div class="gcash-crop-panel" data-gcash-crop-panel hidden>
                            <p>Drag to reposition. Use the slider or mouse wheel to zoom.</p>
                            <input type="range" min="1" max="3" step="0.01" value="1" data-gcash-zoom>
                            <div class="gcash-action-row">
                                <button class="gcash-action-btn" type="button" data-gcash-crop-cancel>Cancel Crop</button>
                                <button class="gcash-action-btn" type="button" data-gcash-crop-save>Save Crop</button>
                            </div>
                        </div>
                    </div>
                </section>

                <form data-gcash-form class="gcash-form-panel">
                    <div class="gcash-fields">
                        <label>
                            <span>GCash Account Name</span>
                            <input type="text" maxlength="120" data-gcash-name value="<?php echo htmlspecialchars($accountName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter account owner name" required>
                        </label>
                        <label>
                            <span>GCash Number</span>
                            <input type="text" maxlength="40" data-gcash-number value="<?php echo htmlspecialchars($accountNumber, ENT_QUOTES, 'UTF-8'); ?>" placeholder="09xxxxxxxxx" required>
                        </label>
                    </div>

                    <div class="gcash-submit-row">
                        <button class="gcash-action-btn" type="submit" data-gcash-submit>Save GCash QR Settings</button>
                    </div>

                    <p class="gcash-feedback" data-gcash-feedback></p>
                </form>
            </div>
        </section>
    </main>

    <script>
        window.__creatyAdminGcashSettings = {
            imageUrl: <?php echo json_encode($qrImageUrl, JSON_UNESCAPED_SLASHES); ?>,
            accountName: <?php echo json_encode($accountName, JSON_UNESCAPED_SLASHES); ?>,
            accountNumber: <?php echo json_encode($accountNumber, JSON_UNESCAPED_SLASHES); ?>,
            assetBase: <?php echo json_encode($assetBase, JSON_UNESCAPED_SLASHES); ?>
        };
        window.__creatyAdminGcashUpdateEndpoint = <?php echo json_encode($updateEndpoint, JSON_UNESCAPED_SLASHES); ?>;
    </script>

    <script>
    (function () {
        var settings = window.__creatyAdminGcashSettings || {};
        var updateEndpoint = typeof window.__creatyAdminGcashUpdateEndpoint === 'string'
            ? String(window.__creatyAdminGcashUpdateEndpoint || '')
            : '';

        var previewWrap = document.querySelector('[data-gcash-preview-wrap]');
        var previewImage = document.querySelector('[data-gcash-preview-img]');
        var previewEmpty = document.querySelector('[data-gcash-preview-empty]');
        var fileInput = document.querySelector('[data-gcash-file]');
        var browseButton = document.querySelector('[data-gcash-browse]');
        var recropButton = document.querySelector('[data-gcash-recrop]');
        var cropPanel = document.querySelector('[data-gcash-crop-panel]');
        var zoomInput = document.querySelector('[data-gcash-zoom]');
        var cropCancelButton = document.querySelector('[data-gcash-crop-cancel]');
        var cropSaveButton = document.querySelector('[data-gcash-crop-save]');
        var form = document.querySelector('[data-gcash-form]');
        var nameInput = document.querySelector('[data-gcash-name]');
        var numberInput = document.querySelector('[data-gcash-number]');
        var submitButton = document.querySelector('[data-gcash-submit]');
        var feedback = document.querySelector('[data-gcash-feedback]');

        if (!previewWrap || !previewImage || !fileInput || !form || !nameInput || !numberInput || !submitButton) {
            return;
        }

        var cropState = {
            zoom: 1,
            offsetX: 0,
            offsetY: 0,
            isCropping: false,
            isDragging: false,
            dragPointerId: null,
            dragStartClientX: 0,
            dragStartClientY: 0,
            dragStartOffsetX: 0,
            dragStartOffsetY: 0,
            previewBeforeCrop: ''
        };

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }

            feedback.textContent = String(message || '');
            feedback.classList.remove('is-success');
            feedback.classList.remove('is-error');

            if (type === 'success') {
                feedback.classList.add('is-success');
            } else if (type === 'error') {
                feedback.classList.add('is-error');
            }
        }

        function setPreviewSource(source) {
            var src = String(source || '').trim();

            if (!src) {
                previewImage.hidden = true;
                previewImage.removeAttribute('src');
                if (previewEmpty) {
                    previewEmpty.hidden = false;
                }
                updateRecropState();
                return;
            }

            previewImage.src = src;
            previewImage.hidden = false;
            if (previewEmpty) {
                previewEmpty.hidden = true;
            }
            updateRecropState();
        }

        function updateRecropState() {
            if (!recropButton) {
                return;
            }

            recropButton.disabled = previewImage.hidden || !previewImage.src;
        }

        function getRenderMetrics(zoomValue) {
            var wrapWidth = previewWrap.clientWidth;
            var wrapHeight = previewWrap.clientHeight;
            var naturalWidth = previewImage.naturalWidth;
            var naturalHeight = previewImage.naturalHeight;

            if (!wrapWidth || !wrapHeight || !naturalWidth || !naturalHeight) {
                return null;
            }

            var baseScale = Math.max(wrapWidth / naturalWidth, wrapHeight / naturalHeight);
            var scale = baseScale * Math.max(1, zoomValue);
            var drawWidth = naturalWidth * scale;
            var drawHeight = naturalHeight * scale;
            var maxShiftX = Math.max(0, (drawWidth - wrapWidth) / 2);
            var maxShiftY = Math.max(0, (drawHeight - wrapHeight) / 2);

            return {
                wrapWidth: wrapWidth,
                wrapHeight: wrapHeight,
                drawWidth: drawWidth,
                drawHeight: drawHeight,
                maxShiftX: maxShiftX,
                maxShiftY: maxShiftY,
                scale: scale
            };
        }

        function clampOffsets(nextX, nextY) {
            var metrics = getRenderMetrics(cropState.zoom);

            if (!metrics) {
                return {
                    x: nextX,
                    y: nextY
                };
            }

            return {
                x: Math.min(metrics.maxShiftX, Math.max(-metrics.maxShiftX, nextX)),
                y: Math.min(metrics.maxShiftY, Math.max(-metrics.maxShiftY, nextY))
            };
        }

        function syncPreviewTransform() {
            if (previewImage.hidden || !previewImage.src) {
                return;
            }

            var metrics = getRenderMetrics(cropState.zoom);
            if (!metrics) {
                return;
            }

            var clamped = clampOffsets(cropState.offsetX, cropState.offsetY);
            cropState.offsetX = clamped.x;
            cropState.offsetY = clamped.y;

            previewImage.style.width = String(metrics.drawWidth) + 'px';
            previewImage.style.height = String(metrics.drawHeight) + 'px';
            previewImage.style.left = String(((metrics.wrapWidth - metrics.drawWidth) / 2) + cropState.offsetX) + 'px';
            previewImage.style.top = String(((metrics.wrapHeight - metrics.drawHeight) / 2) + cropState.offsetY) + 'px';
        }

        function setCropMode(enabled) {
            cropState.isCropping = Boolean(enabled);

            if (cropPanel) {
                cropPanel.hidden = !cropState.isCropping;
            }

            previewWrap.classList.toggle('is-cropping', cropState.isCropping);
        }

        function resetCropState() {
            cropState.zoom = 1;
            cropState.offsetX = 0;
            cropState.offsetY = 0;
            cropState.isDragging = false;
            cropState.dragPointerId = null;

            if (zoomInput) {
                zoomInput.value = '1';
            }

            syncPreviewTransform();
        }

        function buildCroppedDataUrl() {
            if (previewImage.hidden || !previewImage.src || !previewImage.naturalWidth || !previewImage.naturalHeight) {
                return '';
            }

            var metrics = getRenderMetrics(cropState.zoom);
            if (!metrics) {
                return '';
            }

            var outputSize = 1000;
            var canvas = document.createElement('canvas');
            canvas.width = outputSize;
            canvas.height = outputSize;

            var context = canvas.getContext('2d');
            if (!context) {
                return '';
            }

            var offsetScaleX = metrics.wrapWidth > 0 ? (outputSize / metrics.wrapWidth) : 1;
            var offsetScaleY = metrics.wrapHeight > 0 ? (outputSize / metrics.wrapHeight) : 1;
            var drawWidth = metrics.drawWidth * (outputSize / metrics.wrapWidth);
            var drawHeight = metrics.drawHeight * (outputSize / metrics.wrapHeight);
            var drawX = ((outputSize - drawWidth) / 2) + (cropState.offsetX * offsetScaleX);
            var drawY = ((outputSize - drawHeight) / 2) + (cropState.offsetY * offsetScaleY);

            context.clearRect(0, 0, outputSize, outputSize);
            context.drawImage(previewImage, drawX, drawY, drawWidth, drawHeight);

            return canvas.toDataURL('image/png');
        }

        function openCropFromCurrentPreview() {
            if (previewImage.hidden || !previewImage.src) {
                return;
            }

            cropState.previewBeforeCrop = previewImage.src;
            resetCropState();
            setCropMode(true);
            syncPreviewTransform();
        }

        if (browseButton) {
            browseButton.addEventListener('click', function () {
                fileInput.click();
            });
        }

        if (recropButton) {
            recropButton.addEventListener('click', function () {
                openCropFromCurrentPreview();
            });
        }

        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;

            if (!file) {
                return;
            }

            var reader = new FileReader();
            reader.onload = function (event) {
                var imageData = String(event && event.target && event.target.result ? event.target.result : '');

                if (!imageData) {
                    return;
                }

                cropState.previewBeforeCrop = previewImage.hidden ? '' : (previewImage.src || '');
                setPreviewSource(imageData);
                resetCropState();
                setCropMode(true);
                syncPreviewTransform();
                setFeedback('', '');
            };

            reader.readAsDataURL(file);
        });

        previewImage.addEventListener('load', function () {
            syncPreviewTransform();
            updateRecropState();
        });

        if (zoomInput) {
            zoomInput.addEventListener('input', function () {
                cropState.zoom = Number.parseFloat(zoomInput.value) || 1;
                var clamped = clampOffsets(cropState.offsetX, cropState.offsetY);
                cropState.offsetX = clamped.x;
                cropState.offsetY = clamped.y;
                syncPreviewTransform();
            });
        }

        previewWrap.addEventListener('wheel', function (event) {
            if (!cropState.isCropping || !zoomInput) {
                return;
            }

            event.preventDefault();

            var minZoom = Number.parseFloat(zoomInput.min || '1');
            var maxZoom = Number.parseFloat(zoomInput.max || '3');
            var stepZoom = Number.parseFloat(zoomInput.step || '0.01');
            var direction = event.deltaY < 0 ? 1 : -1;
            var nextZoom = cropState.zoom + (direction * (stepZoom * 5));

            nextZoom = Math.min(maxZoom, Math.max(minZoom, nextZoom));
            nextZoom = Math.round(nextZoom * 100) / 100;

            cropState.zoom = nextZoom;
            zoomInput.value = String(nextZoom);

            var clamped = clampOffsets(cropState.offsetX, cropState.offsetY);
            cropState.offsetX = clamped.x;
            cropState.offsetY = clamped.y;
            syncPreviewTransform();
        }, { passive: false });

        previewWrap.addEventListener('pointerdown', function (event) {
            if (!cropState.isCropping || event.button !== 0) {
                return;
            }

            event.preventDefault();

            cropState.isDragging = true;
            cropState.dragPointerId = event.pointerId;
            cropState.dragStartClientX = event.clientX;
            cropState.dragStartClientY = event.clientY;
            cropState.dragStartOffsetX = cropState.offsetX;
            cropState.dragStartOffsetY = cropState.offsetY;
            previewWrap.setPointerCapture(event.pointerId);
        });

        previewWrap.addEventListener('pointermove', function (event) {
            if (!cropState.isCropping || !cropState.isDragging || cropState.dragPointerId !== event.pointerId) {
                return;
            }

            var nextX = cropState.dragStartOffsetX + (event.clientX - cropState.dragStartClientX);
            var nextY = cropState.dragStartOffsetY + (event.clientY - cropState.dragStartClientY);
            var clamped = clampOffsets(nextX, nextY);
            cropState.offsetX = clamped.x;
            cropState.offsetY = clamped.y;
            syncPreviewTransform();
        });

        function stopDragging(event) {
            if (!cropState.isDragging || cropState.dragPointerId !== event.pointerId) {
                return;
            }

            cropState.isDragging = false;
            cropState.dragPointerId = null;
            previewWrap.releasePointerCapture(event.pointerId);
        }

        previewWrap.addEventListener('pointerup', stopDragging);
        previewWrap.addEventListener('pointercancel', stopDragging);

        if (cropCancelButton) {
            cropCancelButton.addEventListener('click', function () {
                setPreviewSource(cropState.previewBeforeCrop || '');
                resetCropState();
                setCropMode(false);
            });
        }

        if (cropSaveButton) {
            cropSaveButton.addEventListener('click', function () {
                var croppedDataUrl = buildCroppedDataUrl();

                if (!croppedDataUrl) {
                    setFeedback('Please select and crop an image first.', 'error');
                    return;
                }

                setPreviewSource(croppedDataUrl);
                cropState.previewBeforeCrop = croppedDataUrl;
                resetCropState();
                setCropMode(false);
                setFeedback('', '');
            });
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            var accountName = String(nameInput.value || '').trim();
            var accountNumber = String(numberInput.value || '').trim();

            if (accountName === '') {
                setFeedback('GCash account name is required.', 'error');
                nameInput.focus();
                return;
            }

            if (accountNumber === '') {
                setFeedback('GCash account number is required.', 'error');
                numberInput.focus();
                return;
            }

            if (cropState.isCropping) {
                var autoCropped = buildCroppedDataUrl();

                if (autoCropped) {
                    setPreviewSource(autoCropped);
                    cropState.previewBeforeCrop = autoCropped;
                }

                resetCropState();
                setCropMode(false);
            }

            var previewSrc = !previewImage.hidden ? String(previewImage.src || '') : '';

            if (!previewSrc) {
                setFeedback('Please upload a GCash QR image.', 'error');
                return;
            }

            if (!updateEndpoint) {
                setFeedback('GCash update endpoint is unavailable.', 'error');
                return;
            }

            var imageDataUrl = previewSrc.indexOf('data:image/') === 0 ? previewSrc : '';

            submitButton.disabled = true;
            setFeedback('Saving GCash QR settings...', '');

            fetch(updateEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    accountName: accountName,
                    accountNumber: accountNumber,
                    imageDataUrl: imageDataUrl
                })
            })
                .then(function (response) {
                    return response.text().then(function (rawBody) {
                        var payload = {};

                        try {
                            payload = JSON.parse(rawBody);
                        } catch (error) {
                            payload = {};
                        }

                        if (!response.ok || !payload || payload.ok !== true) {
                            var message = payload && payload.message ? String(payload.message) : 'Unable to save GCash QR settings.';
                            throw new Error(message);
                        }

                        return payload;
                    });
                })
                .then(function (payload) {
                    var saved = payload.settings && typeof payload.settings === 'object' ? payload.settings : {};
                    var nextPath = String(saved.qrImagePath || '').trim();
                    var nextImageUrl = nextPath ? (String(settings.assetBase || '../../') + nextPath.replace(/^\/+/, '')) : '';

                    if (nextImageUrl) {
                        setPreviewSource(nextImageUrl + '?t=' + String(Date.now()));
                    }

                    setFeedback('GCash QR settings updated.', 'success');
                })
                .catch(function (error) {
                    setFeedback(error && error.message ? String(error.message) : 'Unable to save GCash QR settings.', 'error');
                })
                .finally(function () {
                    submitButton.disabled = false;
                });
        });

        setPreviewSource(String(settings.imageUrl || '').trim());
        updateRecropState();
        resetCropState();
        setCropMode(false);
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
