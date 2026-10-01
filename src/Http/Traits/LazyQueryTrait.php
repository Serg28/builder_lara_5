<?php

namespace Vis\Builder\Helpers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;
use Illuminate\Pagination\LengthAwarePaginator;

trait LazyQueryTrait
{
    /**
     * Выполняет более быструю ленивую выборку записей из базы данных, используя joinSub.
     * 
     * Это позволяет обрабатывать большие объемы данных без перегрузки памяти,
     * загружая данные небольшими частями (пакетами).
     *
     * https://greghermo.medium.com/a-faster-laravel-lazy-query-method-d08d2587d2d7
     * 
     * @param Builder $query Запрос, к которому применяется scope.
     * @param int $chunkSize Размер пакета (по умолчанию 1000 записей).
     * @param callable|null $q Дополнительная функция, изменяющая запрос.
     * @return LazyCollection Лениво загружаемая коллекция записей.
     * 
     * @throws \Exception Если размер пакета меньше 1.
     * 
     * @example
     * ```php
     * $products = Product::latezyById(500)->each(function ($product) {
     *     // Обработка каждой записи
     *     echo $product->name . "\n";
     * });
     *
     *   User::select(['id','email','name'])
     *  ->latezyById(1000, fn($q) => $q
     *   ->whereBetween('credits', [50,99])
     *   ->where('active', 1)
     *   ->orderBy('credits'))->each(function ($user) {
     *     // Обработка каждой записи
     *     echo $user->name . "\n";
     * });
     * ```
     */
    public function scopeLatezyById(Builder $query, int $chunkSize = 1000, callable $q = null): LazyCollection
    {
        if ($chunkSize < 1) {
            throw new \Exception('Размер пакета должен быть не менее 1');
        }

        return LazyCollection::make(function () use ($query, $chunkSize, $q) {
            $page = 0;

            do {
                $clone = clone $query;

                // Если передана дополнительная функция изменения запроса, применяем её
                $tmpQuery = $q ? $q(clone $query) : clone $query;

                // Формируем подзапрос для постраничного получения идентификаторов
                $tmp = $tmpQuery->select('id as tmpId')->limit($chunkSize)->offset($chunkSize * $page);
                
                // Соединяем основной запрос с подзапросом по id
                $results = $clone->joinSub($tmp, 'tmp', fn ($join) => $join->on('tmpId', 'id'))->get();

                foreach ($results as $result) {
                    yield $result;
                }

                $page++;
            } while ($results->count() >= $chunkSize);
        });
    }

    /**
     * Выполняет ленивую загрузку записей из базы данных, используя разбиение на страницы.
     * Работает аналогично `paginate()`, но возвращает `LazyCollection`, что позволяет загружать данные постепенно.
     *
     * @param Builder $query Запрос, к которому применяется метод.
     * @param int $chunkSize Количество записей на страницу (по умолчанию 1000).
     * @param callable|null $q Дополнительный обработчик запроса (например, для фильтрации).
     *
     * @return LazyCollection Лениво загружаемая коллекция записей.
     *
     * @throws \Exception Если `$chunkSize` меньше 1.
     *
     * @example
     * // Фильтрация только активных товаров при ленивой пагинации
     * $products = Product::lazyPaginatedById(100, fn($q) => $q->where('is_active', 1));
     * foreach ($products as $product) {
     *     echo $product->title . PHP_EOL;
     * }
     *
     * @example
     * // Построение пагинации в Blade (пример с обычной пагинацией)
     * $products = Product::lazyPaginatedById(20);
     * @foreach ($products as $product)
     *     <p>{{ $product->title }}</p>
     * @endforeach
     * $products->links()
     */
    public function scopeLazyPaginatedById($query, int $perPage = 15, callable $q = null)
    {
        if ($perPage < 1) {
            throw new \Exception('Размер пакета должен быть не менее 1');
        }

        // Определяем текущую страницу из запроса
        $page = max(1, (int) request()->input('page', 1));

        // Клонируем запрос, чтобы избежать изменений в оригинале
        $baseQuery = clone $query;

        // Применяем переданный обработчик запроса ($q), если он есть
        if ($q) {
            $baseQuery = $q($baseQuery);
        }

        // Получаем общее количество записей (учитывая условия)
        $total = $baseQuery->count();
        $lastPage = (int) ceil($total / $perPage);

        // Ограничиваем номер страницы
        $page = max(1, min($page, $lastPage));

        // Ленивый генератор записей
        $lazyCollection = LazyCollection::make(function () use ($baseQuery, $perPage, $page) {
            $tmpQuery = clone $baseQuery;
            $tmpQuery = $tmpQuery->select('id as tmpId')
                ->limit($perPage)
                ->offset($perPage * ($page - 1));

            $results = $baseQuery->joinSub($tmpQuery, 'tmp', fn ($join) => $join->on('tmpId', 'id'))->get();

            foreach ($results as $result) {
                yield $result;
            }
        });

        // Создаем объект стандартной пагинации (LengthAwarePaginator)
        return new LengthAwarePaginator(
            $lazyCollection->values(), // Данные для текущей страницы
            $total, // Общее количество записей
            $perPage, // Количество записей на странице
            $page, // Текущая страница
            ['path' => request()->url(), 'query' => request()->query()] // Добавляем параметры в URL
        );
    }
}
