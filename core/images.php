<?php
declare(strict_types=1);

const LARGE_MAX = 2400;
const THUMB_MAX = 720;
const MAX_UPLOAD_BYTES = 40 * 1024 * 1024;

function image_load(string $path, string $mime) {
    $img = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png'  => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default      => false,
    };
    if (!$img) return false;
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $ex = @exif_read_data($path);
        $o = $ex['Orientation'] ?? 1;
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) { $rot = imagerotate($img, $angle, 0); if ($rot) { imagedestroy($img); $img = $rot; } }
    }
    return $img;
}

function image_save($img, string $dest, int $maxSide, bool $webp): array {
    $w = imagesx($img); $h = imagesy($img);
    $scale = min(1, $maxSide / max($w, $h));
    $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, true);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = $webp ? imagewebp($out, $dest, 82) : imagejpeg($out, $dest, 86);
    imagedestroy($out);
    if (!$ok) throw new RuntimeException('Не удалось сохранить изображение');
    return [$nw, $nh];
}

/** @return array{file:string,thumb:string,width:int,height:int} */
function process_upload(array $f, int $galleryId): array {
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('Ошибка загрузки (код ' . ($f['error'] ?? '?') . '). Проверьте upload_max_filesize на хостинге.');
    if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Файл больше 40 МБ');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) throw new RuntimeException('Допустимы JPG, PNG и WebP');
    $dir = ROOT . '/uploads/' . ($galleryId > 0 ? 'g' . $galleryId : 'media');
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Не удалось создать папку галереи');
    $img = image_load($f['tmp_name'], $mime);
    if (!$img) throw new RuntimeException('Файл повреждён или не читается');
    $webp = function_exists('imagewebp'); $ext = $webp ? 'webp' : 'jpg';
    $name = bin2hex(random_bytes(8));
    [$w, $h] = image_save($img, "$dir/$name.$ext", LARGE_MAX, $webp);
    image_save($img, "$dir/{$name}_t.$ext", THUMB_MAX, $webp);
    imagedestroy($img);
    return ['file' => "$name.$ext", 'thumb' => "{$name}_t.$ext", 'width' => $w, 'height' => $h];
}

function delete_photo_files(int $galleryId, array $photo): void {
    foreach ([$photo['file'], $photo['thumb']] as $fn) {
        $p = ROOT . '/uploads/' . ($galleryId > 0 ? 'g' . $galleryId : 'media') . '/' . basename($fn);
        if (is_file($p)) @unlink($p);
    }
}
