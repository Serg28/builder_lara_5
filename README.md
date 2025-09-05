Generate a password for admin
```json
   php artisan admin:generatePassword
```
Публикация конфига админпанели
```json
   php artisan vendor:publish --provider='Vis\Builder\BuilderServiceProvider' --tag=builder-cms-config
```


# Linecore Builder CMS - Руководство разработчика

## Обзор

Linecore Builder - это мощная CMS-система для Laravel, которая предоставляет готовую админ-панель с полным набором CRUD-операций, системой древовидных страниц, мультиязычностью и расширенными возможностями управления контентом.

## Архитектура системы

### Основные компоненты

#### 1. Definitions (Определения ресурсов)
- **Назначение**: Определяют структуру и поведение таблиц в админ-панели
- **Базовый класс**: `Vis\Builder\Definitions\Resource`
- **Расположение**: `app/Cms/Definitions/`

#### 2. Fields (Поля)
- **Назначение**: Типы полей для форм с различным поведением
- **Базовый класс**: `Vis\Builder\Fields\Field`
- **Расположение**: `app/Cms/Fields/` (кастомные), `vendor/vis/builder_lara_5/src/Http/Fields/` (встроенные)

#### 3. Tree (Древовидные страницы)
- **Назначение**: Управление иерархическими страницами сайта
- **Базовый класс**: `Vis\Builder\Definitions\BaseTree`
- **Расположение**: `app/Cms/Tree/`

#### 4. Services (Сервисы)
- **Назначение**: Кастомизация логики листинга и действий
- **Расположение**: `app/Cms/Services/`

#### 5. Buttons (Кнопки)
- **Назначение**: Дополнительные кнопки в админ-панели
- **Базовый класс**: `Vis\Builder\Services\ButtonBase`
- **Расположение**: `app/Cms/Buttons/`

#### 6. Cards (Карточки)
- **Назначение**: Виджеты для дашборда
- **Базовый класс**: `Vis\Builder\Services\Value`
- **Расположение**: `app/Cms/Cards/`

## Основные возможности

### Мультиязычность
- Автоматический перевод полей с флагом `->language()`
- Поддержка множественных языков через `languagesOfSite()`
- Кеширование переводов

### Валидация
- Встроенная валидация через `->rules()`
- Поддержка уникальности с игнорированием текущей записи
- Кастомные правила валидации

### Фильтрация и сортировка
- Автоматическая фильтрация через `->filter()`
- Сортировка через `->sortable()`
- Настройка порядка сортировки по умолчанию

### Отношения

- **Foreign** - для `belongsTo`/`hasOne` связей
- **ManyToMany** - для `belongsToMany` связей
- **hasMany** - поле `Definition` с методом `->hasMany()`
```php
use Vis\Builder\Fields\Definition;

Definition::make('Наборы ключей')
    ->hasMany('paymentKeys', PaymentMethodKeys::class)
    ->comment('Управление ключами связанными с этим ресурсом'),

// Примеры из реального кода:
OrderProductsField::make('Товары')
    ->hasMany('products', OrderProducts::class),
    
PaymentDefinition::make('Платежи')
    ->hasMany('payments', OrderPayments::class),
```
- **Options** - для настройки выбора связанных данных

```php
use Vis\Builder\Fields\Relations\Options;

// Основное использование
Foreign::make('Метод оплаты', 'pay_method_id')
    ->options((new Options('payMethod'))->isJson())
    ->filter('foreign'),
    
// Для выбора по конкретному полю
ForeignAjaxManager::make('Менеджер', 'manager_id')
    ->options((new Options('manager'))->keyField('first_name'))
    ->filter(),
    
// С сортировкой
Foreign::make('Причина отмены', 'cancel_reason_id')
    ->options((new Options('cancelReason'))
        ->isJson()
        ->orderBy('priority', 'asc'))
    ->filter(),
```

## Создание Definitions

### Базовый шаблон

