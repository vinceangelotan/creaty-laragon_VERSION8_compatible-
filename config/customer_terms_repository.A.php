<?php

function customer_terms_repository_path()
{
    return __DIR__ . '/customer_terms.json';
}

function default_customer_terms_content_html()
{
    return <<<'HTML'
<h3>Key Rental Rules</h3>
<ul>
    <li><strong>Rental Window:</strong> 22 hours</li>
    <li><strong>Grace Period:</strong> 2 hours (return process must be initiated within this period)</li>
    <li><strong>Late Fee:</strong> <span style="color: #ffb369;">&#8369;50 per hour (or part of an hour)</span></li>
</ul>

<hr>

<h3>1. Acceptance of Terms</h3>
<p>By creating a reservation through the CREATY system, you ("Customer") enter into a legally binding contract with <strong>Nifty Fifty Camera Rentals</strong> and agree to all terms listed below.</p>

<hr>

<h3>2. Definitions</h3>
<ul>
    <li><strong>Equipment:</strong> Camera gear listed for rental.</li>
    <li><strong>Reservation:</strong> A booking request that becomes valid after confirmation by Nifty Fifty staff.</li>
    <li><strong>Rental Period:</strong> 22 hours of usage time starting when equipment is received.</li>
    <li><strong>Grace Period:</strong> A 2-hour window to initiate the return process.</li>
    <li><strong>Late Period:</strong> Time after the Grace Period until equipment is returned.</li>
</ul>

<hr>

<h3>3. Reservation and Equipment Assignment</h3>
<ul>
    <li>Reservations are requests until confirmed by Nifty Fifty staff via the system.</li>
    <li><strong>Equipment assignment is automated by CREATY</strong> based on availability, event suitability, and fair usage rotation.</li>
    <li>Cancellations must be made through official channels.</li>
</ul>

<hr>

<h3>4. Claiming Equipment</h3>
<p>You must choose one claiming method:</p>
<ul>
    <li><strong>Pick-up:</strong> Collect at Nifty Fifty's location during business hours with a valid ID.</li>
    <li><strong>Meet-up:</strong> Time and location require prior confirmation. Being late may forfeit the reservation.</li>
    <li><strong>Delivery:</strong> Customer arranges delivery directly and must submit valid identity verification requirements when requested.</li>
</ul>

<hr>

<h3>5. Returning Equipment, Grace Period, and Penalties</h3>
<p>You must choose one return method:</p>
<ul>
    <li><strong>Return to Store:</strong> Return within the agreed return window.</li>
    <li><strong>Meet-up Return:</strong> Time and location must be pre-arranged with staff.</li>
    <li><strong>Delivery Return:</strong> Customer arranges courier directly. Return shipment must be initiated within the 2-hour Grace Period.</li>
</ul>
<p><strong>Late Return Rules</strong></p>
<ul>
    <li>The 2-hour Grace Period is for initiating return, not extended usage.</li>
    <li>Returns completed after the Grace Period incur <span style="color: #ffb369; font-weight: 700;">&#8369;50 per hour</span> in penalties.</li>
    <li>Failure to return equipment within 24 hours after the Grace Period may be treated as theft or conversion, with legal action pursued.</li>
</ul>

<hr>

<h3>6. Care, Liability, and Fees</h3>
<ul>
    <li>You are responsible for the equipment from receipt until verified return.</li>
    <li>You are fully liable for all damage, loss, or theft and corresponding repair or replacement costs.</li>
    <li>All rental fees and late penalties are the Customer's responsibility.</li>
    <li>Nifty Fifty is not liable for indirect damages (for example, missed shoots or data loss).</li>
</ul>

<hr>

<h3>7. General Provisions</h3>
<ul>
    <li><strong>Account Integrity:</strong> You must provide accurate information. Misuse of CREATY may result in account suspension.</li>
    <li><strong>Privacy:</strong> ID photos are collected only for verification and handled according to our Privacy Policy.</li>
    <li><strong>Limitation of Liability:</strong> Nifty Fifty's maximum liability is limited to the total rental or package amount paid for the reservation.</li>
    <li><strong>Changes to Terms:</strong> We may update these Terms. Continued use of CREATY means acceptance of updated Terms.</li>
</ul>
HTML;
}

function default_customer_terms_repository_record()
{
    return [
        'contentHtml' => default_customer_terms_content_html(),
        'updatedAt' => gmdate('c')
    ];
}

