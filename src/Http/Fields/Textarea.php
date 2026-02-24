<?php

namespace Linecore\Cms\Fields;

class Textarea extends Field
{
    protected int $rows = 3;

    protected ?int $maxRows = null;

    /**
     * Устанавливает количество строк для textarea
     */
    public function rows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    /**
     * Получает количество строк
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    /**
     * Устанавливает максимальное количество строк для авто-расширения
     */
    public function maxRows(int $maxRows): self
    {
        $this->maxRows = $maxRows;

        return $this;
    }

    /**
     * Получает максимальное количество строк
     */
    public function getMaxRows(): ?int
    {
        return $this->maxRows;
    }

    /**
     * Проверяет, установлено ли максимальное количество строк
     */
    public function hasMaxRows(): bool
    {
        return $this->maxRows !== null;
    }
}