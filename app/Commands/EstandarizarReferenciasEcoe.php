<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class EstandarizarReferenciasEcoe extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'ecoe:estandarizar-referencias';
    protected $description = 'Estandariza los códigos de referencia en GU, EEM y Tarifa Social al nuevo formato.';

    public function run(array $params)
    {
        $db = Database::connect();
        $total = 0;
        $procesados = 0;
        $tablas = [
            // Formatos: tabla, campo tipo, campo dpi, campo id, campo referencia
            ['dyn_ecoe_gu1', 'GU1', 'dpi', 'id', 'codigo_referencia'],
            ['dyn_ecoe_gu2', 'GU2', 'dpi', 'id', 'codigo_referencia'],
            ['dyn_ecoe_eem1', 'EEM1', 'dpi', 'id', 'codigo_referencia'],
            ['dyn_ecoe_eem2', 'EEM2', 'dpi', 'id', 'codigo_referencia'],
            ['dyn_ecoe_eem3', 'EEM3', 'dpi', 'id', 'codigo_referencia'],
            ['ecoe_ts_ticket', 'TS', 'dpi', 'id', 'codigo_referencia'],
        ];

        foreach ($tablas as [$tabla, $tipo, $campoDpi, $campoId, $campoRef]) {
            if (! $db->tableExists($tabla)) {
                CLI::write("[WARN] Tabla no existe: $tabla", 'yellow');
                continue;
            }
            $rows = $db->table($tabla)->select("$campoId, $campoDpi, $campoRef")->get()->getResultArray();
            $total += count($rows);
            foreach ($rows as $row) {
                $id = $row[$campoId];
                $dpi = preg_replace('/\D+/', '', (string)($row[$campoDpi] ?? ''));
                $ult5 = str_pad(substr($dpi, -5), 5, '0', STR_PAD_LEFT);
                $num = str_pad((string)$id, 7, '0', STR_PAD_LEFT);
                $nuevo = $tipo . '-' . $ult5 . '-' . $num;
                if ($row[$campoRef] !== $nuevo) {
                    $db->table($tabla)->where($campoId, $id)->update([$campoRef => $nuevo]);
                    $procesados++;
                }
            }
            CLI::write("[OK] $tabla: $procesados referencias actualizadas.", 'green');
        }
        CLI::write("Total registros procesados: $total. Referencias actualizadas: $procesados.", 'blue');
    }
}
