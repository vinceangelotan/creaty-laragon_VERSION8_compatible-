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

require_once dirname(__DIR__, 2) . '/config/customer_terms_repository.php';

$assetBase = '../../';
$adminHomePath = '../dashboard/';
$manageBrandsPath = '../brands/';
$manageCategoriesPath = '../categories/';
$archivedPath = '../../archive/';
$setGcashQrPath = '../gcash-qr/';
$editTocPath = './';
$logoutPath = '../logout.php';
$updateEndpoint = '../dashboard/update_customer_terms.php';

$termsSettings = load_customer_terms_repository();
$tocContentHtml = (string) ($termsSettings['contentHtml'] ?? '');
$tocDisplayHtml = customer_terms_prepare_display_html($tocContentHtml);
$tocUpdatedAt = trim((string) ($termsSettings['updatedAt'] ?? ''));

function format_admin_toc_updated_at_label($value)
{
    $raw = trim((string) $value);

    if ($raw === '') {
        return 'Not yet updated';
    }

    try {
        $parsed = new DateTimeImmutable($raw);
        $parsed = $parsed->setTimezone(new DateTimeZone('Asia/Manila'));

        return $parsed->format('M d, Y h:i A') . ' (Asia/Manila)';
    } catch (Throwable $error) {
        return $raw;
    }
}