```php
<?php

namespace App\Cms\Definitions;

use Vis\Builder\Definitions\Resource;
use Vis\Builder\Fields\{Text, Image, Checkbox, Select};
use Vis\Builder\Services\Actions;

class ExampleResource extends Resource
{
    public $model = ExampleModel::class;
    public string $title = 'Название ресурса';
    
    protected $orderBy = 'id desc';
    protected $perPage = [20, 100, 1000];
    
    public function fields(): array
    {
        return [
            'Основная информация' => [
                Text::make('Название', 'title')
                    ->language()
                    ->filter()
                    ->sortable()
                    ->rules(['required', 'max:255']),
                    
                Image::make('Изображение', 'image')
                    ->rules(['image', 'max:2048']),
                    
                Checkbox::make('Активно', 'is_active')
                    ->filter()
                    ->sortable(),
            ],
            
            'Дополнительно' => [
                Select::make('Статус', 'status')
                    ->options([
                        'draft' => 'Черновик',
                        'published' => 'Опубликовано',
                        'archived' => 'В архиве'
                    ])
                    ->filter()
                    ->sortable(),
            ],
        ];
    }
    
    public function actions()
    {
        return Actions::make()
            ->insert()
            ->update()
            ->clone()
            ->revisions()
            ->delete();
    }
    
    public function buttons()
    {
        return [
            CustomButton::class,
        ];
    }
}
```

### Группировка полей

Поля можно группировать по логическим блокам для лучшей организации:

```php
public function fields(): array
{
    return [
        'Основное' => [
            // Основные поля
        ],
        'SEO' => [
            // SEO-поля
        ],
        'Медиа' => [
            // Поля для медиа-контента
        ],
        'Настройки' => [
            // Настройки и флаги
        ],
    ];
}
```

## Типы полей

### Встроенные поля

#### Text
```php
Text::make('Название', 'title')
    ->language()           // Мультиязычное поле
    ->filter()             // Включить фильтрацию
    ->sortable()           // Включить сортировку
    ->rules(['required'])  // Правила валидации
    ->className('col-md-6') // CSS классы
    ->comment('Подсказка')  // Комментарий
    ->transliteration('slug', true) // Автотранслитерация
```

#### Image
```php
Image::make('Фото', 'picture')
    ->rules(['image', 'max:2048'])
    ->resize(800, 600)    // Изменение размера
    ->watermark()          // Водяной знак
```

#### Select
```php
Select::make('Категория', 'category_id')
    ->options([
        '1' => 'Категория 1',
        '2' => 'Категория 2',
    ])
    ->filter()
    ->sortable();
```

#### Foreign (Связанные поля)
```php
Foreign::make('Категория', 'category_id')
    ->options((new Options('category'))->isJson())
    ->filter()
    ->sortable();
```

### Кастомные поля

#### Создание кастомного поля

```php
<?php

namespace App\Cms\Fields;

use Vis\Builder\Fields\Field;

class CustomField extends Field
{
    protected $customOption = 'default';
    
    public function customOption($value)
    {
        $this->customOption = $value;
        return $this;
    }
    
    public function getFieldForm($definition)
    {
        return view('cms.fields.custom_field', [
            'field' => $this,
            'definition' => $definition,
            'value' => $this->getValue(),
        ])->render();
    }
    
    public function getValueForList($definition)
    {
        // Логика отображения в списке
        return Str::limit($this->getValue(), 50);
    }
}
```

#### Использование кастомного поля

```php
CustomField::make('Кастомное поле', 'custom_field')
    ->customOption('special_value')
    ->filter()
    ->sortable();
```

## Древовидные страницы (Tree)

### Структура Tree

```
app/Cms/Tree/
├── Tree.php                    # Главный файл с шаблонами
└── Templates/                  # Шаблоны страниц
    ├── Article.php            # Шаблон статьи
    ├── News.php               # Шаблон новости
    ├── Main.php               # Главная страница
    └── ...
```

### Главный файл Tree

```php
<?php

namespace App\Cms\Tree;

use App\Cms\Tree\Templates\{Article, News, Main};
use Vis\Builder\Definitions\BaseTree;

class Tree extends BaseTree
{
    public function templates()
    {
        return [
            'main' => Main::class,
            'article' => Article::class,
            'news' => News::class,
        ];
    }
}
```

### Шаблон страницы

```php
<?php

namespace App\Cms\Tree\Templates;

use Vis\Builder\Definitions\ResourceTree;
use Vis\Builder\Fields\{Text, Image, Checkbox, Tinymce};

class Article extends ResourceTree
{
    public $action = 'ArticleController@index';
    protected $titleDefinition = 'Статья';
    
    public function fields()
    {
        return [
            'Основное' => [
                Text::make('Заголовок', 'title')->language(),
                Text::make('Slug', 'slug')->rules(['required', 'unique:tb_tree']),
                Tinymce::make('Содержание', 'content')->language(),
                Image::make('Изображение', 'image'),
                Checkbox::make('Активно', 'is_active'),
            ],
            'SEO' => [
                Text::make('Meta Title', 'meta_title')->language(),
                Text::make('Meta Description', 'meta_description')->language(),
            ],
        ];
    }
}
```

