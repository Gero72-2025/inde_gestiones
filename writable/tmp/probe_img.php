<?php
$raw = file_get_contents(__DIR__ . '/ts_upload_test/ok1.png');
var_dump(strlen((string) $raw));
$im = @imagecreatefromstring((string) $raw);
var_dump($im !== false);
if ($im !== false) {
    imagedestroy($im);
}
