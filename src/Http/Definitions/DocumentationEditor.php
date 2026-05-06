<?php

namespace Vis\Builder\Definitions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Vis\Builder\Fields\Textarea;
use Vis\Builder\Http\Requests\DocumentationRequest;
use Vis\Builder\Services\Listing;
use Vis\Builder\Helpers\Traits\ResolvesDefinitionTitle;

class DocumentationEditor extends Resource
{
    use ResolvesDefinitionTitle;

    public $title = 'Редактор документации';
    public $model = null;

    public function model()
    {
        return null;
    }

    public function getList()
    {
        $req = request();
        $edit = $req?->input('edit');
        $create = $req?->boolean('create');

        return ($edit || $create)
            ? $this->renderForm($edit)
            : $this->renderList();
    }

    public function getListing()
    {
        $this->checkPermissions();

        $dir = config('builder.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        $ext = config('builder.documentation.docs_extensions', 'html');

        // Группируем файлы по базовому имени
        $filesByBase = [];
        foreach (File::files($dir) as $file) {
            $filename = $file->getFilename();
            $parts = explode('.', $filename);
            if (count($parts) < 2) continue;

            $base = strtolower($parts[0]);
            $filesByBase[$base][] = $file;
        }

        return collect(array_keys($filesByBase))->map(function($b) use ($dir, $ext, $filesByBase) {
            $title = '';

            // Обрабатываем все языковые версии файла
            foreach ($filesByBase[$b] as $file) {
                // Извлекаем заголовок из содержимого файла (кастомный заголовок имеет максимальный приоритет)
                if (empty($title)) {
                    $content = File::get($file->getPathname());
                    $title = $this->extractTitleFromContent($content, $ext) ?: $this->resolveDefinitionTitle($b) ?: $b;
                }
            }

            return ['name' => $b, 'path' => $b, 'title' => $title];
        });
    }

    private function renderList()
    {
        return view('admin::documentation_editor.list', [
            'docs' => $this->getListing(),
            'definition' => $this,
        ]);
    }

    private function renderForm(?string $fileName = null)
    {
        $this->checkPermissions();

        $contentByLang = [];
        $name = '';
        $customTitle = '';

        $dir = config('builder.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        $ext = config('builder.documentation.docs_extensions', 'html');

        if ($fileName) {
            $base = strtolower(preg_replace('/\.'.$ext.'$/i', '', $fileName));
            $name = $base;

            $languages = languagesOfSite();
            if ($languages->isEmpty()) $languages = collect([defaultLanguage()]);

            foreach ($languages as $lang) {
                $langFile = $dir . "{$base}.{$lang}." . $ext;
                $content = File::exists($langFile) ? File::get($langFile) : '';
                $contentByLang[$lang] = $content;

                // Извлекаем кастомный заголовок из содержимого файла
                $extractedTitle = $this->extractTitleFromContent($content, $ext);
                if (!empty($extractedTitle)) {
                    $customTitle = $extractedTitle;
                    break; // Используем первый найденный заголовок
                }
            }
        }

        $fieldClass = config('builder.documentation.editor_default', Textarea::class);
        $field = $fieldClass::make('Содержимое', 'content')->language();
        $field->setValue(['content' => json_encode($contentByLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        return view('admin::documentation_editor.form', [
            'tinymceHtml' => $field->getFieldForm($this),
            'name' => $name,
            'customTitle' => $customTitle,
            'isCreating' => !$fileName,
            'definition' => $this,
        ]);
    }

    public function saveEditForm($request): array
    {
        $ext = config('builder.documentation.docs_extensions', 'html');

        $data = $request instanceof Request ? $request->all() : (array)$request;

        $validator = Validator::make($data, (new DocumentationRequest())->rules(), (new DocumentationRequest())->messages());

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $nameBase = $data['name'];
        $originalBase = $data['original_name'] ?? null;

        $dir = config('builder.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;

        if ($originalBase !== $nameBase && File::glob($dir . $nameBase . '*.' . $ext)) {
            return ['success' => false, 'message' => "Файл с именем {$nameBase} уже существует."];
        }

        $langs = languagesOfSite()->isNotEmpty() ? languagesOfSite() : collect([defaultLanguage()]);
        $content = $data['content'];

        foreach ($langs as $lang) {
            $body = is_array($content) ? ($content[$lang] ?? '') : $content;

            // Если задан произвольный заголовок, добавляем его в виде мета-тега в начало HTML
            if (!empty($data['custom_title'])) {
                $body = $this->addTitleToContent($body, $data['custom_title'], $ext);
            }
            // Если кастомный заголовок не задан, но есть в содержимом файла, сохраняем его как метатег
            elseif (empty($data['custom_title']) && !empty(trim($body))) {
                $existingTitle = $this->extractTitleFromContent($body, $ext);
                if (!empty($existingTitle)) {
                    $body = $this->addTitleToContent($body, $existingTitle, $ext);
                }
            }
            // Если кастомный заголовок не задан и в содержимом нет заголовка, удаляем старый метатег
            else {
                // Удаляем старый метатег title, если он есть
                if ($ext === 'html' || $ext === 'htm') {
                    $body = preg_replace('/<meta\s+name=["\']title["\']\s+content=["\']([^"\']*)["\']\s*\/?>/i', '', $body);
                }
            }

            $filePath = $dir . "{$nameBase}.{$lang}." . $ext;

            // Ensure directory exists before writing file
            $fileDir = dirname($filePath);
            if (!File::exists($fileDir)) {
                File::makeDirectory($fileDir, 0755, true);
            }

            // Attempt to write the file with error handling
            try {
                File::put($filePath, $body);
            } catch (\ErrorException $e) {
                return [
                    'success' => false,
                    'message' => "Ошибка при записи файла {$filePath}: " . $e->getMessage()
                ];
            }
        }

        if ($originalBase && $originalBase !== $nameBase) {
            foreach ($langs as $lang) {
                $old = $dir . "{$originalBase}.{$lang}." . $ext;
                if (File::exists($old)) File::delete($old);
            }
        }

        return [
            'success' => true,
            'message' => "Файл {$nameBase} успешно сохранён.",
            'redirect' => url()->current() . '?edit=' . urlencode($nameBase),
        ];
    }

    public function saveAddForm($request): array
    {
        $arr = $request instanceof Request
            ? $request->merge(['original_name' => null])->all()
            : array_merge((array)$request, ['original_name' => null]);

        $result = $this->saveEditForm($arr);

        if ($result['success']) {
            $newName = $arr['name'];
            // редирект на форму редактирования нового файла
            $result['redirect'] = url()->current() . '?edit=' . urlencode($newName);
        }

        return $result;
    }

    public function remove($id) : array
    {
        $this->checkPermissions();

        $dir = config('builder.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        $ext = config('builder.documentation.docs_extensions', 'html');

        $files = File::glob($dir . $id . '.*.' . $ext);
        
        if (empty($files)) {
             // Также проверяем файл без языковой метки
             $noLangFile = $dir . $id . '.' . $ext;
             if (File::exists($noLangFile)) {
                 $files[] = $noLangFile;
             }
        }

        if (empty($files)) {
            return ['status' => 'error', 'message' => "Файлы для {$id} не найдены."];
        }

        foreach ($files as $file) {
            File::delete($file);
        }

        $this->clearCache();

        return [
            'status' => 'success', 
            'message' => "Документация \"{$id}\" успешно удалена."
        ];
    }

    public function clearCache()
    {
        if (app()->bound(\Vis\Builder\Interfaces\DocSearchInterface::class)) {
            app(\Vis\Builder\Interfaces\DocSearchInterface::class)->clearCache();
        }
    }

    private function returnSuccess()
    {
        return [
            'status' => 'success'
        ];
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
}
