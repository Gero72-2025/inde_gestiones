<?php
$dir = __DIR__ . '/ts_upload_test';
if (!is_dir($dir)) { mkdir($dir, 0777, true); }
for ($i = 1; $i <= 3; $i++) {
  $im = imagecreatetruecolor(140, 90);
  $bg = imagecolorallocate($im, 40 * $i, 80, 140);
  imagefill($im, 0, 0, $bg);
  imagepng($im, $dir . '/pdf' . $i . '.png');
  imagedestroy($im);
}
