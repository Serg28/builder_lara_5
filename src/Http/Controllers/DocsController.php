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

        foreach (File::files($this->dir) as $file) {
            $filename = $file->getFilename();
            $parts    = explode('.', $filename);
            if (count($parts) < 2) continue;

            $base = strtolower($parts[0]);
            $lang = count($parts) === 3 ? strtolower($parts[1]) : defaultLanguage();

            $content = File::get($file->getPathname());
            if (preg_match('/<h1.*?>(.*?)<\/h1>/i', $content, $matches)) {
                $title = strip_tags($matches[1]);
            } elseif (preg_match('/<h[2-6].*?>(.*?)<\/h[2-6]>/i', $content, $matches)) {
                $title = strip_tags($matches[1]);
            } else {
                //$title = $base;
                $title = $this->resolveDefinitionTitle($base) ?: $base;
            }

            $docs[$base]['definition'] = $base;
            $docs[$base]['title']      = $title;
            $docs[$base]['languages'][] = $lang;
            $docs[$base]['link']       = route('admin.docs.show', [
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

        return  [
            'title'   => $this->resolveDefinitionTitle($definition) ?: $definition,
            'content' => File::get($path),
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

            $results[] = [
                'name'    => $this->resolveDefinitionTitle($baseName) ?: $baseName,
                'link'    => route('admin.docs.index', ['definition' => $baseName]),
                'snippet' => $fileSearch->snippet($content, $snippetQuery),
            ];
        }

        return $results;
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