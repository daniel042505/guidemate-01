<?php
/**
 * Shared helpers for parsing and formatting review records.
 */

function gm_parse_review_comment($rawComment) {
    $rawComment = (string)$rawComment;
    $locationName = '';
    $displayComment = $rawComment;
    $reviewType = 'location';

    if (preg_match('/^Type:\s*(location|guide)\RLocation:\s*(.+?)\RReview:\s*(.*)$/si', $rawComment, $matches)) {
        $reviewType = strtolower(trim($matches[1]));
        $locationName = trim($matches[2]);
        $displayComment = trim($matches[3]);
    } elseif (preg_match('/^Location:\s*(.+?)\RReview:\s*(.*)$/s', $rawComment, $matches)) {
        $locationName = trim($matches[1]);
        $displayComment = trim($matches[2]);
    }

    return [
        'review_type' => $reviewType,
        'location_name' => $locationName,
        'comment' => $displayComment,
        'raw_comment' => $rawComment,
    ];
}

function gm_review_subject($guideName, $locationName) {
    $guideName = trim((string)$guideName);
    $locationName = trim((string)$locationName);
    if ($guideName === '') {
        return $locationName !== '' ? $locationName : '—';
    }
    if ($locationName === '') {
        return $guideName;
    }
    return $guideName . ' @ ' . $locationName;
}

function gm_ensure_review_replies_table(mysqli $mysqli) {
    static $ensured = false;

    if ($ensured) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS review_replies (
        reply_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        review_id INT UNSIGNED NOT NULL,
        guide_id INT UNSIGNED NOT NULL,
        reply_text TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_review_guide_reply (review_id, guide_id),
        KEY idx_review_replies_review (review_id),
        KEY idx_review_replies_guide (guide_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $ensured = $mysqli->query($sql) === true;
    return $ensured;
}
