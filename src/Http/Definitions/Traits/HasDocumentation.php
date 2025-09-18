<?php

namespace Vis\Builder\Definitions\Traits;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait HasDocumentation
{
    public function getDocumentationUrl(): ?string
    {
        // slug -> нижний регистр, безопасно для любых языков
        $slug = Str::slug(class_basename($this), '-');
        if ($slug === '') {
            return null;
        }

        $locale = adminLang() ?? defaultLanguage(); // текущий язык админки

        // Поиск в папке приложения
        if ($path = $this->findDocPath(resource_path('docs/definitions'), $slug, $locale)) {
            return route('admin.docs.show', [
                'definition' => $slug,
            ]);
        }

        // Поиск в ресурсах пакета
        if ($path = $this->findDocPath(__DIR__ . '/../../resources/docs', $slug, $locale)) {
            return route('admin.docs.show', [
                'definition' => $slug,
            ]);
        }

        return null;
    }

    /**
     * Ищет файл:
     *   slug.{locale}.html
     *   или fallback slug.html
     * без учёта регистра.
     */
    protected function findDocPath(string $directory, string $slug, ?string $locale = null): ?string
    {
        if (!File::isDirectory($directory)) {
            return null;
        }

        $ext = config('builder.documentation.extension', 'html');
        $slug      = strtolower($slug);
        $locale    = $locale ? strtolower($locale) : null;
        $candidates = [];

        // приоритет: slug.{locale}.html, затем slug.html
        if ($locale) {
            $candidates[] = "{$slug}.{$locale}." . $ext;
        }
        $candidates[] = "{$slug}." . $ext;

        foreach (File::files($directory) as $file) {
            $filename = strtolower($file->getFilename());
            foreach ($candidates as $candidate) {
                if ($filename === $candidate) {
                    return $file->getPathname();
                }
            }
        }

        return null;
    }
}