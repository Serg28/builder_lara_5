<?php

namespace Linecore\Cms\Interfaces;

interface DocSearchInterface
{
    /**
     * Поиск текста в файлах
     *
     * @param string $query     Что ищем
     * @param string $path      Каталог для обхода
     * @param string $extension Расширение файлов (например, 'html')
     * @return array            Массив результатов [['file' => ..., 'matches' => [...]], ...]
     */
    public function search(string $query, string $path, string $extension = 'html'): array;

    /**
     * Создание сниппета (короткого отрывка) из текста с подсвечиванием поискового запроса
     * 
     * @param string $text
     * @param string $q
     * @param int $len
     * @return string
     */
    public function snippet(string $text, string $q, int $len = 150): string;
}