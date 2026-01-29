<?php

/**
 * Linecore CMS - Content Management System for Laravel
 *
 * @package     Linecore\Cms
 * @author      Linecore Team <sales@linecore.com>
 * @copyright   2024 Linecore
 * @license     Proprietary
 */

namespace Linecore\Cms\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Linecore\Cms\Definitions\DocumentationEditor;

class MakeDocCommand extends Command
{
    protected $signature = 'admin:doc {definition : Имя Definition (любые символы/языки)}';
    protected $description = 'Создать HTML-файл документации для Definition';

    public function handle(): int
    {
        $editor = new DocumentationEditor();

        // нормализуем имя через метод редактора
        $rawName = $this->argument('definition');
        $fileName = $editor->normalize($rawName);
        $ext = config('cms.documentation.extension', 'html');

        if (!$fileName) {
            $this->error('Некорректное имя: не удалось сформировать имя файла.');
            return self::FAILURE;
        }

        // путь к документации из конфига
        $dir = config('cms.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        File::ensureDirectoryExists($dir);

        // языки
        $langs = languagesOfSite()->isNotEmpty() ? languagesOfSite() : collect([defaultLanguage()]);

        $created = false;
        foreach ($langs as $lang) {
            $path = $dir . "{$fileName}.{$lang}." . $ext;
            if (File::exists($path)) {
                $this->warn("Файл [{$fileName}.{$lang}.]" . $ext . " уже существует, пропускаем.");
                continue;
            }

            File::put(
                $path,
                "<h1>Документация для {$rawName} ({$lang})</h1>\n<p>Опишите здесь ваш Definition...</p>"
            );

            $this->info("Файл [{$fileName}.{$lang}.]" . $ext . " создан: {$path}");
            $created = true;
        }

        if (!$created) {
            $this->warn('Ни один файл не был создан: все файлы уже существуют.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}