function customer_terms_dom_supported()
{
    return class_exists('DOMDocument') && class_exists('DOMXPath');
}

function customer_terms_strip_utf8_bom($value)
{
    return preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
}

function customer_terms_normalize_special_symbols($value)
{
    $normalized = (string) $value;

    if ($normalized === '') {
        return '';
    }

    // Keep peso rendering stable across environments by normalizing to HTML entity.
    $normalized = str_replace(
        [
            "\xE2\x82\xB1", // UTF-8 peso sign
            '&amp;#8369;'
        ],
        [
            '&#8369;',
            '&#8369;'
        ],
        $normalized
    );

    $normalized = preg_replace('/&#(?:x0*20b1|0*8369);?/i', '&#8369;', $normalized) ?? $normalized;

    $mojibakePesoTokens = [
        "\xC3\xA2\xE2\x80\x9A\xC2\xB1", // â‚±
        "\xC3\x83\xC2\xA2\xC3\x82\xC2\x82\xC3\x82\xC2\xB1", // Ã¢ÂÂ±
        "\xC3\x83\xC2\xA2\xE2\x80\x9A\xC2\xAC\xC3\x82\xC2\xB1" // Ã¢â‚¬Â±
    ];

    foreach ($mojibakePesoTokens as $token) {
        $normalized = str_replace($token, '&#8369;', $normalized);
    }

    return $normalized;
}

function customer_terms_is_separator_text($text)
{
    $normalized = preg_replace('/\s+/', '', trim((string) $text));

    if ($normalized === null) {
        return false;
    }

    if ($normalized === '') {
        return false;
    }

    return preg_match('/^-{3,}$/', $normalized) === 1;
}

function sanitize_customer_terms_color_value($value)
{
    $candidate = strtolower(trim((string) $value));

    if ($candidate === '') {
        return '';
    }

    if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/', $candidate) === 1) {
        return $candidate;
    }

    if (preg_match('/^rgba?\(\s*(?:\d{1,3}%?\s*,\s*){2}\d{1,3}%?(\s*,\s*(?:0|0?\.\d+|1(?:\.0+)?))?\s*\)$/', $candidate) === 1) {
        return $candidate;
    }

    if (preg_match('/^[a-z]{3,20}$/', $candidate) === 1) {
        return $candidate;
    }

    return '';
}

function sanitize_customer_terms_style_attribute($style)
{
    $raw = trim((string) $style);

    if ($raw === '') {
        return '';
    }

    $allowedProperties = [
        'color',
        'background-color',
        'text-align',
        'font-weight',
        'font-style',
        'text-decoration'
    ];

    $safePairs = [];
    $segments = explode(';', $raw);

    foreach ($segments as $segment) {
        $pair = explode(':', $segment, 2);

        if (count($pair) !== 2) {
            continue;
        }

        $property = strtolower(trim((string) $pair[0]));
        $value = trim((string) $pair[1]);

        if ($property === '' || $value === '' || !in_array($property, $allowedProperties, true)) {
            continue;
        }

        $safeValue = '';

        if ($property === 'color' || $property === 'background-color') {
            $safeValue = sanitize_customer_terms_color_value($value);
        } elseif ($property === 'text-align') {
            $candidate = strtolower($value);
            if (in_array($candidate, ['left', 'right', 'center', 'justify'], true)) {
                $safeValue = $candidate;
            }
        } elseif ($property === 'font-weight') {
            $candidate = strtolower($value);
            if (preg_match('/^(normal|bold|bolder|lighter|[1-9]00)$/', $candidate) === 1) {
                $safeValue = $candidate;
            }
        } elseif ($property === 'font-style') {
            $candidate = strtolower($value);
            if (in_array($candidate, ['normal', 'italic', 'oblique'], true)) {
                $safeValue = $candidate;
            }
        } elseif ($property === 'text-decoration') {
            $candidate = strtolower($value);
            if (in_array($candidate, ['none', 'underline', 'line-through'], true)) {
                $safeValue = $candidate;
            }
        }

        if ($safeValue !== '') {
            $safePairs[$property] = $safeValue;
        }
    }

    if (!$safePairs) {
        return '';
    }

    $styleParts = [];

    foreach ($safePairs as $property => $value) {
        $styleParts[] = $property . ': ' . $value;
    }

    return implode('; ', $styleParts);
}

