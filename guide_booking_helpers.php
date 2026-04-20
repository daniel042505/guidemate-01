<?php

function guide_bookings_column_exists(mysqli $mysqli, $table, $column)
{
    $table = $mysqli->real_escape_string($table);
    $column = $mysqli->real_escape_string($column);
    $result = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $result && $result->num_rows > 0;
}

function ensure_guide_bookings_table(mysqli $mysqli)
{
    $sql = "CREATE TABLE IF NOT EXISTS guide_bookings (
        booking_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        tourist_user_id INT UNSIGNED NOT NULL,
        guide_id INT UNSIGNED NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        meet_time DATETIME NULL DEFAULT NULL,
        meeting_location VARCHAR(255) NULL DEFAULT NULL,
        tourist_message TEXT NULL,
        approved_at DATETIME NULL DEFAULT NULL,
        INDEX idx_guide_bookings_status (status),
        INDEX idx_guide_bookings_guide (guide_id),
        INDEX idx_guide_bookings_tourist (tourist_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$mysqli->query($sql)) {
        return false;
    }

    if (!guide_bookings_column_exists($mysqli, 'guide_bookings', 'approved_at')) {
        $mysqli->query("ALTER TABLE guide_bookings ADD COLUMN approved_at DATETIME NULL DEFAULT NULL AFTER created_at");
    }

    if (!guide_bookings_column_exists($mysqli, 'guide_bookings', 'meet_time')) {
        $mysqli->query("ALTER TABLE guide_bookings ADD COLUMN meet_time DATETIME NULL DEFAULT NULL AFTER created_at");
    }

    if (!guide_bookings_column_exists($mysqli, 'guide_bookings', 'meeting_location')) {
        $mysqli->query("ALTER TABLE guide_bookings ADD COLUMN meeting_location VARCHAR(255) NULL DEFAULT NULL AFTER meet_time");
    }

    if (!guide_bookings_column_exists($mysqli, 'guide_bookings', 'tourist_message')) {
        $mysqli->query("ALTER TABLE guide_bookings ADD COLUMN tourist_message TEXT NULL AFTER meeting_location");
    }

    return !$mysqli->error;
}

function ensure_booking_messages_table(mysqli $mysqli)
{
    $sql = "CREATE TABLE IF NOT EXISTS booking_messages (
        message_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        booking_id INT UNSIGNED NOT NULL,
        guide_id INT UNSIGNED NOT NULL,
        tourist_user_id INT UNSIGNED NOT NULL,
        sender_role VARCHAR(20) NOT NULL,
        sender_user_id INT UNSIGNED NOT NULL,
        message_text TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_messages_booking (booking_id),
        INDEX idx_booking_messages_sender (sender_user_id),
        INDEX idx_booking_messages_guide (guide_id),
        INDEX idx_booking_messages_tourist (tourist_user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$mysqli->query($sql)) {
        return false;
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'guide_id')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN guide_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER booking_id");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'tourist_user_id')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN tourist_user_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER guide_id");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'sender_role')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN sender_role VARCHAR(20) NOT NULL DEFAULT 'guide' AFTER tourist_user_id");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'sender_user_id')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN sender_user_id INT UNSIGNED NOT NULL DEFAULT 0 AFTER sender_role");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'message_text')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN message_text TEXT NOT NULL AFTER sender_user_id");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'created_at')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER message_text");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'meet_time')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN meet_time DATETIME NULL DEFAULT NULL AFTER created_at");
    }

    if (!guide_bookings_column_exists($mysqli, 'booking_messages', 'meeting_location')) {
        $mysqli->query("ALTER TABLE booking_messages ADD COLUMN meeting_location VARCHAR(255) NULL DEFAULT NULL AFTER meet_time");
    }

    return !$mysqli->error;
}

function ensure_admin_messages_table(mysqli $mysqli)
{
    $sql = "CREATE TABLE IF NOT EXISTS admin_messages (
        message_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        sender_role VARCHAR(20) NOT NULL,
        sender_user_id INT UNSIGNED NOT NULL,
        recipient_role VARCHAR(20) NOT NULL,
        recipient_user_id INT UNSIGNED NOT NULL,
        message_text TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_messages_sender (sender_role, sender_user_id),
        INDEX idx_admin_messages_recipient (recipient_role, recipient_user_id),
        INDEX idx_admin_messages_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$mysqli->query($sql)) {
        return false;
    }

    return !$mysqli->error;
}

function normalize_booking_meet_time($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $formats = [
        'Y-m-d\TH:i',
        'Y-m-d\TH:i:s',
        'Y-m-d H:i',
        'Y-m-d H:i:s'
    ];

    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        $errors = DateTime::getLastErrors();
        $warningCount = is_array($errors) ? (int) ($errors['warning_count'] ?? 0) : 0;
        $errorCount = is_array($errors) ? (int) ($errors['error_count'] ?? 0) : 0;
        if ($date instanceof DateTime && $warningCount === 0 && $errorCount === 0) {
            return $date->format('Y-m-d H:i:s');
        }
    }

    $timestamp = strtotime($value);
    if ($timestamp !== false) {
        return date('Y-m-d H:i:s', $timestamp);
    }

    return null;
}

function normalize_booking_location($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (mb_strlen($value) > 255) {
        $value = mb_substr($value, 0, 255);
    }

    return $value;
}

function normalize_booking_message($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }

    if (mb_strlen($value) > 1000) {
        $value = mb_substr($value, 0, 1000);
    }

    return $value;
}

function get_tourist_by_user_id(mysqli $mysqli, $userId)
{
    $stmt = $mysqli->prepare("SELECT tourist_id, first_name, last_name FROM tourists WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

function get_guide_id_by_user_id(mysqli $mysqli, $userId)
{
    $stmt = $mysqli->prepare("SELECT guide_id FROM tour_guides WHERE user_id = ? LIMIT 1");
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ? (int) $row['guide_id'] : 0;
}
?>
