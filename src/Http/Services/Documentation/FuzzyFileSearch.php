<?php

namespace Vis\Builder\Services\Documentation;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Vis\Builder\Interfaces\DocSearchInterface;

/**
 * Быстрый поиск по файлам с нечетким поиском и автоматической индексацией
 *
 * Автоматически создает и обновляет индекс для мгновенного поиска среди тысяч файлов.
 * Поддерживает fuzzy search на кириллице и латинице, поиск по содержимому И названию файлов.
 *
 * @example
 * $search = new FuzzyFileSearch();
 *
 * // Поиск по названию и содержимому файлов
 * // Находит "my-article.ru.html" по запросу "article"
 * // Находит "user_guide.en.html" по запросу "guide"
 * $results = $search->search('article', '/путь/к/файлам', 'html', 'ru', 0.3, 20);
 *
 * // Поиск только среди русских файлов (filename.ru.html)
 * $results = $search->search('поисковый запрос', '/путь/к/файлам', 'html', 'ru', 0.3, 20);
 *
 * // Поиск среди всех файлов (любой язык)
 * $results = $search->search('search query', '/путь/к/файлам', 'html', null, 0.3, 20);
 *
 * // Результат:
 * // [['file' => '/path/filename.ru.html', 'matches' => ['запрос'], 'score' => 0.85], ...]
 *
 * // Получить сниппет из файла
 * $snippet = $search->snippet($fileContent, 'запрос', 200);
 *
 * // Принудительно обновить индекс
 * $search->refreshIndex('/путь/к/файлам', 'html', 'ru');
 *
 * @author Linecore <https://linecore.ua> Тельный Сергей tsv.art.com@gmail.com
 */
class FuzzyFileSearch implements DocSearchInterface
{
    private const CACHE_TTL = 3600; // 1 час
    private const INDEX_CACHE_KEY = 'fuzzy_search_index';
    private const MAX_FILE_SIZE = 1048576; // 1MB
    private const BATCH_SIZE = 100; // файлов в пакете

    /** @var array Инвертированный индекс [ngram => [file_path => count]] */
    private array $invertedIndex = [];

    /** @var array Метаданные файлов [file_path => ['size' => ..., 'modified' => ...]] */
    private array $fileMetadata = [];

    /** @var bool Индекс загружен */
    private bool $indexLoaded = false;

    public function search(
        string $query,
        string $dir,
        string $ext = 'html',
        ?string $lang = null,
        float $threshold = 0.3,
        int $limit = 100,
        int $offset = 0
    ): array {
        if (!is_dir($dir)) {
            return [];
        }

        // Загружаем или создаем индекс
        $this->ensureIndexLoaded($dir, $ext, $lang);

        // Быстрый поиск через индекс с учетом языка
        $candidates = $this->findCandidatesFromIndex($query, $threshold, $lang);

        // Сортировка по релевантности
        usort($candidates, static fn($a, $b) => $b['score'] <=> $a['score']);

        // Пагинация
        $paginated = array_slice($candidates, $offset, $limit);

        // Финальная проверка и формирование результатов
        return $this->finalizeResults($paginated, $query, $threshold);
    }

