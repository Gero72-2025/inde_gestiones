<?php
$dir = __DIR__ . '/ts_upload_test';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }
for ($i = 1; $i <= 3; $i++) {
  $im = imagecreatetruecolor(120, 80);
  $bg = imagecolorallocate($im, 30 * $i, 120, 140);
  imagefill($im, 0, 0, $bg);
  imagepng($im, $dir . '/b' . $i . '.png');
  imagedestroy($im);
}
