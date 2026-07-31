<?php
require 'system/bootstrap.php';
$db = \CodeIgniter\Database\Database::connect('ecoe');
$builder = $db->query("EXEC sp_ObtenerHistorialPorAnioDeocsa @id_usuario = ?, @anio = ?", ['5229034', '2026']);
var_dump($builder->getResultArray());
