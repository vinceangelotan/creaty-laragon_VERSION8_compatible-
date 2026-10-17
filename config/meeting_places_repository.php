<?php
/**
 * Meeting / pick-up places shown in the customer's "Meeting Place" dropdown.
 * Stored as a simple JSON list in config/meeting_places.json and managed by the admin
 * on the "Manage Meeting Places" page. One list is shared by pick-up and meet-up.
 */

function meeting_places_repository_path()
{
    return __DIR__ . '/meeting_places.json';
}

function meeting_places_default_list()
{
    return [
        'Walter Mart Entrance, Carmona',
        'Cabilang Baybay (Arko), Carmona',
    ];
}

function meeting_place_max_length()
{
    return 120;
}

function meeting_place_text_length($value)
{
    return function_exists('mb_strlen') ? mb_strlen((string) $value, 'UTF-8') : strlen((string) $value);
}

function sanitize_meeting_place_label($value)
{
    $label = (string) $value;
    $label = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $label) ?? $label;
    $label = preg_replace('/\s+/u', ' ', $label) ?? $label;
    $label = trim($label);

    if (function_exists('mb_substr')) {
        $label = mb_substr($label, 0, meeting_place_max_length(), 'UTF-8');
    } else {
        $label = substr($label, 0, meeting_place_max_length());
    }

    return trim($label);
}

function meeting_place_lookup_key($label)
{
    $label = sanitize_meeting_place_label($label);

    return function_exists('mb_strtolower') ? mb_strtolower($label, 'UTF-8') : strtolower($label);
}

function normalize_meeting_places_collection($places)
{
    if (!is_array($places)) {
        return [];
    }

    $normalized = [];
    $seen = [];

    foreach ($places as $place) {
        if (is_array($place)) {
            $place = $place['name'] ?? '';
        }

        $label = sanitize_meeting_place_label($place);

        if ($label === '') {
            continue;
        }

        $key = meeting_place_lookup_key($label);

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $normalized[] = $label;
    }

    return $normalized;
}

function meeting_places_encode($places)
{
    return json_encode(
        array_values($places),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
}

function load_meeting_places_repository()
{
    $path = meeting_places_repository_path();

    if (!is_file($path)) {
        $defaults = meeting_places_default_list();
        $encoded = meeting_places_encode($defaults);

        if ($encoded !== false) {
            @file_put_contents($path, $encoded . PHP_EOL, LOCK_EX);
        }

        return $defaults;
    }

    $json = @file_get_contents($path);
    $decoded = is_string($json) ? json_decode($json, true) : null;

    if (is_array($decoded) && isset($decoded['places']) && is_array($decoded['places'])) {
        $decoded = $decoded['places'];
    }

    $places = normalize_meeting_places_collection($decoded);

    return $places ? $places : meeting_places_default_list();
}

function save_meeting_places_repository($places)
{
    $normalized = normalize_meeting_places_collection($places);

    if (!$normalized) {
        return false;
    }

    $encoded = meeting_places_encode($normalized);

    if ($encoded === false) {
        return false;
    }

    return @file_put_contents(meeting_places_repository_path(), $encoded . PHP_EOL, LOCK_EX) !== false;
}

/**
 * Returns the index of a place in the list (case-insensitive), or -1 when it is not found.
 */
function find_meeting_place_index($places, $label)
{
    $targetKey = meeting_place_lookup_key($label);

    foreach (array_values($places) as $index => $place) {
        if (meeting_place_lookup_key($place) === $targetKey) {
            return $index;
        }
    }

    return -1;
}