## Сервисы

### Listing Service

```php
<?php

namespace App\Cms\Services;

use Vis\Builder\Services\Listing;

class CustomListing extends Listing
{
    public function getQuery()
    {
        $query = parent::getQuery();
        
        // Кастомная логика
        if (request('special_filter')) {
            $query->where('special_field', request('special_filter'));
        }
        
        return $query;
    }
    
    public function getPerPage()
    {
        return 50; // Кастомное количество на странице
    }
}
```

### Actions Service

```php
<?php

namespace App\Cms\Services;

use Vis\Builder\Services\Actions;

class CustomActions extends Actions
{
    public function insert()
    {
        $this->insert = true;
        $this->insertTitle = 'Создать новый элемент';
        $this->insertIcon = 'plus';
        
        return $this;
    }
    
    public function customAction()
    {
        $this->customAction = true;
        $this->customActionTitle = 'Кастомное действие';
        $this->customActionIcon = 'star';
        
        return $this;
    }
}
```

## Кнопки

### Создание кастомной кнопки

```php
<?php

namespace App\Cms\Buttons;

use Illuminate\Contracts\View\View;
use Vis\Builder\Interfaces\Button;
use Vis\Builder\Services\ButtonBase;

class CustomButton extends ButtonBase implements Button
{
    public function show(): View
    {
        $button = [
            'link' => route('custom.action'),
            'ajax' => true,
            'icon' => 'star',
            'caption' => 'Кастомное действие',
            'id' => 'custom-button',
            'massage_start' => 'Выполняется...',
            'massage_end' => 'Готово!',
        ];
        
        return view('admin::tb.button', compact('button'));
    }
}
```

### Использование кнопки в Definition

```php
public function buttons()
{
    return [
        CustomButton::class,
        AnotherButton::class,
    ];
}
```

## Карточки для дашборда

### Создание карточки

```php
<?php

namespace App\Cms\Cards;

use Vis\Builder\Services\Value;
use App\Models\Order;

class OrdersCount extends Value
{
    public $title = 'Количество заказов';
    
    public function calculate()
    {
        return $this->count(Order::class);
    }
}

class OrdersSum extends Value
{
    public $title = 'Сумма заказов';
    
    public function calculate()
    {
        return $this->sum(Order::class, 'total_amount');
    }
}
```

### Использование карточек в Definition

```php
public function cards()
{
    return [
        OrdersCount::class,
        OrdersSum::class,
    ];
}
```

## Мультиязычность

### Настройка языков

```php
// config/builder/translations/cms/languages.php
return [
    'uk' => 'Українська',
    'ru' => 'Русский',
    'en' => 'English',
];
```

### Мультиязычные поля

```php
Text::make('Название', 'title')
    ->language()           // Мультиязычное поле
    ->autoTranslate(true); // Автоперевод
```

### Работа с переводами

```php
// Получение языка по умолчанию
$defaultLang = defaultLanguage();

// Список языков сайта
$languages = languagesOfSite();

// Перевод для CMS
$translated = __cms('key');
```

## Валидация

### Правила валидации

```php
Text::make('Email', 'email')
    ->rules([
        'required',
        'email',
        Rule::unique('users')->ignore(request('id')),
    ]);
```

### Кастомные правила

```php
Text::make('Телефон', 'phone')
    ->rules([
        'required',
        'regex:/^\+?[0-9\s\-\(\)]+$/',
    ])
    ->comment('Формат: +380501234567');
```

## Фильтрация и сортировка

### Фильтры

```php
Text::make('Название', 'title')
    ->filter()             // Включить фильтрацию
    ->filterScope('title'); // Кастомный scope для фильтрации
```

### Сортировка

```php
Text::make('Название', 'title')
    ->sortable();          // Включить сортировку

// Настройка порядка сортировки по умолчанию
protected $orderBy = 'title asc';
```

## Отношения

### HasOne

```php
Text::make('SEO Title', 'seo_title')
    ->hasOne('seo')        // Связь hasOne
    ->language();          // Мультиязычное поле
```

### HasMany

```php
Definition::make('Комментарии')
    ->morphMany('comments', Comments::class);
```

### ManyToMany

```php
ManyToMany::make('Теги')
    ->options((new Options('tags'))->keyField('name'))
    ->onlyForm();
```

## Кеширование

### Настройки

```php
// Получение настройки с кешированием
$setting = setting('site_name');

// Настройка с кешированием для почты
$mailSettings = settingForMail('admin_emails');
```

### Кеширование ресурсов

