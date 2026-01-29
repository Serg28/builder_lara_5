<?php

namespace Linecore\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentationRequest extends FormRequest
{
    public function authorize()
    {
        return true; // или добавить проверку прав
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'regex:/^[a-z0-9_-]+$/'],
            'content' => ['required'], // массив или строка
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Имя файла обязательно.',
            'name.regex' => 'Имя файла должно содержать только латиницу, цифры, "-", "_".',
            'content.required' => 'Содержимое не может быть пустым.',
        ];
    }

    protected function prepareForValidation()
    {
        $ext = config('cms.documentation.extension', 'html');

        if ($this->has('name')) {
            $this->merge(['name' => strtolower(preg_replace('/\.'.$ext.'$/i', '', $this->input('name')))]);
        }

        if ($this->has('original_name')) {
            $this->merge(['original_name' => strtolower(preg_replace('/\.'.$ext.'$/i', '', $this->input('original_name')))]);
        }
    }
}