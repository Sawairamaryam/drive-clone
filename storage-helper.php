<?php

function formatStorageSize($bytes) {
    if ($bytes >= 1073741824) {
        return round($bytes / 1073741824, 2) . " GB";
    }

    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2) . " MB";
    }

    if ($bytes >= 1024) {
        return round($bytes / 1024, 2) . " KB";
    }

    return $bytes . " Bytes";
}

function getStorageInfo($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT storage_limit 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $storageLimit = $user["storage_limit"] ?? 1073741824;

    $stmt = $pdo->prepare("
        SELECT SUM(file_size) AS total_used
        FROM files
        WHERE user_id = ?
        AND is_deleted = 0
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $usedStorage = $result["total_used"] ?? 0;

    $percentage = 0;

    if ($storageLimit > 0) {
        $percentage = ($usedStorage / $storageLimit) * 100;
    }

    if ($percentage > 100) {
        $percentage = 100;
    }

    return [
        "used" => $usedStorage,
        "limit" => $storageLimit,
        "used_formatted" => formatStorageSize($usedStorage),
        "limit_formatted" => formatStorageSize($storageLimit),
        "percentage" => round($percentage, 2)
    ];
}