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
<p>By creating a reservation through the CREATY system, you ("Customer") enter into a legally binding agreement with <strong>Nifty Fifty Camera Rentals</strong> and agree to all terms and conditions listed below.</p>

<hr>

<h3>2. Definitions</h3>
<ul>
    <li><strong>Equipment:</strong> Camera gear and related items listed for rental.</li>
    <li><strong>Reservation:</strong> A booking request submitted through CREATY that becomes valid only after confirmation by Nifty Fifty staff.</li>
    <li><strong>Rental Period:</strong> The 22-hour usage period starting from the time the equipment is received by the Customer.</li>
    <li><strong>Grace Period:</strong> A 2-hour period provided for the Customer to initiate the return process after the rental period ends.</li>
    <li><strong>Late Period:</strong> The period after the Grace Period until the equipment is successfully returned and verified.</li>
</ul>

<hr>

<h3>3. Reservation and Equipment Assignment</h3>
<ul>
    <li>Reservations submitted through CREATY are considered requests until confirmed by Nifty Fifty staff.</li>
    <li><strong>Equipment assignment is automatically managed by CREATY</strong> based on equipment availability, event requirements, and fair usage rotation.</li>
    <li>Customers must provide accurate reservation details, including event information and contact details.</li>
    <li>Reservation cancellations must be made through official communication channels.</li>
    <li>Nifty Fifty reserves the right to modify or reject reservations due to equipment availability, inaccurate information, or policy violations.</li>
</ul>

<hr>

<h3>4. Claiming Equipment</h3>
<p>Customers must select one claiming method:</p>
<p><strong>Pick-up</strong></p>
<ul>
    <li>Customer must collect the equipment at Nifty Fifty's designated location during business hours.</li>
    <li>Customer must present a valid identification document when claiming equipment.</li>
    <li>Equipment condition will be checked before release.</li>
</ul>
<p><strong>Meet-up</strong></p>
<ul>
    <li>Meet-up schedule, location, and time must be confirmed by Nifty Fifty staff.</li>
    <li>Customer must arrive on time.</li>
    <li>Failure to appear or excessive delays may result in cancellation of the reservation.</li>
</ul>
<p><strong>Delivery</strong></p>
<ul>
    <li>Customer may request delivery of equipment through available delivery options.</li>
    <li>Customer is responsible for arranging and coordinating the delivery service.</li>
    <li>All delivery, courier, shipping, and transportation fees shall be fully shouldered by the Customer as an additional fee separate from the rental fee.</li>
    <li>Delivery charges are not included in the equipment rental price unless specifically stated.</li>
    <li>Nifty Fifty is not responsible for delays, damages, losses, or issues caused by third-party courier or delivery services.</li>
    <li>Customer must provide valid identity verification requirements when requested before equipment release.</li>
</ul>

<hr>

<h3>5. Returning Equipment, Grace Period, and Penalties</h3>
<p>Customers must select one return method:</p>
<p><strong>Return to Store</strong></p>
<ul>
    <li>Customer must return equipment to Nifty Fifty's designated location within the agreed return schedule.</li>
</ul>
<p><strong>Meet-up Return</strong></p>
<ul>
    <li>Return schedule, location, and time must be arranged and confirmed with Nifty Fifty staff.</li>
    <li>Customer must ensure the equipment is available for inspection upon return.</li>
</ul>
<p><strong>Delivery Return</strong></p>
<ul>
    <li>Customer may arrange a courier or delivery service to return the equipment.</li>
    <li>All return delivery, courier, shipping, and transportation fees shall be fully shouldered by the Customer as an additional fee separate from the rental fee.</li>
    <li>Customer is responsible for ensuring that the equipment is properly packed and safely transported.</li>
    <li>Nifty Fifty is not responsible for delays, losses, or damages caused during third-party transportation.</li>
</ul>
<p><strong>Late Return Rules</strong></p>
<ul>
    <li>The 2-hour Grace Period is provided only for initiating the return process and is not an extension of equipment usage.</li>
    <li>Returns completed after the Grace Period will incur a <span style="color: #ffb369; font-weight: 700;">&#8369;50 per hour</span> late fee (or part of an hour).</li>
    <li>Failure to return equipment within 24 hours after the Grace Period may be considered unauthorized retention, and Nifty Fifty may pursue appropriate legal action.</li>
</ul>

<hr>

<h3>6. Care, Liability, and Fees</h3>
<ul>
    <li>Customer is responsible for the equipment from the time of receipt until the equipment has been inspected and officially accepted by Nifty Fifty.</li>
    <li>Customer shall be liable for any damage, loss, missing accessories, or theft involving rented equipment.</li>
    <li>Repair, replacement, and recovery costs shall be charged to the responsible Customer.</li>
    <li>All rental fees, delivery fees, late penalties, damages, and other applicable charges remain the Customer's responsibility.</li>
    <li>Nifty Fifty is not responsible for indirect damages, including missed events, missed opportunities, lost files, or business losses.</li>
</ul>

<hr>

<h3>7. Refund Policy</h3>
<ul>
    <li>Customers may request cancellation of a reservation through official channels.</li>
    <li>Refund requests will be reviewed and approved based on the reservation status and applicable circumstances.</li>
    <li>Approved cancellations made before equipment release may be eligible for a refund.</li>
    <li>Once equipment has been claimed or the rental period has started, rental fees are generally non-refundable.</li>
    <li>Delivery fees, courier charges, and other third-party service fees are non-refundable once processed.</li>
    <li>Refunds will not be issued for cancellations caused by Customer negligence, failure to comply with requirements, late arrival, misuse of equipment, or violation of these Terms and Conditions.</li>
    <li>Any outstanding charges, including late fees, damages, missing equipment costs, or other penalties, may be deducted from the refundable amount.</li>
    <li>Approved refunds will be processed within 7&#8211;14 business days, depending on the payment provider or financial institution.</li>
    <li>Nifty Fifty reserves the right to deny refund requests involving fraud, misuse of CREATY, false information, or violations of rental policies.</li>
</ul>

<hr>

<h3>8. Account Integrity, Privacy, and General Provisions</h3>
<ul>
    <li>Customers must provide accurate and complete information when creating an account and making reservations.</li>
    <li>Misuse of CREATY, including fraudulent reservations or false information, may result in account suspension or termination.</li>
    <li>Customer identification documents are collected only for verification purposes and handled according to the Privacy Policy.</li>
    <li>Nifty Fifty's maximum liability is limited to the total rental or package amount paid for the reservation.</li>
    <li>Nifty Fifty reserves the right to update these Terms and Conditions at any time.</li>
    <li>Continued use of CREATY after updates means acceptance of the revised Terms and Conditions.</li>
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