function sanitize_customer_terms_link_href($href)
{
    $candidate = trim((string) $href);

    if ($candidate === '') {
        return '';
    }

    if ($candidate[0] === '#') {
        return $candidate;
    }

    if (strpos($candidate, '/') === 0 || strpos($candidate, './') === 0 || strpos($candidate, '../') === 0) {
        return $candidate;
    }

    $scheme = strtolower((string) parse_url($candidate, PHP_URL_SCHEME));

    if (in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
        return $candidate;
    }

    return '';
}

function customer_terms_has_element_children($node)
{
    if (!($node instanceof DOMNode)) {
        return false;
    }

    foreach ($node->childNodes as $childNode) {
        if ($childNode instanceof DOMElement) {
            return true;
        }
    }

    return false;
}

function customer_terms_append_sanitized_children($sourceNode, $targetNode, $targetDocument)
{
    if (!($sourceNode instanceof DOMNode) || !($targetNode instanceof DOMNode) || !($targetDocument instanceof DOMDocument)) {
        return;
    }

    foreach ($sourceNode->childNodes as $sourceChild) {
        $sanitizedChild = sanitize_customer_terms_dom_node($sourceChild, $targetDocument);

        if ($sanitizedChild instanceof DOMNode) {
            $targetNode->appendChild($sanitizedChild);
        }
    }
}

function sanitize_customer_terms_dom_node($sourceNode, $targetDocument)
{
    if (!($sourceNode instanceof DOMNode) || !($targetDocument instanceof DOMDocument)) {
        return null;
    }

    if ($sourceNode instanceof DOMText) {
        return $targetDocument->createTextNode($sourceNode->nodeValue);
    }

    if ($sourceNode instanceof DOMComment) {
        return null;
    }

    if (!($sourceNode instanceof DOMElement)) {
        $fragment = $targetDocument->createDocumentFragment();
        customer_terms_append_sanitized_children($sourceNode, $fragment, $targetDocument);

        return $fragment->hasChildNodes() ? $fragment : null;
    }

    $tagName = strtolower((string) $sourceNode->tagName);

    if (in_array($tagName, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
        return null;
    }

    if ($tagName === 'font') {
        $replacementNode = $targetDocument->createElement('span');

        $colorStyle = sanitize_customer_terms_color_value($sourceNode->getAttribute('color'));
        $inlineStyle = sanitize_customer_terms_style_attribute($sourceNode->getAttribute('style'));

        $styleFragments = [];
        if ($colorStyle !== '') {
            $styleFragments[] = 'color: ' . $colorStyle;
        }
        if ($inlineStyle !== '') {
            $styleFragments[] = $inlineStyle;
        }

        if ($styleFragments) {
            $replacementNode->setAttribute('style', implode('; ', $styleFragments));
        }

        customer_terms_append_sanitized_children($sourceNode, $replacementNode, $targetDocument);

        return $replacementNode;
    }

    $allowedTags = [
        'a',
        'article',
        'br',
        'div',
        'em',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'i',
        'li',
        'ol',
        'p',
        'section',
        'span',
        'strong',
        'b',
        'u',
        'ul'
    ];

    if (!in_array($tagName, $allowedTags, true)) {
        $fragment = $targetDocument->createDocumentFragment();
        customer_terms_append_sanitized_children($sourceNode, $fragment, $targetDocument);

        return $fragment->hasChildNodes() ? $fragment : null;
    }

    if (in_array($tagName, ['p', 'div', 'section', 'article'], true)) {
        if (!customer_terms_has_element_children($sourceNode) && customer_terms_is_separator_text($sourceNode->textContent)) {
            return $targetDocument->createElement('hr');
        }
    }

    $targetElement = $targetDocument->createElement($tagName);

    $sanitizedStyle = sanitize_customer_terms_style_attribute($sourceNode->getAttribute('style'));
    if ($sanitizedStyle !== '') {
        $targetElement->setAttribute('style', $sanitizedStyle);
    }

    if ($tagName === 'a') {
        $sanitizedHref = sanitize_customer_terms_link_href($sourceNode->getAttribute('href'));

        if ($sanitizedHref !== '') {
            $targetElement->setAttribute('href', $sanitizedHref);
        }

        $targetValue = strtolower(trim((string) $sourceNode->getAttribute('target')));
        if ($targetValue === '_blank') {
            $targetElement->setAttribute('target', '_blank');
            $targetElement->setAttribute('rel', 'noopener noreferrer');
        }
    }

    customer_terms_append_sanitized_children($sourceNode, $targetElement, $targetDocument);

    return $targetElement;
}

function customer_terms_extract_inner_html($node)
{
    if (!($node instanceof DOMNode)) {
        return '';
    }

    $ownerDocument = $node->ownerDocument;

    if (!($ownerDocument instanceof DOMDocument)) {
        return '';
    }

    $html = '';

    foreach ($node->childNodes as $childNode) {
        $html .= $ownerDocument->saveHTML($childNode);
    }

    return $html;
}

function customer_terms_sanitize_content_html($contentHtml)
{
    $rawHtml = customer_terms_normalize_special_symbols(trim((string) $contentHtml));

    if ($rawHtml === '') {
        return default_customer_terms_content_html();
    }

    if (!customer_terms_dom_supported()) {
        $fallback = strip_tags($rawHtml, '<a><article><br><div><em><h2><h3><h4><h5><h6><hr><i><li><ol><p><section><span><strong><b><u><ul>');
        $fallback = customer_terms_normalize_special_symbols($fallback);

        return trim($fallback) !== '' ? $fallback : default_customer_terms_content_html();
    }

    $sourceDocument = new DOMDocument('1.0', 'UTF-8');
    $internalErrors = libxml_use_internal_errors(true);

    $loadResult = $sourceDocument->loadHTML(
        '<!DOCTYPE html><html><body><div data-customer-terms-root="1">' . $rawHtml . '</div></body></html>',
        LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
    );

    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);

    if (!$loadResult) {
        return default_customer_terms_content_html();
    }

    $sourceXPath = new DOMXPath($sourceDocument);
    $rootNodes = $sourceXPath->query('//div[@data-customer-terms-root="1"]');
    $sourceRoot = ($rootNodes instanceof DOMNodeList && $rootNodes->length > 0)
        ? $rootNodes->item(0)
        : null;

    if (!($sourceRoot instanceof DOMElement)) {
        return default_customer_terms_content_html();
    }

    $targetDocument = new DOMDocument('1.0', 'UTF-8');
    $targetRoot = $targetDocument->createElement('div');
    $targetDocument->appendChild($targetRoot);

    foreach ($sourceRoot->childNodes as $sourceChild) {
        $sanitizedNode = sanitize_customer_terms_dom_node($sourceChild, $targetDocument);

        if ($sanitizedNode instanceof DOMNode) {
            $targetRoot->appendChild($sanitizedNode);
        }
    }

    $sanitizedHtml = customer_terms_normalize_special_symbols(trim(customer_terms_extract_inner_html($targetRoot)));

    if ($sanitizedHtml === '') {
        return default_customer_terms_content_html();
    }

    return $sanitizedHtml;
}