    /**
     * Быстрое создание или обновление индекса с проверкой необходимости
     */
    public function buildIndex(string $dir, string $ext = 'html', ?string $lang = null, bool $force = false): void
    {
        $cacheKey = $this->getIndexCacheKey($dir, $ext, $lang);

        // Если принудительное обновление не требуется, проверяем кеш
        if (!$force) {
            $cachedIndex = Cache::get($cacheKey);
            if ($cachedIndex && $this->isIndexValid($cachedIndex, $dir, $ext, $lang)) {
                $this->invertedIndex = $cachedIndex['inverted_index'];
                $this->fileMetadata = $cachedIndex['file_metadata'];
                $this->indexLoaded = true;
                return;
            }
        }

        $pattern = $dir . '/*.' . ($lang ? $lang . '.' : '') . $ext;
        $files = File::glob($pattern) ?: [];

        if (empty($files)) {
            \Log::warning("FuzzyFileSearch: Нет файлов для индексации по паттерну: {$pattern}");
            return;
        }

        \Log::info("FuzzyFileSearch: Начинаем индексацию файлов", [
            'count' => count($files),
            'pattern' => $pattern,
            'language' => $lang ?? 'все языки'
        ]);

        $this->invertedIndex = [];
        $this->fileMetadata = [];

        // Обрабатываем файлы пакетами
        $batches = array_chunk($files, self::BATCH_SIZE);

        foreach ($batches as $batchIndex => $batch) {
            $this->processBatch($batch, $ext);

            // Логируем прогресс каждые 5 пакетов
            if ($batchIndex % 5 === 0) {
                $processed = ($batchIndex + 1) * self::BATCH_SIZE;
                \Log::info("FuzzyFileSearch: Обработано файлов {$processed} из " . count($files));
            }

            // Освобождаем память между пакетами
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        // Сохраняем индекс в кеш
        Cache::put($cacheKey, [
            'inverted_index' => $this->invertedIndex,
            'file_metadata' => $this->fileMetadata,
            'created_at' => time()
        ], self::CACHE_TTL);

        $this->indexLoaded = true;

        \Log::info("FuzzyFileSearch: Индексация завершена", [
            'files_count' => count($this->fileMetadata),
            'ngrams_count' => count($this->invertedIndex),
            'memory_usage' => memory_get_usage(true)
        ]);
    }

    /**
     * Обработка пакета файлов
     */
    private function processBatch(array $files, string $ext): void
    {
        foreach ($files as $filePath) {
            if (!file_exists($filePath)) {
                continue;
            }

            $fileSize = filesize($filePath);
            if ($fileSize > self::MAX_FILE_SIZE) {
                continue; // Пропускаем слишком большие файлы
            }

            $this->fileMetadata[$filePath] = [
                'size' => $fileSize,
                'modified' => filemtime($filePath)
            ];

            // Потоковое чтение файла
            $content = $this->readFileStream($filePath);
            if ($content === false) {
                continue;
            }

            $this->indexFileContent($filePath, $content, $ext);
        }
    }

    /**
     * Потоковое чтение файла с ограничением размера
     */
    private function readFileStream(string $filePath): string|false
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return false;
        }

        $content = stream_get_contents($handle, self::MAX_FILE_SIZE);
        fclose($handle);

