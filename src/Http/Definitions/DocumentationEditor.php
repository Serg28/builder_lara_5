<?php

namespace Linecore\Cms\Definitions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Linecore\Cms\Fields\Textarea;
use Linecore\Cms\Http\Requests\DocumentationRequest;
use Linecore\Cms\Services\Listing;
use Linecore\Cms\Helpers\Traits\ResolvesDefinitionTitle;

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

        $dir = config('cms.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        $ext = config('cms.documentation.extension', 'html');

        $files = File::glob($dir . '*.' . $ext) ?: [];

        $bases = collect($files)
            ->map(function ($path) {
                $file = basename($path);
                $parts = explode('.', $file);
                if (count($parts) >= 3) {
                    array_pop($parts); // html
                    array_pop($parts); // lang
                } else {
                    array_pop($parts); // html
                }
                return strtolower(implode('.', $parts));
            })
            ->unique()
            ->values();

        return $bases->map(fn($b) => ['name' => $b, 'path' => $b, 'title' => $this->resolveDefinitionTitle($b)]);
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

        $dir = config('cms.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;
        $ext = config('cms.documentation.extension', 'html');

        if ($fileName) {
            $base = strtolower(preg_replace('/\.'.$ext.'$/i', '', $fileName));
            $name = $base;

            $languages = languagesOfSite();
            if ($languages->isEmpty()) $languages = collect([defaultLanguage()]);

            foreach ($languages as $lang) {
                $langFile = $dir . "{$base}.{$lang}." . $ext;
                $contentByLang[$lang] = File::exists($langFile) ? File::get($langFile) : '';
            }
        }

        $fieldClass = config('cms.documentation.editor_default', Textarea::class);
        $field = $fieldClass::make('Содержимое', 'content')->language();
        $field->setValue(['content' => json_encode($contentByLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);

        return view('admin::documentation_editor.form', [
            'tinymceHtml' => $field->getFieldForm($this),
            'name' => $name,
            'isCreating' => !$fileName,
            'definition' => $this,
        ]);
    }

    public function saveEditForm($request): array
    {
        $ext = config('cms.documentation.extension', 'html');

        $data = $request instanceof Request ? $request->all() : (array)$request;

        $validator = Validator::make($data, (new DocumentationRequest())->rules(), (new DocumentationRequest())->messages());

        if ($validator->fails()) {
            return ['success' => false, 'message' => $validator->errors()->first()];
        }

        $nameBase = $data['name'];
        $originalBase = $data['original_name'] ?? null;

        $dir = config('cms.documentation.path_app', resource_path('docs/definitions')) . DIRECTORY_SEPARATOR;

        if ($originalBase !== $nameBase && File::glob($dir . $nameBase . '*.' . $ext)) {
            return ['success' => false, 'message' => "Файл с именем {$nameBase} уже существует."];
        }

        $langs = languagesOfSite()->isNotEmpty() ? languagesOfSite() : collect([defaultLanguage()]);
        $content = $data['content'];

        foreach ($langs as $lang) {
            $body = is_array($content) ? ($content[$lang] ?? '') : $content;
            File::put($dir . "{$nameBase}.{$lang}." . $ext, $body);
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

    // TODO: Implement remove() method.
    /*public function remove(string $definationName) : array
    {

        $this->model()->destroy($id);
        $this->clearCache();

        return $this->returnSuccess();
    }

    private function returnSuccess()
    {
        return [
            'status' => 'success'
        ];
    }*/
}