function normalize_customer_terms_repository_record($record)
{
    $defaults = default_customer_terms_repository_record();

    if (!is_array($record)) {
        $record = [];
    }

    $contentHtml = customer_terms_sanitize_content_html(
        $record['contentHtml'] ?? $record['content_html'] ?? $defaults['contentHtml']
    );

    $updatedAt = trim((string) ($record['updatedAt'] ?? $record['updated_at'] ?? ''));

    if ($updatedAt === '') {
        $updatedAt = gmdate('c');
    }

    return [
        'contentHtml' => $contentHtml,
        'updatedAt' => $updatedAt
    ];
}

function load_customer_terms_repository()
{
    $path = customer_terms_repository_path();

    if (!is_file($path)) {
        $defaultRecord = default_customer_terms_repository_record();
        save_customer_terms_repository($defaultRecord);

        return normalize_customer_terms_repository_record($defaultRecord);
    }

    $raw = file_get_contents($path);

    if ($raw === false) {
        return default_customer_terms_repository_record();
    }

    $decoded = json_decode(customer_terms_strip_utf8_bom($raw), true);

    if (!is_array($decoded)) {
        return default_customer_terms_repository_record();
    }

    return normalize_customer_terms_repository_record($decoded);
}

function save_customer_terms_repository($record)
{
    $path = customer_terms_repository_path();
    $normalized = normalize_customer_terms_repository_record($record);
    $normalized['updatedAt'] = gmdate('c');

    $encoded = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if ($encoded === false) {
        return false;
    }

    return file_put_contents($path, $encoded . PHP_EOL, LOCK_EX) !== false;
}

