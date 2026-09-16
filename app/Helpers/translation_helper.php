<?php
/**
 * Helper global de traducciones dinamicas: diccionario global por texto normalizado, con
 * autodeteccion y cache.
 *
 * Flujo de __(key, default):
 *   1. Resuelve el idioma activo desde session('site_locale') (o el locale por defecto de App).
 *   2. Normaliza $default (trim, colapsar espacios, quitar acentos, minusculas) y consulta el
 *      diccionario global cacheado de traducciones ya aprobadas manualmente para ese idioma
 *      (source_text_normalized => translation_value), SIN importar que clave la origino.
 *      Si existe una coincidencia, se retorna de inmediato y la clave actual queda sincronizada
 *      con ese valor (se crea o corrige en BD si hacia falta).
 *   3. Si el texto normalizado no tiene traduccion global aprobada, se busca la clave exacta en
 *      el mapa cacheado por idioma (clave => valor).
 *   4. Si la clave tampoco existe en BD, se inserta automaticamente con is_autodiscovered = 1 y
 *      el valor por defecto recibido, quedando disponible para edicion en el panel administrativo.
 */

use App\Models\TranslationModel;
use Config\App as AppConfig;

if (! function_exists('__')) {
    /**
     * Obtiene el texto traducido para la clave indicada en el idioma activo del visitante,
     * priorizando el diccionario global por texto normalizado sobre la clave exacta.
     */
    function __(string $key, string $default = ''): string
    {
        $key = trim($key);

        if ($key === '') {
            return $default;
        }

        $locale = translation_current_locale();
        $normalized = $default !== '' ? translation_normalize_text($default) : '';

        if ($normalized !== '') {
            $normalizedMap = translation_load_normalized_map($locale);

            if (array_key_exists($normalized, $normalizedMap)) {
                $globalValue = trim($normalizedMap[$normalized]);

                if ($globalValue !== '') {
                    translation_sync_key_with_global_value($locale, $key, $default, $normalized, $globalValue);

                    return $globalValue;
                }
            }
        }

        $map = translation_load_locale_map($locale);

        if (array_key_exists($key, $map)) {
            $value = trim($map[$key]);

            return $value !== '' ? $value : $default;
        }

        return translation_autodiscover_key($locale, $key, $default, $normalized);
    }
}

if (! function_exists('translation_normalize_text')) {
    /**
     * Normaliza un texto para comparacion semantica: colapsa espacios, quita acentos/diacriticos
     * y convierte a minusculas. Se usa para detectar textos identicos entre distintas claves.
     */
    function translation_normalize_text(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if (function_exists('transliterator_transliterate')) {
            $translit = @transliterator_transliterate('Any-Latin; Latin-ASCII;', $text);

            if (is_string($translit) && $translit !== '') {
                $text = $translit;
            }
        } elseif (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);

            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        return mb_strtolower($text, 'UTF-8');
    }
}

if (! function_exists('translation_current_locale')) {
    function translation_current_locale(): string
    {
        $supported = config(AppConfig::class)->supportedLocales;
        $default = config(AppConfig::class)->defaultLocale;
        $locale = strtolower((string) (session('site_locale') ?? $default));

        return in_array($locale, $supported, true) ? $locale : $default;
    }
}

if (! function_exists('translation_cache_key')) {
    function translation_cache_key(string $locale): string
    {
        return 'translations_map_' . $locale;
    }
}

if (! function_exists('translation_normalized_cache_key')) {
    function translation_normalized_cache_key(string $locale): string
    {
        return 'translations_normalized_map_' . $locale;
    }
}

if (! function_exists('translation_load_locale_map')) {
    /**
     * @return array<string, string>
     */
    function translation_load_locale_map(string $locale): array
    {
        $cache = cache();
        $cacheKey = translation_cache_key($locale);
        $map = $cache->get($cacheKey);

        if (is_array($map)) {
            return $map;
        }

        $map = (new TranslationModel())->getAllForLocale($locale);
        $cache->save($cacheKey, $map, 3600);

        return $map;
    }
}

if (! function_exists('translation_load_normalized_map')) {
    /**
     * Diccionario global cacheado: source_text_normalized => translation_value, solo con
     * traducciones aprobadas manualmente (is_autodiscovered = 0).
     *
     * @return array<string, string>
     */
    function translation_load_normalized_map(string $locale): array
    {
        $cache = cache();
        $cacheKey = translation_normalized_cache_key($locale);
        $map = $cache->get($cacheKey);

        if (is_array($map)) {
            return $map;
        }

        $map = (new TranslationModel())->getApprovedNormalizedMapForLocale($locale);
        $cache->save($cacheKey, $map, 3600);

        return $map;
    }
}

if (! function_exists('translation_sync_key_with_global_value')) {
    /**
     * Asegura que la clave actual quede registrada en BD con el valor resuelto por el
     * diccionario global, creando o corrigiendo la fila solo cuando hace falta.
     */
    function translation_sync_key_with_global_value(string $locale, string $key, string $default, string $normalized, string $value): void
    {
        $model = new TranslationModel();
        $sourceText = trim($default);

        $model->upsertResolvedKey($locale, $key, $sourceText !== '' ? $sourceText : null, $normalized, $value);
        translation_clear_cache($locale);
    }
}

if (! function_exists('translation_autodiscover_key')) {
    /**
     * Crea la clave faltante y devuelve el valor efectivo que quedo almacenado (reutilizado o default).
     */
    function translation_autodiscover_key(string $locale, string $key, string $default, string $normalized = ''): string
    {
        $model = new TranslationModel();
        $existing = $model->findByLangAndKey($locale, $key);

        if ($existing !== null) {
            $value = trim((string) $existing['translation_value']);

            return $value !== '' ? $value : $default;
        }

        $sourceText = trim($default);
        $normalized = $normalized !== '' ? $normalized : ($sourceText !== '' ? translation_normalize_text($sourceText) : '');
        $reused = $normalized !== '' ? $model->findApprovedByNormalizedSourceText($locale, $normalized) : null;

        $model->insert([
            'lang_code' => $locale,
            'translation_key' => $key,
            'source_text' => $sourceText !== '' ? $sourceText : null,
            'source_text_normalized' => $normalized !== '' ? $normalized : null,
            'translation_value' => $reused !== null ? (string) $reused['translation_value'] : $default,
            'is_autodiscovered' => $reused !== null ? 0 : 1,
        ]);

        translation_clear_cache($locale);

        return $reused !== null ? (string) $reused['translation_value'] : $default;
    }
}

if (! function_exists('translation_clear_cache')) {
    /**
     * Limpia el cache de traducciones (mapa por clave y diccionario global normalizado).
     * Si no se indica idioma, limpia todos los soportados.
     */
    function translation_clear_cache(?string $locale = null): void
    {
        $cache = cache();
        $locales = $locale !== null ? [$locale] : config(AppConfig::class)->supportedLocales;

        foreach ($locales as $loc) {
            $cache->delete(translation_cache_key($loc));
            $cache->delete(translation_normalized_cache_key($loc));
        }
    }
}
