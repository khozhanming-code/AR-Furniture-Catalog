<?php
/**
 * includes/uploads.php
 * Secure handling for 3D model and thumbnail image uploads.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Validates and stores an uploaded 3D model file (.glb/.gltf/.usdz).
 * Returns the relative path (e.g. "models/ab12cd34.glb") to store in
 * the database, or null if no file was uploaded for this field.
 *
 * @throws RuntimeException on validation failure.
 */
function upload_model_file(array $fileField): ?string
{
    if (empty($fileField['name'])) {
        return null;
    }
    return upload_generic_file(
        $fileField,
        app_config()['uploads']['models_dir'],
        app_config()['uploads']['allowed_model_ext'],
        (int) app_config()['uploads']['max_model_mb']
    );
}

/**
 * Validates and stores an uploaded thumbnail image.
 * Returns the relative path (e.g. "uploads/thumbs/ab12cd34.jpg"),
 * or null if no file was uploaded.
 */
function upload_thumbnail_file(array $fileField): ?string
{
    if (empty($fileField['name'])) {
        return null;
    }
    return upload_generic_file(
        $fileField,
        app_config()['uploads']['thumbs_dir'],
        app_config()['uploads']['allowed_image_ext'],
        (int) app_config()['uploads']['max_image_mb']
    );
}

/**
 * Shared validation + move-uploaded-file logic.
 */
function upload_generic_file(array $fileField, string $relativeDir, array $allowedExt, int $maxMb): string
{
    if (($fileField['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed (error code ' . ($fileField['error'] ?? 'unknown') . ').');
    }

    $tmpPath = $fileField['tmp_name'] ?? '';
    if (!is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid upload.');
    }

    $sizeMb = filesize($tmpPath) / (1024 * 1024);
    if ($sizeMb > $maxMb) {
        throw new RuntimeException("File is too large. Maximum allowed size is {$maxMb} MB.");
    }

    $originalName = (string) ($fileField['name'] ?? '');
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException(
            'Unsupported file type "' . h($ext) . '". Allowed: ' . implode(', ', $allowedExt)
        );
    }

    // Best-effort MIME sniffing for image files (GLB/USDZ often report
    // application/octet-stream, so we do not hard-fail on those, only
    // reject anything that MIME-sniffs as a script/executable/html).
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath) ?: '';
        finfo_close($finfo);

        $dangerous = ['text/x-php', 'application/x-httpd-php', 'text/html', 'application/x-sh'];
        if (in_array($mime, $dangerous, true)) {
            throw new RuntimeException('This file type is not allowed for security reasons.');
        }
    }

    $root = dirname(__DIR__);
    $destDir = $root . '/' . trim($relativeDir, '/');
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    // Never trust or reuse the original filename.
    $newFilename = random_filename($originalName);
    $destPath = $destDir . '/' . $newFilename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        throw new RuntimeException('Could not save the uploaded file.');
    }

    return trim($relativeDir, '/') . '/' . $newFilename;
}
