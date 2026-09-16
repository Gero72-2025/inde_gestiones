<?php

namespace App\Models;

use CodeIgniter\Model;

class TranslationModel extends Model
{
    protected $table = 'translations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'lang_code',
        'translation_key',
        'source_text',
        'source_text_normalized',
        'translation_value',
        'is_autodiscovered',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [
        'lang_code' => 'required|max_length[10]',
        'translation_key' => 'required|max_length[191]',
    ];

    public function findByLangAndKey(string $langCode, string $key): ?array
    {
        $row = $this->where('lang_code', $langCode)
            ->where('translation_key', $key)
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, string> Map translation_key => translation_value for a locale.
     */
    public function getAllForLocale(string $langCode): array
    {
        $rows = $this->where('lang_code', $langCode)->findAll();
        $map = [];

        foreach ($rows as $row) {
            $map[(string) $row['translation_key']] = (string) $row['translation_value'];
        }

        return $map;
    }

    /**
     * Busca una traduccion ya aprobada manualmente (is_autodiscovered = 0) cuyo texto origen
     * normalizado (trim + sin acentos + minusculas) coincida exactamente con el indicado, sin
     * importar la clave. Permite reutilizar traducciones existentes para textos semanticamente
     * identicos en lugar de duplicar el trabajo manual (ej. "Fase 1" vs "fase 1").
     */
    public function findApprovedByNormalizedSourceText(string $langCode, string $normalizedSourceText): ?array
    {
        if (trim($normalizedSourceText) === '') {
            return null;
        }

        $row = $this->where('lang_code', $langCode)
            ->where('source_text_normalized', $normalizedSourceText)
            ->where('is_autodiscovered', 0)
            ->where('translation_value !=', '')
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->getRowArray();

        return is_array($row) ? $row : null;
    }

    /**
     * Diccionario global de traducciones aprobadas manualmente para un idioma, indexado por
     * source_text_normalized => translation_value. Sirve para resolver cualquier clave cuyo
     * texto por defecto coincida, sin importar que clave origino la traduccion.
     *
     * @return array<string, string>
     */
    public function getApprovedNormalizedMapForLocale(string $langCode): array
    {
        $rows = $this->select('source_text_normalized, translation_value')
            ->where('lang_code', $langCode)
            ->where('is_autodiscovered', 0)
            ->where('source_text_normalized IS NOT NULL')
            ->where('translation_value !=', '')
            ->orderBy('updated_at', 'ASC')
            ->findAll();

        $map = [];

        foreach ($rows as $row) {
            $normalized = (string) ($row['source_text_normalized'] ?? '');

            if ($normalized === '') {
                continue;
            }

            // Si hay mas de una traduccion aprobada para el mismo texto, gana la mas reciente.
            $map[$normalized] = (string) $row['translation_value'];
        }

        return $map;
    }

    /**
     * Crea o actualiza la fila de una clave para que quede sincronizada con un valor ya
     * resuelto por el diccionario global (mismo texto origen normalizado, otra clave).
     */
    public function upsertResolvedKey(string $langCode, string $key, ?string $sourceText, ?string $sourceTextNormalized, string $value): void
    {
        $existing = $this->findByLangAndKey($langCode, $key);

        $payload = [
            'lang_code' => $langCode,
            'translation_key' => $key,
            'source_text' => $sourceText !== null && $sourceText !== '' ? $sourceText : null,
            'source_text_normalized' => $sourceTextNormalized !== null && $sourceTextNormalized !== '' ? $sourceTextNormalized : null,
            'translation_value' => $value,
            'is_autodiscovered' => 0,
        ];

        if ($existing === null) {
            $this->insert($payload);

            return;
        }

        if ((int) $existing['is_autodiscovered'] === 0 && trim((string) $existing['translation_value']) === trim($value)) {
            return;
        }

        $this->update((int) $existing['id'], $payload);
    }

    /**
     * Propaga una traduccion recien aprobada hacia otras claves autodetectadas pendientes
     * que comparten el mismo texto origen normalizado e idioma, evitando traducir el mismo
     * texto varias veces.
     */
    public function propagatePendingByNormalizedSourceText(string $langCode, string $normalizedSourceText, string $value, ?int $excludeId = null): int
    {
        if (trim($normalizedSourceText) === '') {
            return 0;
        }

        $builder = $this->where('lang_code', $langCode)
            ->where('source_text_normalized', $normalizedSourceText)
            ->where('is_autodiscovered', 1);

        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }

        $pendingIds = array_map(static fn(array $row): int => (int) $row['id'], $builder->select('id')->findAll());

        if ($pendingIds === []) {
            return 0;
        }

        $this->whereIn('id', $pendingIds)->set([
            'translation_value' => $value,
            'is_autodiscovered' => 0,
        ])->update();

        return count($pendingIds);
    }
}