        return $content;
    }

    /**
     * Индексация содержимого файла и его названия
     */
    private function indexFileContent(string $filePath, string $content, string $ext): void
    {
        // 1. Извлекаем заголовок документа для индексации
        $title = $this->extractTitleFromContent($content, $ext);
        $cleanTitle = mb_strtolower($title, 'UTF-8');
        $cleanTitle = preg_replace('/\s+/', ' ', trim($cleanTitle));

        // 2. Индексируем содержимое файла
        $cleanContent = strip_tags($content);
        $cleanContent = mb_strtolower($cleanContent, 'UTF-8');
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);

        // 3. Индексируем название файла (с повышенным весом)
        $fileName = $this->extractSearchableFileName($filePath);
        $cleanFileName = mb_strtolower($fileName, 'UTF-8');
        $cleanFileName = preg_replace('/[_\-\.]+/', ' ', $cleanFileName); // заменяем разделители на пробелы
        $cleanFileName = preg_replace('/\s+/', ' ', trim($cleanFileName));

        // Объединяем контент с названием и заголовком (повышаем вес названия и заголовка)
        // Заголовок и название файла повторяем несколько раз для релевантности
        $searchableText = implode(' ', [
            $cleanTitle, $cleanTitle, $cleanTitle, $cleanTitle,
            $cleanFileName, $cleanFileName, $cleanFileName,
            $cleanContent
        ]);

        // Создаем n-граммы разных размеров для лучшего поиска
        $ngrams = [];
        for ($n = 2; $n <= 4; $n++) {
            $ngrams = array_merge($ngrams, $this->makeNgrams($searchableText, $n));
        }

        // Подсчитываем частоту n-грамм
        $ngramCounts = array_count_values($ngrams);

        // Добавляем в инвертированный индекс
        foreach ($ngramCounts as $ngram => $count) {
            if (!isset($this->invertedIndex[$ngram])) {
                $this->invertedIndex[$ngram] = [];
            }
            $this->invertedIndex[$ngram][$filePath] = $count;
        }
    }

    /**
     * Извлечение поисковой части названия файла
     */
    private function extractSearchableFileName(string $filePath): string
    {
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        // Убираем языковую часть из названия (например: "article.ru" -> "article")
        if (preg_match('/^(.+)\.[a-z]{2,3}$/', $fileName, $matches)) {
            $fileName = $matches[1];
        }

        return $fileName;
    }

    /**
     * Поиск кандидатов через инвертированный индекс с учетом языка
     */
    private function findCandidatesFromIndex(string $query, float $threshold, ?string $lang = null): array
    {
        $normalizedQuery = mb_strtolower($query, 'UTF-8');
        $queryNgrams = [];

        // Создаем n-граммы запроса
        for ($n = 2; $n <= 4; $n++) {
            $queryNgrams = array_merge($queryNgrams, $this->makeNgrams($normalizedQuery, $n));
        }

        $queryNgrams = array_unique($queryNgrams);

        // Собираем кандидатов из индекса
        $candidates = [];
        foreach ($queryNgrams as $ngram) {
            if (isset($this->invertedIndex[$ngram])) {
                foreach ($this->invertedIndex[$ngram] as $filePath => $count) {
                    // Фильтруем по языку если указан
                    if ($lang !== null && !$this->matchesLanguage($filePath, $lang)) {
                        continue;
                    }

                    if (!isset($candidates[$filePath])) {
                        $candidates[$filePath] = ['hits' => 0, 'total_ngrams' => 0];
                    }
                    $candidates[$filePath]['hits'] += $count;
                    $candidates[$filePath]['total_ngrams']++;
                }
            }
        }

        // Фильтруем по порогу и вычисляем релевантность
        $results = [];
        $totalQueryNgrams = count($queryNgrams);

        foreach ($candidates as $filePath => $data) {
            $score = $data['total_ngrams'] / $totalQueryNgrams;
            if ($score >= $threshold) {
                $results[] = [
                    'file' => $filePath,
                    'score' => $score,
                    'hits' => $data['hits']
                ];
            }
        }

        return $results;
    }

    /**
     * Проверка соответствия файла указанному языку
     */
    private function matchesLanguage(string $filePath, string $lang): bool
    {
        $filename = basename($filePath);

        // Паттерн для файлов с языком: filename.lang.extension
        // Например: page.ru.html, article.en.html
        $pattern = '/\.' . preg_quote($lang, '/') . '\.[^.]+$/';

        return preg_match($pattern, $filename) === 1;
    }

    /**
     * Финализация результатов с точной проверкой
     */
    private function finalizeResults(array $candidates, string $query, float $threshold): array
    {
        $results = [];

        foreach ($candidates as $candidate) {
            $filePath = $candidate['file'];

            // Быстрая проверка существования файла
            if (!file_exists($filePath)) {
                continue;
            }

            // Для высоко релевантных результатов пропускаем повторную проверку
            if ($candidate['score'] > 0.8) {
                $results[] = [
                    'file' => $filePath,
                    'matches' => [$query],
                    'score' => $candidate['score']
                ];
                continue;
            }

            // Для остальных делаем точную проверку
            $content = $this->readFileStream($filePath);
            if ($content && $this->matchesQuery($content, $query, $threshold, $filePath)) {
                $results[] = [
                    'file' => $filePath,
                    'matches' => [$query],
                    'score' => $candidate['score']
                ];
            }
        }

        return $results;
    }

    /**
     * Обеспечение загрузки индекса с автоматической переиндексацией
     */
    private function ensureIndexLoaded(string $dir, string $ext, ?string $lang): void
    {
        if ($this->indexLoaded) {
            return;
        }

        $cacheKey = $this->getIndexCacheKey($dir, $ext, $lang);
        $cachedIndex = Cache::get($cacheKey);

        if ($cachedIndex && $this->isIndexValid($cachedIndex, $dir, $ext, $lang)) {
            $this->invertedIndex = $cachedIndex['inverted_index'];
            $this->fileMetadata = $cachedIndex['file_metadata'];
            $this->indexLoaded = true;
        } else {
            // Кеш отсутствует или устарел - создаем заново
            $this->buildIndex($dir, $ext, $lang, true);
        }
    }

    /**
     * Проверка валидности индекса (проверяем изменения в директории)
     */
    private function isIndexValid(array $cachedIndex, string $dir, string $ext, ?string $lang): bool
    {
        // Проверяем время создания индекса
        $indexAge = time() - ($cachedIndex['created_at'] ?? 0);
        if ($indexAge > self::CACHE_TTL) {
            return false;
        }

        // Быстрая проверка - сравниваем количество файлов
        $pattern = $dir . '/*.' . ($lang ? $lang . '.' : '') . $ext;
        $currentFiles = File::glob($pattern) ?: [];
        $cachedFileCount = count($cachedIndex['file_metadata'] ?? []);

        if (count($currentFiles) !== $cachedFileCount) {
            return false;
        }

        // Выборочная проверка изменений файлов (проверяем каждый 10-й файл)
        $filesToCheck = array_slice($currentFiles, 0, min(10, count($currentFiles)), true);

        foreach ($filesToCheck as $index => $file) {
            if ($index % 10 === 0) { // каждый 10-й файл
                $currentModified = filemtime($file);
                $cachedModified = $cachedIndex['file_metadata'][$file]['modified'] ?? 0;

                if ($currentModified > $cachedModified) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Генерация ключа кеша для индекса
     */
    private function getIndexCacheKey(string $dir, string $ext, ?string $lang): string
    {
        return self::INDEX_CACHE_KEY . '_' . md5($dir . '_' . $ext . '_' . ($lang ?: 'all'));
    }

    /**
     * Оптимизированное создание n-грамм
     */
    private function makeNgrams(string $str, int $n): array
    {
        $length = mb_strlen($str, 'UTF-8');

        if ($length < $n) {
            return [];
        }

        $ngrams = [];
        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = mb_substr($str, $i, $n, 'UTF-8');
        }

        return $ngrams;
    }

    /**
     * Быстрая проверка совпадения с учетом названия файла
     */
    private function matchesQuery(string $text, string $query, float $threshold, string $filePath = ''): bool
    {
        $normalizedText = mb_strtolower(strip_tags($text), 'UTF-8');
        $normalizedQuery = mb_strtolower($query, 'UTF-8');

        // 1. Проверяем название файла (приоритетный поиск)
        if (!empty($filePath)) {
            $fileName = $this->extractSearchableFileName($filePath);
            $normalizedFileName = mb_strtolower($fileName, 'UTF-8');
            $normalizedFileName = preg_replace('/[_\-\.]+/', ' ', $normalizedFileName);

            // Точное совпадение в названии файла
            if (mb_strpos($normalizedFileName, $normalizedQuery, 0, 'UTF-8') !== false) {
                return true;
            }

            // Fuzzy поиск в названии с более низким порогом
            if ($this->simpleFuzzyMatch($normalizedFileName, $normalizedQuery, max(0.2, $threshold - 0.2))) {
                return true;
            }
        }

        // 2. Точное совпадение в тексте
        if (mb_strpos($normalizedText, $normalizedQuery, 0, 'UTF-8') !== false) {
            return true;
        }

        // 3. Для коротких запросов не делаем fuzzy поиск в тексте
        if (mb_strlen($normalizedQuery, 'UTF-8') < 3) {
            return false;
        }

        // 4. Fuzzy поиск в тексте
        return $this->simpleFuzzyMatch($normalizedText, $normalizedQuery, $threshold);
    }

    /**
     * Упрощенный fuzzy поиск для финальной проверки
     */
    private function simpleFuzzyMatch(string $text, string $query, float $threshold): bool
    {
        $queryLength = mb_strlen($query, 'UTF-8');
        $textLength = mb_strlen($text, 'UTF-8');
        $queryNgrams = $this->makeNgrams($query, 3);

        if (empty($queryNgrams)) {
            return false;
        }

        $step = max(1, intval($queryLength / 3));

        for ($i = 0; $i <= $textLength - $queryLength; $i += $step) {
            $window = mb_substr($text, $i, $queryLength, 'UTF-8');
            $windowNgrams = $this->makeNgrams($window, 3);

            if (!empty($windowNgrams)) {
                $intersect = count(array_intersect($queryNgrams, $windowNgrams));
                $union = count(array_unique(array_merge($queryNgrams, $windowNgrams)));
                $similarity = $union > 0 ? $intersect / $union : 0;

                if ($similarity >= $threshold) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Оптимизированный сниппет
     */
    public function snippet(string $text, string $query, int $length = 150): string
    {
        if (empty($text) || empty($query)) {
            return '';
        }

        $cleanText = strip_tags($text);
        $cleanText = preg_replace('/\s+/', ' ', trim($cleanText));

        if (mb_strlen($cleanText, 'UTF-8') <= $length) {
            return $cleanText;
        }

        $pos = mb_stripos($cleanText, $query, 0, 'UTF-8');

        if ($pos === false) {
            return mb_substr($cleanText, 0, $length, 'UTF-8') . '...';
        }

        $start = max(0, $pos - intval($length / 2));

        if ($start > 0) {
            $spacePos = mb_strpos($cleanText, ' ', $start, 'UTF-8');
            if ($spacePos !== false && $spacePos - $start < 20) {
                $start = $spacePos + 1;
            }
        }

        $snippet = mb_substr($cleanText, $start, $length, 'UTF-8');

        if ($start + $length < mb_strlen($cleanText, 'UTF-8')) {
            $lastSpace = mb_strrpos($snippet, ' ', 0, 'UTF-8');
            if ($lastSpace !== false && $lastSpace > $length * 0.8) {
                $snippet = mb_substr($snippet, 0, $lastSpace, 'UTF-8');
            }
            $snippet .= '...';
        }

        if ($start > 0) {
            $snippet = '...' . $snippet;
        }

        return $snippet;
    }

    /**
     * Принудительное обновление индекса
     */
    public function refreshIndex(string $dir, string $ext = 'html', ?string $lang = null): void
    {
        $this->buildIndex($dir, $ext, $lang, true);
    }

    /**
     * Очистка всех кешей
     */
    public function clearCache(): void
    {
        Cache::forget(self::INDEX_CACHE_KEY);
        $this->invertedIndex = [];
        $this->fileMetadata = [];
        $this->indexLoaded = false;
    }

    /**
     * Проверка наличия валидного кеша для директории
     */
    public function hasCachedIndex(string $dir, string $ext = 'html', ?string $lang = null): bool
    {
        $cacheKey = $this->getIndexCacheKey($dir, $ext, $lang);
        $cachedIndex = Cache::get($cacheKey);

        if (!$cachedIndex) {
            return false;
        }

        return $this->isIndexValid($cachedIndex, $dir, $ext, $lang);
    }

    /**
     * Получение информации о кеше индекса
     */
    public function getCacheInfo(string $dir, string $ext = 'html', ?string $lang = null): array
    {
        $cacheKey = $this->getIndexCacheKey($dir, $ext, $lang);
        $cachedIndex = Cache::get($cacheKey);

        if (!$cachedIndex) {
            return [
                'exists' => false,
                'valid' => false,
                'files_count' => 0,
                'created_at' => null,
                'age_seconds' => null
            ];
        }

        $createdAt = $cachedIndex['created_at'] ?? 0;
        $age = time() - $createdAt;
        $isValid = $this->isIndexValid($cachedIndex, $dir, $ext, $lang);

        return [
            'exists' => true,
            'valid' => $isValid,
            'files_count' => count($cachedIndex['file_metadata'] ?? []),
            'ngrams_count' => count($cachedIndex['inverted_index'] ?? []),
            'created_at' => $createdAt ? date('Y-m-d H:i:s', $createdAt) : null,
            'age_seconds' => $age,
            'age_human' => $this->formatAge($age)
        ];
    }

    /**
     * Форматирование возраста кеша в человекочитаемом виде
     */
    private function formatAge(int $seconds): string
    {
        if ($seconds < 60) return "{$seconds} сек";
        if ($seconds < 3600) return round($seconds/60) . " мин";
        if ($seconds < 86400) return round($seconds/3600) . " ч";
        return round($seconds/86400) . " дн";
    }
    /**
     * Извлечение заголовка из содержимого документа (для индексации)
     */
    private function extractTitleFromContent(string $content, string $ext): string
    {
        if ($ext === 'html' || $ext === 'htm') {
            if (preg_match('/<meta\s+name=["\']title["\']\s+content=["\']([^"\']*)["\']\s*\/?>/i', $content, $matches)) {
                return $matches[1];
            }
            if (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                return trim(strip_tags($matches[1]));
            }
            if (preg_match('/<h1.*?>(.*?)<\/h1>/i', $content, $matches)) {
                return trim(strip_tags($matches[1]));
            }
        } elseif ($ext === 'md' || $ext === 'markdown') {
            if (preg_match('/^---\\s*\\n.*?title:\\s*(.+?)\\n.*?---\\s*\\n/is', $content, $matches)) {
                return trim($matches[1], '"\' ');
            }
            if (preg_match('/^#\\s+(.+)/m', $content, $matches)) {
                return trim($matches[1]);
            }
        }

        return '';
    }
}