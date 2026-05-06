<?php

namespace Vis\Builder;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;
use Vis\Builder\Http\Requests\ShowDocRequest;
use Vis\Builder\Interfaces\DocSearchInterface;
use Vis\Builder\Helpers\Traits\ResolvesDefinitionTitle;

// TODO: вынести логику из контроллера, рефакторинг
class DocsController extends Controller
{
    use ResolvesDefinitionTitle;

    private string $fileExt;

    private string $dir;
    /**
     * Объединённая страница документации: поиск, список, рабочая область.
     */
    public function index(DocSearchInterface $fileSearch, ShowDocRequest $request)
    {
        $query = trim(request('q', ''));
        $this->dir  = config('builder.documentation.path_app', resource_path('docs/definitions'));
        $this->fileExt = config('builder.documentation.docs_extensions', 'html');

        return view('admin::documentation_page.index', [
            'documents' => $this->list(),
            'currentDocument' => $this->show($request),
            'searchResults' => $this->search($fileSearch, $query),
            'query' => $query,
        ]);
    }

    private function list()
    {
        $docs = [];
        // $dir  = config('builder.documentation.path_app', resource_path('docs/definitions'));
        if (!is_dir($this->dir)) {
            return view('admin::documentation_page.index', ['docs' => []]);
        }

        // Группируем файлы по базовому имени
        $filesByBase = [];
        foreach (File::files($this->dir) as $file) {
            $filename = $file->getFilename();
            $parts = explode('.', $filename);
            if (count($parts) < 2) continue;

            $base = strtolower($parts[0]);
            $filesByBase[$base][] = $file;
        }

        foreach ($filesByBase as $base => $files) {
            $title = '';
            $languages = [];

            // Обрабатываем все языковые версии файла
            foreach ($files as $file) {
                $filename = $file->getFilename();
                $parts = explode('.', $filename);

                if (count($parts) >= 3) {
                    $lang = strtolower($parts[1]);
                } else {
                    $lang = defaultLanguage();
                }

                $languages[] = $lang;

                // Извлекаем заголовок из содержимого файла (кастомный заголовок имеет максимальный приоритет)
                if (empty($title)) {
                    $content = File::get($file->getPathname());
                    $title = $this->extractTitleFromContent($content, $this->fileExt) ?: $this->resolveDefinitionTitle($base) ?: $base;
                }
            }

            $docs[$base]['definition'] = $base;
            $docs[$base]['title'] = $title;
            $docs[$base]['languages'] = array_unique($languages);
            $docs[$base]['link'] = route('admin.docs.show', [
                'definition' => $base
            ]);
        }

        ksort($docs);

        return array_values($docs);
    }

    private function show(ShowDocRequest $request): ?array
    {
        $definition = $request->validatedDefinition() ?? null;
        if(!$definition) return null;

        // $dir = config('builder.documentation.path_app', resource_path('docs/definitions'));

        if (!File::isDirectory($this->dir)) {
            return null;
        }

        $thisLang = adminLang() ?? defaultLanguage();
        $default  = defaultLanguage();

        $path = $this->findDocFile($this->dir, $definition, $thisLang)
            ?? $this->findDocFile($this->dir, $definition, $default)
            ?? $this->findDocFile($this->dir, $definition);

        if (!$path) {
            return null;
        }

        $content = File::get($path);

        // Извлекаем заголовок из содержимого файла (кастомный заголовок имеет максимальный приоритет)
        $title = $this->extractTitleFromContent($content, $this->fileExt) ?: $this->resolveDefinitionTitle($definition) ?: $definition;

        return  [
            'title'   => $title,
            'content' => $content,
        ];
    }

    /**
     * Поиск по содержимому HTML-документации (с учётом текущего языка).
     */
    private function search(DocSearchInterface $fileSearch, $query = ''): ?array
    {
        $query = trim($query);

        if ($query === '') {
            return null;
        }

        // текущий язык админки
        $lang = adminLang();

        $files = $fileSearch->search($query, $this->dir, $this->fileExt, $lang, 0.3, 50, 0);
        $results = [];

        foreach ($files as $item) {
            $filePath = $item['file'] ?? null;
            $matches  = $item['matches'] ?? [];
            if (!$filePath) continue;

            $fileName = basename($filePath);
            $baseName = strtolower(preg_replace("/\.{$lang}\." . $this->fileExt . "$/i", '', $fileName));

            // Читаем контент файла для генерации snippet
            $content = file_get_contents($filePath);

            // Если fuzzy поиск, можем взять первое совпадение из matches
            $snippetQuery = $matches[0] ?? $query;

            // Извлекаем заголовок из содержимого файла (кастомный заголовок имеет максимальный приоритет)
            $title = $this->extractTitleFromContent($content, $this->fileExt) ?: $this->resolveDefinitionTitle($baseName) ?: $baseName;

            $results[] = [
                'name'    => $title,
                'link'    => route('admin.docs.index', ['definition' => $baseName]),
                'snippet' => $fileSearch->snippet($content, $snippetQuery),
            ];
        }

        return $results;
    }