function customer_terms_append_class_name($element, $className)
{
    if (!($element instanceof DOMElement)) {
        return;
    }

    $existingClassNames = trim((string) $element->getAttribute('class'));
    $tokens = preg_split('/\s+/', $existingClassNames) ?: [];

    $normalized = [];
    foreach ($tokens as $token) {
        $safeToken = trim((string) $token);

        if ($safeToken === '') {
            continue;
        }

        $normalized[$safeToken] = true;
    }

    $normalized[$className] = true;
    $element->setAttribute('class', implode(' ', array_keys($normalized)));
}

function customer_terms_node_is_separator($node)
{
    if ($node instanceof DOMText) {
        return customer_terms_is_separator_text($node->nodeValue);
    }

    if (!($node instanceof DOMElement)) {
        return false;
    }

    $tagName = strtolower((string) $node->tagName);

    if ($tagName === 'hr') {
        return true;
    }

    if (!in_array($tagName, ['p', 'div', 'section', 'article'], true)) {
        return false;
    }

    if (customer_terms_has_element_children($node)) {
        return false;
    }

    return customer_terms_is_separator_text($node->textContent);
}

function customer_terms_prepare_display_html($contentHtml)
{
    $sanitizedHtml = customer_terms_sanitize_content_html($contentHtml);

    if (!customer_terms_dom_supported()) {
        return $sanitizedHtml;
    }

    $sourceDocument = new DOMDocument('1.0', 'UTF-8');
    $internalErrors = libxml_use_internal_errors(true);

    $loadResult = $sourceDocument->loadHTML(
        '<!DOCTYPE html><html><body><div data-customer-terms-display-source="1">' . $sanitizedHtml . '</div></body></html>',
        LIBXML_HTML_NODEFDTD | LIBXML_HTML_NOIMPLIED
    );

    libxml_clear_errors();
    libxml_use_internal_errors($internalErrors);

    if (!$loadResult) {
        return $sanitizedHtml;
    }

    $sourceXPath = new DOMXPath($sourceDocument);
    $sourceRootNodes = $sourceXPath->query('//div[@data-customer-terms-display-source="1"]');
    $sourceRoot = ($sourceRootNodes instanceof DOMNodeList && $sourceRootNodes->length > 0)
        ? $sourceRootNodes->item(0)
        : null;

    if (!($sourceRoot instanceof DOMElement)) {
        return $sanitizedHtml;
    }

    $renderDocument = new DOMDocument('1.0', 'UTF-8');
    $layoutRoot = $renderDocument->createElement('div');
    $layoutRoot->setAttribute('class', 'cart-terms-editor-layout');
    $renderDocument->appendChild($layoutRoot);

    $currentContainer = null;
    $hasRenderableContent = false;

    foreach ($sourceRoot->childNodes as $sourceChild) {
        if ($sourceChild instanceof DOMText && trim((string) $sourceChild->nodeValue) === '') {
            continue;
        }

        if (customer_terms_node_is_separator($sourceChild)) {
            $currentContainer = null;
            continue;
        }

        if (!($currentContainer instanceof DOMElement)) {
            $currentContainer = $renderDocument->createElement('section');
            $currentContainer->setAttribute('class', 'cart-terms-editor-container');
            $layoutRoot->appendChild($currentContainer);
        }

        $currentContainer->appendChild($renderDocument->importNode($sourceChild, true));
        $hasRenderableContent = true;
    }

    if (!$hasRenderableContent) {
        $fallbackContainer = $renderDocument->createElement('section');
        $fallbackContainer->setAttribute('class', 'cart-terms-editor-container');

        $fallbackParagraph = $renderDocument->createElement('p', 'Terms and conditions are currently unavailable.');
        $fallbackContainer->appendChild($fallbackParagraph);
        $layoutRoot->appendChild($fallbackContainer);
    }

    $renderXPath = new DOMXPath($renderDocument);
    $miniLists = $renderXPath->query('//ul | //ol');

    if ($miniLists instanceof DOMNodeList) {
        foreach ($miniLists as $miniList) {
            if (!($miniList instanceof DOMElement)) {
                continue;
            }

            customer_terms_append_class_name($miniList, 'cart-terms-editor-mini-list');

            foreach ($miniList->childNodes as $listChild) {
                if ($listChild instanceof DOMElement && strtolower((string) $listChild->tagName) === 'li') {
                    customer_terms_append_class_name($listChild, 'cart-terms-editor-mini-item');
                }
            }
        }
    }

    return customer_terms_normalize_special_symbols(customer_terms_extract_inner_html($layoutRoot));
}
