<?php
$payload = trim((string) file_get_contents('php://stdin'));
$configured = 'base64:MW5kM0d1YXRlbWFsYS0yMDI2';
$keyMaterial = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : $configured;
$key = strlen($keyMaterial) === 32 ? $keyMaterial : hash('sha256', $keyMaterial, true);
$raw = base64_decode($payload, true);
$iv = substr($raw, 0, 16);
$cipher = substr($raw, 48);
echo openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv), PHP_EOL;