```php
protected $cacheTag = 'products';

public function getCacheTag()
{
    return $this->cacheTag;
}
```

## Роуты

### Автоматические роуты

Пакет автоматически регистрирует роуты:

- `/admin` - главная страница админки
- `/admin/{resource}` - CRUD операции для ресурса
- `/admin/tree` - управление древовидными страницами
- `/admin/settings` - настройки
- `/admin/translations` - переводы

### Кастомные роуты

```php
// routes/admin.php
Route::get('/custom-action', [CustomController::class, 'action'])
    ->name('custom.action');
```

## Шаблоны

### Пространство имен

Пакет использует шаблоны в пространстве имен `admin::`:

- `admin::table` - таблица списка
- `admin::form` - форма создания/редактирования
- `admin::layouts.default` - основной макет
- `admin::partials.navigation` - навигация

### Кастомные шаблоны

```php
// Создание кастомного шаблона для поля
public function getFieldForm($definition)
{
    return view('cms.fields.custom_field', [
        'field' => $this,
        'definition' => $definition,
    ])->render();
}
```

## Конфигурация

### Публикация конфигов

```bash
# Основные конфиги
php artisan vendor:publish --provider='Vis\Builder\BuilderServiceProvider' --tag=builder

# Конфиг CMS
php artisan vendor:publish --provider='Vis\Builder\BuilderServiceProvider' --tag=builder-cms-config

# Публичные ассеты
php artisan vendor:publish --provider='Vis\Builder\BuilderServiceProvider' --tag=public
```

### Генерация пароля для админпанели
```json
   php artisan admin:generatePassword
```

### Основные настройки

```php
// config/builder/cms.php
return [
    'admin_prefix' => env('CMS_ADMIN_PREFIX', 'admin'),
    'login_path' => env('CMS_LOGIN_PATH', 'login'),
];
```

## Полезные хелперы

### Основные функции

```php
// Языки
defaultLanguage()           // Язык по умолчанию
languagesOfSite()          // Список языков сайта

// Настройки
setting($slug)             // Получение настройки
settingForMail($value)     // Настройка для почты

// Форматирование
filesize_format($bytes)    // Форматирование размера файла

// CMS
__cms($key)               // Перевод для CMS
```

## Лучшие практики

### 1. Структура кода
- Следуйте существующим соглашениям проекта
- Группируйте поля логически
- Используйте описательные названия

### 2. Производительность
- Кешируйте настройки и переводы
- Используйте eager loading для отношений
- Оптимизируйте запросы в Services

### 3. Безопасность
- Всегда валидируйте данные
- Используйте политики авторизации
- Проверяйте права доступа

### 4. Тестирование
- Создавайте тесты для кастомной логики
- Тестируйте Services и Buttons
- Проверяйте валидацию

### 5. Мультиязычность
- Используйте флаг `->language()` для переводимых полей
- Настройте автоперевод где необходимо
- Кешируйте переводы

## Роуты

Пакет автоматически регистрирует роуты для админ-панели:
- `/admin` - главная страница админки
- `/admin/{resource}` - CRUD операции для ресурса
- `/admin/tree` - управление древовидными страницами

### Проверка роутов
```bash
php artisan route:list --name=admin
```

## Шаблоны

Пакет использует свои шаблоны в пространстве имен `admin::`:
- `admin::table` - таблица списка
- `admin::form` - форма создания/редактирования
- `admin::layouts.default` - основной макет

## Важные свойства Definition

### Основные свойства:
```php
class Orders extends Resource
{
    public $model = Order::class;              // Модель Laravel
    public string $title = 'Заказы';      // Название в админке
    protected $orderBy = 'id desc';            // Сортировка по умолчанию
    protected $isSortable = true;              // Ручная сортировка
    protected $relations = [                   // Предзагрузка связей
        'paymentstatus', 'status', 'delivery'
    ];
}
```

### Методы кастомизации:
```php
// Кастомная сортировка списка
public function getFilterScope($collection)
{
    return $collection->whereIn('is_quick', [1, 0]);
}

// Кастомные действия
public function actions()
{
    return Actions::make()->insert()->update()->delete()->clone();
}

// Кастомные кнопки
public function buttons(): array
{
    return [
        Export::class,
    ];
}

// Кастомный список
public function getList()
{
    $list = new ListingOrders($this);
    $listingRecords = $list->body();
    return view('cms.fields.ordertable', compact('list', 'listingRecords'));
}
```

## Полезные методы полей

