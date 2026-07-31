<?php
$payload = 'lpq+6+CuyoK1cpp2sceoo3N8elEKIzFxGTQVVfb54COsKanIAUAOl7Gtmd5wwH6X9U3Af4EdyeO0gW/smjXyFngwVi6n0tFKcvCRHmHDOoGA/s1KWEVxs3mpgX6yGJvcZ9NC3zMxhgDn7JJj1OOMAhZV7dNZgzJAAd3dk2gvtY414tb7AfqFKNMjgMa4H1gcDzam6sm065HYYhnnY+eWlktTxtxhY3XORApFzZAhPyY=';
$configured = 'base64:MW5kM0d1YXRlbWFsYS0yMDI2';
$keyMaterial = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : $configured;
$key = strlen($keyMaterial) === 32 ? $keyMaterial : hash('sha256', $keyMaterial, true);
$raw = base64_decode($payload, true);
$iv = substr($raw, 0, 16);
$cipher = substr($raw, 48);
$plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
echo $plain, PHP_EOL;
