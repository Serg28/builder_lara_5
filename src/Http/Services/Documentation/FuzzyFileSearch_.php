<?php

namespace Linecore\Cms\Services\Documentation;

use Illuminate\Support\Facades\File;
use Linecore\Cms\Interfaces\DocSearchInterface;

/**
 * Класс поиска в файлах с нечетким поиском (fuzzy search)
 *
 * @input string $query     Что ищем
 * @input string $dir       Каталог для обхода
 * @input string $ext       Расширение файлов (например, 'html')
 * @input string $lang      Язык (например, 'en', 'ua', 'ru') или null для всех
 * @input float  $threshold Порог схожести от 0 до 1 (по умолчанию 0.3)
 * @return array             Массив результатов [['file' => ..., 'matches' => [...]], ...]
 *
 * @author Linecore <https://linecore.ua> Тельный Сергей tsv.art.com@gmail.com
 */
class FuzzyDocSearch_ implements DocSearchInterface
{
    /** @var array Кеш n-грамм для избежания пересчета */
    private array $ngramCache = [];

    /** @var int Максимальный размер кеша */
    private const MAX_CACHE_SIZE = 1000;

    public function search(
        string $query,
        string $dir,
        string $ext = 'html',
        ?string $lang = null,
        float $threshold = 0.3
    ): array {
        $results = [];
        if (!is_dir($dir)) {
            return $results;
        }

        // Нормализуем запрос один раз
        $normalizedQuery = mb_strtolower($query, 'UTF-8');
        $queryLength = mb_strlen($normalizedQuery, 'UTF-8');

        $pattern = $dir . '/*.' . ($lang ? $lang . '.' : '') . $ext;
        $files = File::glob($pattern) ?: [];

        foreach ($files as $path) {
            $content = File::get($path);

            if ($this->matchesQuery($content, $normalizedQuery, $queryLength, $threshold)) {
                $results[] = [
                    'file'    => $path,
                    'matches' => [$query],
                ];
            }
        }

        return $results;
    }

    /**
     * Оптимизированная проверка текста на точное или «размытое» совпадение.
     */
    private function matchesQuery(string $text, string $normalizedQuery, int $queryLength, float $threshold): bool
    {
        // Нормализация текста
        $normalizedText = mb_strtolower($text, 'UTF-8');

        // 1. Быстрое точное вхождение
        if (mb_strpos($normalizedText, $normalizedQuery, 0, 'UTF-8') !== false) {
            return true;
        }

        // 2. Если запрос слишком короткий для fuzzy search
        if ($queryLength < 2) {
            return false;
        }

        // 3. N-граммное приближение с оптимизацией
        return $this->fuzzyMatch($normalizedText, $normalizedQuery, $queryLength, $threshold);
    }

    /**
     * Оптимизированный fuzzy поиск
     */
    private function fuzzyMatch(string $text, string $query, int $queryLength, float $threshold): bool
    {
        $n = min(3, max(2, $queryLength)); // Оптимальный размер n-грамм

        // Получаем n-граммы запроса (с кешированием)
        $cacheKey = $query . '_' . $n;
        if (!isset($this->ngramCache[$cacheKey])) {
            // Очищаем кеш если он переполнен
            if (count($this->ngramCache) >= self::MAX_CACHE_SIZE) {
                $this->ngramCache = [];
            }
            $this->ngramCache[$cacheKey] = $this->makeNgrams($query, $n);
        }
        $queryNgrams = $this->ngramCache[$cacheKey];

        // Быстрый фильтр: проверяем наличие хотя бы одной n-граммы
        $hasCommonNgram = false;
        foreach ($queryNgrams as $ngram) {
            if (mb_strpos($text, $ngram, 0, 'UTF-8') !== false) {
                $hasCommonNgram = true;
                break;
            }
        }

        if (!$hasCommonNgram) {
            return false;
        }

        // Оптимизированное скользящее окно со шагом
        $textLength = mb_strlen($text, 'UTF-8');
        $step = max(1, intval($queryLength / 4)); // Шаг для ускорения

        for ($i = 0; $i <= $textLength - $queryLength; $i += $step) {
            $window = mb_substr($text, $i, $queryLength, 'UTF-8');
            $windowNgrams = $this->makeNgrams($window, $n);

            $similarity = $this->calculateJaccardSimilarity($queryNgrams, $windowNgrams);
            if ($similarity >= $threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Оптимизированное создание n-грамм
     */
    private function makeNgrams(string $str, int $n): array
    {
        $length = mb_strlen($str, 'UTF-8');

        if ($length <= $n) {
            return [$str];
        }

        $ngrams = [];
        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = mb_substr($str, $i, $n, 'UTF-8');
        }

        return array_unique($ngrams); // Убираем дубликаты сразу
    }

    /**
     * Быстрый расчет сходства Жаккара
     */
    private function calculateJaccardSimilarity(array $set1, array $set2): float
    {
        if (empty($set1) || empty($set2)) {
            return 0.0;
        }

        // Используем array_flip для быстрого поиска
        $flipped1 = array_flip($set1);
        $intersection = 0;

        foreach ($set2 as $item) {
            if (isset($flipped1[$item])) {
                $intersection++;
            }
        }

        $union = count($set1) + count($set2) - $intersection;

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Оптимизированный метод создания сниппета
     */
    public function snippet(string $text, string $query, int $length = 150): string
    {
        if (empty($text) || empty($query)) {
            return '';
        }

        // Убираем HTML теги сразу для более точного поиска
        $cleanText = strip_tags($text);
        $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));

        if (mb_strlen($cleanText, 'UTF-8') <= $length) {
            return $cleanText;
        }

        // Ищем позицию вхождения (без учета регистра)
        $pos = mb_stripos($cleanText, $query, 0, 'UTF-8');

        if ($pos === false) {
            // Если точного вхождения нет, возвращаем начало текста
            return mb_substr($cleanText, 0, $length, 'UTF-8') . '...';
        }

        // Вычисляем оптимальную позицию начала сниппета
        $halfLength = intval($length / 2);
        $start = max(0, $pos - $halfLength);

        // Корректируем начало, чтобы не резать слова
        if ($start > 0) {
            $spacePos = mb_strpos($cleanText, ' ', $start, 'UTF-8');
            if ($spacePos !== false && $spacePos - $start < 20) {
                $start = $spacePos + 1;
            }
        }

        $snippet = mb_substr($cleanText, $start, $length, 'UTF-8');

        // Корректируем конец, чтобы не резать слова
        if ($start + $length < mb_strlen($cleanText, 'UTF-8')) {
            $lastSpace = mb_strrpos($snippet, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > $length * 0.8) {
                $snippet = mb_substr($snippet, 0, $lastSpace, 'UTF-8');
            }
            $snippet .= '...';
        }

        // Добавляем многоточие в начале, если сниппет не с начала текста
        if ($start > 0) {
            $snippet = '...' . $snippet;
        }

        return $snippet;
    }

    /**
     * Очистка кеша (можно вызывать периодически)
     */
    public function clearCache(): void
    {
        $this->ngramCache = [];
    }
}