$tocUpdatedAtLabel = format_admin_toc_updated_at_label($tocUpdatedAt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit TOC | Creaty</title>
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

        .toc-shell {
            width: min(100%, 1200px);
            margin: 0 auto;
            padding: 1.2rem 1rem 2.5rem;
        }

        .toc-card {
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.03);
            padding: 1.1rem;
            display: grid;
            gap: 1rem;
        }

        .toc-head h1 {
            margin: 0;
            font-size: 1.22rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .toc-head p {
            margin: 0.42rem 0 0;
            color: #d2d7de;
            font-size: 0.9rem;
            line-height: 1.55;
        }

        .toc-guide-list {
            margin: 0.75rem 0 0;
            padding-left: 1.1rem;
            color: #d2d7de;
            display: grid;
            gap: 0.3rem;
            font-size: 0.84rem;
        }

        .toc-editor-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 0.95fr);
            align-items: stretch;
        }

        .toc-editor-panel,
        .toc-preview-panel {
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.02);
            padding: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 0.72rem;
            align-items: stretch;
        }

        .toc-toolbar {
            display: flex;
            gap: 0.44rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .toc-toolbar button,
        .toc-toolbar select,
        .toc-toolbar label {
            min-height: 36px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            border-radius: 9px;
            background: rgba(255, 255, 255, 0.06);
            color: #f4f4f4;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0 0.72rem;
        }

        .toc-toolbar button {
            cursor: pointer;
        }

        .toc-toolbar button:hover,
        .toc-toolbar button:focus-visible,
        .toc-toolbar select:hover,
        .toc-toolbar select:focus-visible {
            border-color: rgba(221, 229, 49, 0.6);
            background: rgba(221, 229, 49, 0.1);
            outline: none;
        }

        .toc-toolbar select {
            min-width: 180px;
            appearance: none;
            cursor: pointer;
        }

        .toc-color-picker {
            display: flex;
            gap: 0.45rem;
            align-items: center;
            cursor: pointer;
        }

        .toc-color-picker span {
            color: #d6dce3;
        }

        .toc-color-picker input[type='color'] {
            width: 34px;
            height: 24px;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .toc-editor-surface {
            min-height: 460px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(13, 15, 19, 0.84);
            color: #f2f2f2;
            padding: 0.82rem;
            overflow: visible;
            line-height: 1.55;
            font-size: 0.9rem;
            outline: none;
            white-space: normal;
            word-break: break-word;
            flex: 1 1 auto;
        }

        .toc-editor-surface:focus-visible {
            border-color: rgba(221, 229, 49, 0.56);
            box-shadow: 0 0 0 1px rgba(221, 229, 49, 0.22);
        }

        .toc-editor-surface hr {
            border: none;
            border-top: 2px dashed rgba(255, 255, 255, 0.34);
            margin: 0.8rem 0;
        }

        .toc-editor-surface p,
        .toc-editor-surface h2,
        .toc-editor-surface h3,
        .toc-editor-surface h4,
        .toc-editor-surface h5,
        .toc-editor-surface h6,
        .toc-editor-surface ul,
        .toc-editor-surface ol {
            margin: 0 0 0.6rem;
        }

        .toc-editor-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 0.55rem;
        }

        .toc-action-btn {
            min-height: 38px;
            border: 1px solid rgba(255, 255, 255, 0.26);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            color: #f4f4f4;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            padding: 0 0.92rem;
            cursor: pointer;
        }

        .toc-action-btn:hover,
        .toc-action-btn:focus-visible {
            border-color: rgba(221, 229, 49, 0.62);
            background: rgba(221, 229, 49, 0.14);
            outline: none;
        }

        .toc-action-btn.is-primary {
            border-color: rgba(221, 229, 49, 0.66);
            background: rgba(221, 229, 49, 0.18);
            color: #f7fbe2;
        }

        .toc-action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .toc-feedback {
            margin: 0;
            min-height: 1.2rem;
            font-size: 0.84rem;
            line-height: 1.45;
        }

        .toc-feedback.is-success {
            color: #bff0cd;
        }

        .toc-feedback.is-error {
            color: #ffbebe;
        }

        .toc-updated {
            margin: 0;
            color: #adb4c0;
            font-size: 0.78rem;
            letter-spacing: 0.02em;
        }

        .toc-preview-panel h2 {
            margin: 0;
            font-size: 0.9rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #e9edf2;
        }

        .toc-preview-markdown {
            min-height: 460px;
            max-height: none;
            overflow: visible;
            flex: 1 1 auto;
        }

        @media (max-width: 980px) {
            .toc-editor-grid {
                grid-template-columns: 1fr;
            }

            .toc-editor-surface,
            .toc-preview-markdown {
                min-height: 360px;
            }
        }

        @media (max-width: 640px) {
            .toc-toolbar {
                align-items: stretch;
            }

            .toc-toolbar button,
            .toc-toolbar select,
            .toc-toolbar label {
                width: 100%;
                justify-content: center;
            }

            .toc-editor-actions {
                justify-content: stretch;
            }

            .toc-action-btn {
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

    <main class="toc-shell">
        <section class="toc-card">
            <header class="toc-head">
                <h1>Edit Customer Terms and Conditions</h1>
                <p>Use the editor below to customize TOC copy, formatting, lists, and font colors. Save once done, and the customer cart page will render your latest version.</p>
                <ul class="toc-guide-list">
                    <li>Type a line with only -------- to start a new major container in customer view.</li>
                    <li>Bulleted and numbered entries become mini-containers inside each major container.</li>
                    <li>Use the Customer Preview panel to verify readability and emphasis before saving.</li>
                </ul>
            </header>

            <div class="toc-editor-grid">
                <section class="toc-editor-panel" aria-label="TOC editor panel">
                    <div class="toc-toolbar" role="toolbar" aria-label="TOC formatting tools">
                        <select data-toc-block-format aria-label="Text block format">
                            <option value="">Paragraph</option>
                            <option value="H2">Heading 2</option>
                            <option value="H3">Heading 3</option>
                            <option value="H4">Heading 4</option>
                        </select>
                        <button type="button" data-toc-command="bold">Bold</button>
                        <button type="button" data-toc-command="italic">Italic</button>
                        <button type="button" data-toc-command="underline">Underline</button>
                        <button type="button" data-toc-command="insertUnorderedList">Bullets</button>
                        <button type="button" data-toc-command="insertOrderedList">Numbering</button>
                        <button type="button" data-toc-divider>Divider</button>
                        <button type="button" data-toc-command="removeFormat">Clear Format</button>
                        <label class="toc-color-picker">
                            <span>Text Color</span>
                            <input type="color" value="#f4f4f4" data-toc-text-color aria-label="Text color">
                        </label>
                    </div>

                    <div class="toc-editor-surface" contenteditable="true" data-toc-editor><?php echo $tocContentHtml; ?></div>

                    <div class="toc-editor-actions">
                        <button class="toc-action-btn" type="button" data-toc-reset>Reset to Last Saved</button>
                        <button class="toc-action-btn is-primary" type="button" data-toc-save>Save TOC Changes</button>
                    </div>

                    <p class="toc-feedback" data-toc-feedback></p>
                    <p class="toc-updated">Last updated: <span data-toc-updated-at><?php echo htmlspecialchars($tocUpdatedAtLabel, ENT_QUOTES, 'UTF-8'); ?></span></p>
                </section>

                <section class="toc-preview-panel" aria-label="Customer TOC preview panel">
                    <h2>Customer Preview</h2>
                    <article class="cart-terms-markdown toc-preview-markdown" data-toc-preview><?php echo $tocDisplayHtml; ?></article>
                </section>
            </div>
        </section>
    </main>

    <script>
        window.__creatyAdminTocEditorConfig = {
            updateEndpoint: <?php echo json_encode($updateEndpoint, JSON_UNESCAPED_SLASHES); ?>,
            initialContentHtml: <?php echo json_encode($tocContentHtml, JSON_UNESCAPED_SLASHES); ?>,
            initialDisplayHtml: <?php echo json_encode($tocDisplayHtml, JSON_UNESCAPED_SLASHES); ?>,
            updatedAtLabel: <?php echo json_encode($tocUpdatedAtLabel, JSON_UNESCAPED_SLASHES); ?>
        };
    </script>

    <script>
    (function () {
        var config = window.__creatyAdminTocEditorConfig || {};

        var editor = document.querySelector('[data-toc-editor]');
        var preview = document.querySelector('[data-toc-preview]');
        var blockFormatSelect = document.querySelector('[data-toc-block-format]');
        var textColorInput = document.querySelector('[data-toc-text-color]');
        var dividerButton = document.querySelector('[data-toc-divider]');
        var saveButton = document.querySelector('[data-toc-save]');
        var resetButton = document.querySelector('[data-toc-reset]');
        var feedback = document.querySelector('[data-toc-feedback]');
        var updatedAtLabel = document.querySelector('[data-toc-updated-at]');

        if (!editor || !preview || !saveButton || !resetButton) {
            return;
        }

        var savedContentHtml = String(config.initialContentHtml || '').trim();

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

        function hasNonWhitespaceText(value) {
            return String(value || '').replace(/\u00a0/g, ' ').trim() !== '';
        }

        function isSeparatorText(value) {
            return /^-{3,}$/.test(String(value || '').replace(/\s+/g, ''));
        }

        function isSeparatorNode(node) {
            if (!node) {
                return false;
            }

            if (node.nodeType === Node.TEXT_NODE) {
                return isSeparatorText(node.nodeValue || '');
            }

            if (node.nodeType !== Node.ELEMENT_NODE) {
                return false;
            }

            var tagName = String(node.tagName || '').toLowerCase();
            if (tagName === 'hr') {
                return true;
            }

            if (['p', 'div', 'section', 'article'].indexOf(tagName) === -1) {
                return false;
            }

            if (node.children.length > 0) {
                return false;
            }

            return isSeparatorText(node.textContent || '');
        }

        function addClassName(element, className) {
            if (!element) {
                return;
            }

            if (element.classList.contains(className)) {
                return;
            }

            element.classList.add(className);
        }

        function buildPreviewLayout(sourceHtml) {
            var parser = new DOMParser();
            var parsedDocument = parser.parseFromString('<div data-toc-preview-root="1">' + String(sourceHtml || '') + '</div>', 'text/html');
            var sourceRoot = parsedDocument.querySelector('[data-toc-preview-root="1"]');

            if (!sourceRoot) {
                return '';
            }

            var outputDocument = document.implementation.createHTMLDocument('toc-preview');
            var layout = outputDocument.createElement('div');
            layout.className = 'cart-terms-editor-layout';

            var currentContainer = null;
            var hasContent = false;
            var sourceNodes = Array.prototype.slice.call(sourceRoot.childNodes || []);

            sourceNodes.forEach(function (sourceNode) {
                if (sourceNode.nodeType === Node.TEXT_NODE && !hasNonWhitespaceText(sourceNode.nodeValue || '')) {
                    return;
                }

                if (isSeparatorNode(sourceNode)) {
                    currentContainer = null;
                    return;
                }

                if (!currentContainer) {
                    currentContainer = outputDocument.createElement('section');
                    currentContainer.className = 'cart-terms-editor-container';
                    layout.appendChild(currentContainer);
                }

                currentContainer.appendChild(outputDocument.importNode(sourceNode, true));
                hasContent = true;
            });

            if (!hasContent) {
                var fallbackContainer = outputDocument.createElement('section');
                fallbackContainer.className = 'cart-terms-editor-container';
                var fallbackParagraph = outputDocument.createElement('p');
                fallbackParagraph.textContent = 'Terms and conditions are currently unavailable.';
                fallbackContainer.appendChild(fallbackParagraph);
                layout.appendChild(fallbackContainer);
            }

            Array.prototype.slice.call(layout.querySelectorAll('ul, ol')).forEach(function (listElement) {
                addClassName(listElement, 'cart-terms-editor-mini-list');

                Array.prototype.slice.call(listElement.children).forEach(function (childNode) {
                    if (String(childNode.tagName || '').toLowerCase() === 'li') {
                        addClassName(childNode, 'cart-terms-editor-mini-item');
                    }
                });
            });

            return layout.innerHTML;
        }

        function refreshPreview() {
            preview.innerHTML = buildPreviewLayout(editor.innerHTML);
        }

        function runCommand(command, value) {
            editor.focus();

            try {
                document.execCommand('styleWithCSS', false, true);
            } catch (error) {
                // Continue even when the command is unsupported.
            }

            document.execCommand(command, false, value || null);
            refreshPreview();
        }

        var commandButtons = document.querySelectorAll('[data-toc-command]');
        commandButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var command = String(button.getAttribute('data-toc-command') || '').trim();

                if (!command) {
                    return;
                }

                runCommand(command, null);
            });
        });

        if (blockFormatSelect) {
            blockFormatSelect.addEventListener('change', function () {
                var selectedTag = String(blockFormatSelect.value || '').trim();

                if (!selectedTag) {
                    runCommand('formatBlock', 'P');
                    return;
                }

                runCommand('formatBlock', selectedTag);
            });
        }

        if (textColorInput) {
            textColorInput.addEventListener('input', function () {
                var colorValue = String(textColorInput.value || '').trim();

                if (!colorValue) {
                    return;
                }

                runCommand('foreColor', colorValue);
            });
        }

        if (dividerButton) {
            dividerButton.addEventListener('click', function () {
                runCommand('insertHorizontalRule', null);
            });
        }

        editor.addEventListener('input', refreshPreview);

        resetButton.addEventListener('click', function () {
            editor.innerHTML = savedContentHtml;
            refreshPreview();
            setFeedback('Editor reset to last saved content.', 'success');
        });

        saveButton.addEventListener('click', function () {
            var updateEndpoint = String(config.updateEndpoint || '').trim();

            if (!updateEndpoint) {
                setFeedback('TOC update endpoint is unavailable.', 'error');
                return;
            }

            var payloadHtml = String(editor.innerHTML || '').trim();
            var payloadText = String(editor.textContent || '').replace(/\s+/g, ' ').trim();

            if (!payloadHtml || !payloadText) {
                setFeedback('TOC content cannot be empty.', 'error');
                editor.focus();
                return;
            }

            saveButton.disabled = true;
            setFeedback('Saving TOC changes...', '');

            fetch(updateEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                },
                body: JSON.stringify({
                    contentHtml: payloadHtml
                })
            })
                .then(function (response) {
                    return response.text().then(function (rawBody) {
                        var parsedBody = {};

                        try {
                            parsedBody = JSON.parse(rawBody);
                        } catch (error) {
                            parsedBody = {};
                        }

                        if (!response.ok || !parsedBody || parsedBody.ok !== true) {
                            var errorMessage = parsedBody && parsedBody.message
                                ? String(parsedBody.message)
                                : 'Unable to save TOC changes.';
                            throw new Error(errorMessage);
                        }

                        return parsedBody;
                    });
                })
                .then(function (payload) {
                    savedContentHtml = String(payload.contentHtml || '');
                    editor.innerHTML = savedContentHtml;

                    if (typeof payload.displayHtml === 'string' && payload.displayHtml.trim() !== '') {
                        preview.innerHTML = payload.displayHtml;
                    } else {
                        refreshPreview();
                    }

                    if (updatedAtLabel) {
                        updatedAtLabel.textContent = String(payload.updatedAtLabel || 'Saved');
                    }

                    setFeedback('TOC updated successfully.', 'success');
                })
                .catch(function (error) {
                    setFeedback(error && error.message ? String(error.message) : 'Unable to save TOC changes.', 'error');
                })
                .finally(function () {
                    saveButton.disabled = false;
                });
        });

        if (typeof config.initialDisplayHtml === 'string' && config.initialDisplayHtml.trim() !== '') {
            preview.innerHTML = config.initialDisplayHtml;
        } else {
            refreshPreview();
        }

        if (updatedAtLabel && typeof config.updatedAtLabel === 'string' && config.updatedAtLabel.trim() !== '') {
            updatedAtLabel.textContent = config.updatedAtLabel;
        }
    })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
