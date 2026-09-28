<?php

namespace App\Modules\Ecoe\Models;

use DateTimeImmutable;

class TarifaMensualModel extends EcoeBaseModel
{
    protected $table = 'ecoe_tarifas_mensuales';
    protected $allowedFields = ['distribuidora_id', 'anio', 'mes', 'tarifa_plena', 'tarifa_social'];

    public function listOrdenados(?int $distribuidoraId = null): array
    {
        $builder = $this->select('ecoe_tarifas_mensuales.*, ecoe_distribuidoras.nombre AS distribuidora_nombre')
            ->join('ecoe_distribuidoras', 'ecoe_distribuidoras.id = ecoe_tarifas_mensuales.distribuidora_id', 'left');

        if ($distribuidoraId !== null && $distribuidoraId > 0) {
            $builder->where('ecoe_tarifas_mensuales.distribuidora_id', $distribuidoraId);
        }

        return $builder->orderBy('ecoe_tarifas_mensuales.anio', 'DESC')
            ->orderBy('ecoe_tarifas_mensuales.mes', 'DESC')
            ->findAll();
    }

    public function existsForPeriod(int $distribuidoraId, int $year, int $month, int $exceptId = 0): bool
    {
        $builder = $this->where('distribuidora_id', $distribuidoraId)
            ->where('anio', $year)
            ->where('mes', $month);

        if ($exceptId > 0) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Retorna las tarifas del trimestre cíclico más reciente que tenga sus
    * tres meses cargados para una distribuidora, indexadas en orden por
    * posición trimestral (1, 2 y 3).
     *
     * @return array<int, array{plena: float, social: float}>
     */
    public function getLatestQuarterRates(int $distribuidoraId, ?int $year = null, ?int $month = null): array
    {
        if ($distribuidoraId <= 0) {
            return [];
        }

        $year ??= (int) date('Y');
        $month ??= (int) date('n');

        if ($month < 1 || $month > 12) {
            return [];
        }

        $records = $this->where('distribuidora_id', $distribuidoraId)
            ->orderBy('anio', 'ASC')
            ->orderBy('mes', 'ASC')
            ->findAll();

        if ($records === []) {
            return [];
        }

        $byPeriod = [];
        foreach ($records as $record) {
            $byPeriod[sprintf('%04d-%02d', (int) $record['anio'], (int) $record['mes'])] = $record;
        }

        $earliestPeriod = min(array_keys($byPeriod));
        $quarterStartYear = $month === 1 ? $year - 1 : $year;
        $quarterStartMonth = match (true) {
            $month === 1 || $month >= 11 => 11,
            $month <= 4 => 2,
            $month <= 7 => 5,
            default => 8,
        };
        $quarterStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $quarterStartYear, $quarterStartMonth));

        while (true) {
            $quarterRecords = [];
            for ($offset = 0; $offset < 3; $offset++) {
                $period = $quarterStart->modify('+' . $offset . ' months');
                $periodKey = $period->format('Y-m');

                if (! isset($byPeriod[$periodKey])) {
                    $quarterRecords = [];
                    break;
                }

                $record = $byPeriod[$periodKey];
                if ((float) $record['tarifa_plena'] <= 0.0 || (float) $record['tarifa_social'] <= 0.0) {
                    $quarterRecords = [];
                    break;
                }

                $quarterRecords[] = $record;
            }

            if (count($quarterRecords) === 3) {
                $rates = [];
                foreach ($quarterRecords as $position => $record) {
                    $rates[$position + 1] = [
                        'plena' => (float) $record['tarifa_plena'],
                        'social' => (float) $record['tarifa_social'],
                    ];
                }

                return $rates;
            }

            $quarterStart = $quarterStart->modify('-3 months');
            $quarterEnd = $quarterStart->modify('+2 months')->format('Y-m');
            if ($quarterEnd < $earliestPeriod) {
                break;
            }
        }

        return [];
    }
}