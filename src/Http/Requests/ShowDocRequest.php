<?php

namespace Linecore\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShowDocRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Подготавливаем данные для валидации.
     */
    protected function prepareForValidation(): void
    {
        $definition = $this->route('definition') ?? null;
        if ($definition) {
            $this->merge(['definition' => $definition]);
        }
    }

    /**
     * Правила валидации.
     */
    public function rules(): array
    {
        return [
            'definition' => ['sometimes', 'string', 'regex:/^[A-Za-z0-9_]+$/'],
        ];
    }

    /**
     * Возвращаем параметр после валидации.
     */
    public function validatedDefinition(): string
    {
        return $this->validated()['definition'] ?? '';
    }
}