<?php
$dir = __DIR__ . '/ts_upload_test';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }
for ($i = 1; $i <= 3; $i++) {
  $im = imagecreatetruecolor(50, 50);
  $bg = imagecolorallocate($im, 120 + $i, 80, 180);
  imagefill($im, 0, 0, $bg);
  imagepng($im, $dir . '/gd' . $i . '.png');
  imagedestroy($im);
}
