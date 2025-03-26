<?php

namespace Vis\Builder\Helpers\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\LazyCollection;

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
     * @param int $page Номер страницы (по умолчанию 1).
     * @param int $chunkSize Количество записей на страницу (по умолчанию 1000).
     * @param callable|null $q Дополнительный обработчик запроса (например, для фильтрации).
     *
     * @return LazyCollection Лениво загружаемая коллекция записей.
     *
     * @throws \Exception Если `$chunkSize` меньше 1.
     *
     * @example
     * // Получение третьей страницы записей по 500 штук
     * $products = Product::lazyPaginatedById(3, 500);
     * foreach ($products as $product) {
     *     echo $product->id . PHP_EOL;
     * }
     *
     * @example
     * // Фильтрация только активных товаров при ленивой пагинации
     * $products = Product::lazyPaginatedById(1, 100, fn($q) => $q->where('is_active', 1));
     * foreach ($products as $product) {
     *     echo $product->title . PHP_EOL;
     * }
     *
     * @example
     * // Построение пагинации в Blade (пример с обычной пагинацией)
     * $page = request()->input('page', 1);
     * $products = Product::lazyPaginatedById($page, 20);
     * @foreach ($products as $product)
     *     <p>{{ $product->title }}</p>
     * @endforeach
     * <a href="{{ url()->current() }}?page={{ $page + 1 }}">Следующая страница</a>
     */
    public function scopeLazyPaginatedById($query, int $page = 1, int $chunkSize = 1000, callable $q = null): LazyCollection
    {
        if ($chunkSize < 1) {
            throw new \Exception('Размер пакета должен быть не менее 1');
        }

        return LazyCollection::make(function () use ($query, $chunkSize, $q, $page) {
            $currentPage = $page - 1; // Laravel использует 1-based индексацию, а offset — 0-based

            do {
                $clone = clone $query;
                $tmpQuery = $q ? $q(clone $query) : clone $query;

                $tmp = $tmpQuery->select('id as tmpId')
                    ->limit($chunkSize)
                    ->offset($chunkSize * $currentPage);

                $results = $clone->joinSub($tmp, 'tmp', fn ($join) => $join->on('tmpId', 'id'))->get();

                foreach ($results as $result) {
                    yield $result;
                }

                $currentPage++;
            } while ($results->count() >= $chunkSize);
        });
    }
}