### Основные методы:
```php
// Основные методы
->filter()                    // Добавляет фильтрацию
->sortable()                  // Добавляет сортировку
->rules(['required'])          // Правила валидации
->default(значение)           // Значение по умолчанию
->comment('Комментарий')      // Подсказка
->language()                  // Мультиязычность

// Отображение
->onlyForm()                  // Только в форме
->onlyTable()                 // Только в таблице
->className('col-md-6')       // CSS классы

// Специальные
->nullable('Выберите')      // Пустое значение
->action()                    // Действие JavaScript
->saveOnChange()              // Автосохранение
```

### Методы для файлов:
```php
File::make('Маркування PDF', 'pdf_mark')
    ->uploadPath('pdf_marks/')     // Путь загрузки
    ->accept('.pdf')               // Типы файлов
    ->noFileSelection()            // Отключить выбор
```

### Комплексная настройка сортировки:
```php
// Поиск по номеру заказа (цифры = ID, текст = NUM)
public function getFilter()
{
    $filter = session($this->getSessionKeyFilter());
    
    if ($filter && isset($filter['filter']['num']) && is_numeric($filter['filter']['num'])) {
        $filter['filter']['id'] = $filter['filter']['num'];
        unset($filter['filter']['num']);
    }
    
    return $filter;
}
```

### Подсказки в JSON полях:
```php
Json::make('Параметры подключения', 'credentials')
    ->comment('Примеры JSON:<br>'
        . '<strong>LiqPay:</strong> {"public_key": "sandbox_i12345", "private_key": "xyz123"}<br>'
        . '<strong>MonoBank:</strong> {"token": "pk_test_abcdef", "webhook_url": "https://site.com/webhook"}')
```


## Практические примеры

### Кастомная логика для полей:
```php
// Кондиционное отображение
Select::make('Посылку забирает', 'receiver')
    ->options([
        'user' => 'Покупатель',
        'other' => 'Другой человек',
    ])
    ->onlyForm()
    ->action(), // Активирует JS-логику

// Поля, которые появляются по условию
Text::make('Имя', 'receiver_first_name')
    ->onlyForm()
    ->className('other'), // Появится при выборе "other"
```


## Примеры использования

### Полный Definition

```php
<?php

namespace App\Cms\Definitions;

use App\Models\Product;
use Vis\Builder\Definitions\Resource;
use Vis\Builder\Fields\{Text, Image, Checkbox, Select, Number, Tinymce};
use Vis\Builder\Services\Actions;

class Products extends Resource
{
    public $model = Product::class;
    public string $title = 'Товары';
    protected $orderBy = 'id desc';
    protected $perPage = [20, 100, 500];
    
    public function fields(): array
    {
        return [
            'Основное' => [
                Text::make('Код товара', 'code')
                    ->filter()
                    ->sortable()
                    ->rules(['required', 'unique:products']),
                    
                Text::make('Название', 'title')
                    ->language()
                    ->filter()
                    ->sortable()
                    ->transliteration('slug', true)
                    ->rules(['required', 'max:255']),
                    
                Text::make('Slug', 'slug')
                    ->filter()
                    ->sortable()
                    ->rules(['required', 'unique:products'])
                    ->comment('URL товара'),
                    
                Select::make('Категория', 'category_id')
                    ->options((new Options('category'))->isJson())
                    ->filter()
                    ->sortable()
                    ->rules(['required']),
                    
                Number::make('Цена', 'price')
                    ->filter()
                    ->sortable()
                    ->rules(['required', 'numeric', 'min:0']),
                    
                Checkbox::make('Активно', 'is_active')
                    ->filter()
                    ->sortable(),
            ],
            
            'Описание' => [
                Tinymce::make('Описание', 'description')
                    ->language()
                    ->onlyForm(),
                    
                Tinymce::make('Короткое описание', 'short_description')
                    ->language()
                    ->onlyForm(),
            ],
            
            'Медиа' => [
                Image::make('Главное фото', 'main_image')
                    ->rules(['image', 'max:2048']),
                    
                MultiImage::make('Дополнительные фото', 'gallery')
                    ->rules(['array', 'max:10']),
            ],
        ];
    }
    
    public function actions()
    {
        return Actions::make()
            ->insert()
            ->update()
            ->clone()
            ->revisions()
            ->delete();
    }
    
    public function buttons()
    {
        return [
            ExportProductsButton::class,
            ImportProductsButton::class,
        ];
    }
    
    public function cards()
    {
        return [
            ProductsCount::class,
            ActiveProductsCount::class,
            TotalProductsValue::class,
        ];
    }
}
```