    /**
     * Добавление заголовка к содержимому документа
     * Вставляем метатег <meta name="title"> в начало HTML
     */
    protected function addTitleToContent(string $content, string $title, string $ext): string
    {
        if ($ext === 'html' || $ext === 'htm') {
            // Проверяем, есть ли уже тег <head>
            if (preg_match('/<head\b/i', $content)) {
                // Удаляем старый метатег title, если он есть
                $content = preg_replace('/<meta\s+name=["\']title["\']\s+content=["\']([^"\']*)["\']\s*\/?>/i', '', $content);
                // Вставляем новый метатег в существующий блок <head>
                $content = preg_replace('/(<head\b[^>]*>)/i', '$1' . "\n        <meta name=\"title\" content=\"" . addslashes($title) . "\">", $content);
            } else {
                // Ищем тег <html> или сразу вставляем после <body> или в начало
                if (preg_match('/(<html\b[^>]*>)/i', $content)) {
                    // Удаляем старый метатег title, если он есть
                    $content = preg_replace('/<meta\s+name=["\']title["\']\s+content=["\']([^"\']*)["\']\s*\/?>/i', '', $content);
                    // Вставляем <head> блок с метатегом после <html>
                    $content = preg_replace('/(<html\b[^>]*>)/i', '$1<head><meta name="title" content="' . addslashes($title) . "\"></head>", $content);
                } else {
                    // Если нет тега <html>, создаем базовую структуру HTML с метатегом
                    $content = "<!DOCTYPE html>\n<html>\n<head>\n    <meta name=\"title\" content=\"" . addslashes($title) . "\">\n</head>\n<body>\n" . $content . "\n</body>\n</html>";
                }
            }
        } elseif ($ext === 'md' || $ext === 'markdown') {
            // Для Markdown добавляем YAML front matter с заголовком
            if (!preg_match('/^---\\s*\\n/im', $content)) {
                // Если front matter отсутствует, добавляем
                $content = "---\ntitle: " . addslashes($title) . "\n---\n\n" . $content;
            } else {
                // Если front matter уже есть, обновляем заголовок
                $content = preg_replace('/^(title:\\s*.+)$/m', "title: " . addslashes($title), $content, 1, $count);
                if ($count === 0) {
                    // Если заголовка не было в front matter, добавляем его
                    $content = preg_replace('/^---\\s*\\n/im', "---\ntitle: " . addslashes($title) . "\n", $content, 1);
                }
            }
        }

        return $content;
    }

    /**
     * Извлечение заголовка из содержимого документа
     * Сначала ищем метатег <meta name="title">, затем комментарий с кастомным заголовком,
     * затем тег <title>, затем первый заголовок h1
     */
    protected function extractTitleFromContent(string $content, string $ext): string
    {
        $title = '';

        if ($ext === 'html' || $ext === 'htm') {
            // Ищем метатег title в HTML (имеет максимальный приоритет)
            if (preg_match('/<meta\s+name=["\']title["\']\s+content=["\']([^"\']*)["\']\s*\/?>/i', $content, $matches)) {
                $title = $matches[1];
            }
            // Затем ищем комментарий с кастомным заголовком
            elseif (preg_match('/<!--\s*Custom title:\s*([^>]*)\s*-->/i', $content, $matches)) {
                $title = trim($matches[1]);
            }
            // Затем тег <title>
            elseif (preg_match('/<title>(.*?)<\/title>/i', $content, $matches)) {
                $title = trim(strip_tags($matches[1]));
            }
            // Затем заголовок h1
            elseif (preg_match('/<h1.*?>(.*?)<\/h1>/i', $content, $matches)) {
                $title = trim(strip_tags($matches[1]));
            }
        } elseif ($ext === 'md' || $ext === 'markdown') {
            // Для Markdown ищем YAML front matter или первый заголовок
            if (preg_match('/^---\\s*\\n(.*?)\\n---\\s*\\n/im', $content, $matches)) {
                $yaml = $matches[1];
                if (preg_match('/title:\\s*(.+)/i', $yaml, $yamlMatches)) {
                    $title = trim($yamlMatches[1], '"\'');
                }
            }

            // Если не нашли в YAML, ищем первый заголовок
            if (empty($title)) {
                if (preg_match('/^#\\s+(.+)/m', $content, $matches)) {
                    $title = trim($matches[1]);
                }
            }
        }

        return $title;
    }

    /**
     * Поиск файла definition[.lang].html без учёта регистра.
     */
    private function findDocFile(string $dir, string $base, ?string $lang = null): ?string
    {
        $base = strtolower($base);
        $lang = $lang ? strtolower($lang) : null;

        foreach (File::files($dir) as $file) {
            $name = strtolower($file->getFilename());
            if ($lang) {
                if ($name === "{$base}.{$lang}." . $this->fileExt) {
                    return $file->getPathname();
                }
            } else {
                if (preg_match("/^{$base}(\.[^.]+)?\." . $this->fileExt . "$/", $name)) {
                    return $file->getPathname();
                }
            }
        }
        return null;
    }
}