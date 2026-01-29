# Руководство по миграции с vis/builder_lara_5 на linecore/linecore-cms

Данное руководство описывает процесс миграции существующего проекта с пакета `vis/builder_lara_5` на новый пакет `linecore/linecore-cms`.

## Содержание

1. [Автоматическая миграция](#автоматическая-миграция)
2. [Ручная миграция](#ручная-миграция)
3. [Проверка после миграции](#проверка-после-миграции)

---

## Автоматическая миграция

### Скрипт для автоматической миграции

Создайте файл `migrate-to-linecore.sh` в корне проекта и выполните его:

```bash
#!/bin/bash

echo "🚀 Начало миграции на Linecore CMS..."

# 1. Замена неймспейсов в PHP файлах
echo "📦 Замена неймспейсов..."
find app -type f -name "*.php" -exec sed -i 's/namespace Vis\\Builder/namespace Linecore\\Cms/g' {} \;
find app -type f -name "*.php" -exec sed -i 's/use Vis\\Builder/use Linecore\\Cms/g' {} \;
find app -type f -name "*.php" -exec sed -i 's/\\Vis\\Builder\\/\\Linecore\\Cms\\/g' {} \;
find config -type f -name "*.php" -exec sed -i 's/Vis\\Builder/Linecore\\Cms/g' {} \;

# 2. Замена путей в blade-шаблонах
echo "🎨 Обновление путей ассетов..."
find resources/views -type f -name "*.blade.php" -exec sed -i 's|/packages/vis/builder|/packages/linecore/cms|g' {} \;

# 3. Обновление конфигурации
echo "⚙️ Обновление конфигурации..."
find config -type f -name "*.php" -exec sed -i "s|config('builder\.|config('cms.|g" {} \;

# 4. Переименование директории конфигов
if [ -d "config/builder" ]; then
    echo "📁 Переименование config/builder -> config/cms..."
    mv config/builder config/cms
fi

# 5. Перемещение публичных ассетов
if [ -d "public/packages/vis/builder" ]; then
    echo "📂 Перемещение ассетов..."
    mkdir -p public/packages/linecore
    mv public/packages/vis/builder public/packages/linecore/cms
fi

# 6. Обновление composer.json
echo "📝 Обновление composer.json..."
sed -i 's/"vis\/builder_lara_5"/"linecore\/linecore-cms"/g' composer.json

# 7. Замена ServiceProvider в config/app.php (для старых версий Laravel)
if [ -f "config/app.php" ]; then
    sed -i 's/Vis\\Builder\\BuilderServiceProvider/Linecore\\Cms\\CmsServiceProvider/g' config/app.php
fi

echo "✅ Миграция завершена!"
echo ""
echo "Следующие шаги:"
echo "1. Выполните: composer update"
echo "2. Очистите кэш: php artisan cache:clear && php artisan config:clear && php artisan view:clear"
echo "3. Протестируйте работу админ-панели"
```

Выполните скрипт:
```bash
chmod +x migrate-to-linecore.sh
./migrate-to-linecore.sh
```

---

## Ручная миграция

Если вы предпочитаете контролировать процесс вручную, выполните следующие шаги:

### Шаг 1: Обновление composer.json

Замените зависимость:

```diff
- "vis/builder_lara_5": "^1.0"
+ "linecore/linecore-cms": "^1.0"
```

### Шаг 2: Замена неймспейсов

Во всех PHP файлах проекта замените:

| Было | Стало |
|------|-------|
| `namespace Vis\Builder` | `namespace Linecore\Cms` |
| `use Vis\Builder` | `use Linecore\Cms` |
| `\Vis\Builder\` | `\Linecore\Cms\` |

**Примеры:**

```php
// Было:
use Vis\Builder\Definitions\Resource;
use Vis\Builder\Fields\{Text, Image, Checkbox};
use Vis\Builder\Services\Actions;

// Стало:
use Linecore\Cms\Definitions\Resource;
use Linecore\Cms\Fields\{Text, Image, Checkbox};
use Linecore\Cms\Services\Actions;
```

### Шаг 3: Обновление конфигурации

1. Переименуйте директорию `config/builder` в `config/cms`
2. Во всех файлах замените:
   - `config('builder.` → `config('cms.`

### Шаг 4: Обновление путей ассетов

1. Переместите `public/packages/vis/builder` в `public/packages/linecore/cms`
2. В blade-шаблонах замените:
   - `/packages/vis/builder` → `/packages/linecore/cms`

### Шаг 5: Обновление ServiceProvider

Если вы регистрируете провайдер вручную в `config/app.php`:

```php
// Было:
Vis\Builder\BuilderServiceProvider::class,

// Стало:
Linecore\Cms\CmsServiceProvider::class,
```

### Шаг 6: Обновление Composer

```bash
composer update
```

### Шаг 7: Очистка кэша

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

### Шаг 8: Переопубликация ассетов (опционально)

```bash
php artisan vendor:publish --provider="Linecore\Cms\CmsServiceProvider" --tag=public --force
```

---

## Проверка после миграции

После выполнения миграции проверьте:

### 1. Авторизация в админ-панели
- Откройте `/admin` и проверьте страницу входа
- Авторизуйтесь и проверьте работу интерфейса

### 2. Работа с ресурсами (Definitions)
- Откройте любой раздел
- Проверьте отображение списка записей
- Создайте новую запись
- Отредактируйте существующую запись
- Удалите тестовую запись

### 3. Древовидные страницы
- Проверьте работу дерева страниц
- Добавьте/отредактируйте страницу

### 4. Загрузка файлов
- Загрузите изображение
- Загрузите файл
- Проверьте корректность отображения

### 5. Переводы
- Проверьте работу мультиязычных полей
- Проверьте работу переводов интерфейса

---

## Часто встречающиеся проблемы

### Ошибка: Class not found

Если возникает ошибка поиска класса:
```bash
composer dump-autoload
```

### Ошибка: View not found

Очистите кэш представлений:
```bash
php artisan view:clear
```

### Ошибка: Config not found

Переопубликуйте конфигурацию:
```bash
php artisan vendor:publish --provider="Linecore\Cms\CmsServiceProvider" --tag=linecore-cms
```

### Стили/скрипты не загружаются

Убедитесь, что ассеты перемещены корректно:
```bash
php artisan vendor:publish --provider="Linecore\Cms\CmsServiceProvider" --tag=public --force
```

---

## Таблица соответствия путей

| Старый путь | Новый путь |
|------------|-----------|
| `vendor/vis/builder_lara_5` | `vendor/linecore/linecore-cms` |
| `config/builder/` | `config/cms/` |
| `public/packages/vis/builder` | `public/packages/linecore/cms` |
| `config('builder.*')` | `config('cms.*')` |

## Таблица соответствия файлов изображений

| Старый файл | Новый файл |
|------------|-----------|
| `img/vis-admin-lock.jpg` | `img/login-background.jpg` |
| `img/logo.png` | `img/linecore-logo.png` |
| `img/logo-w.png` | `img/linecore-logo-white.png` |
| `img/blank_avatar.gif` | `img/default-avatar.gif` |

---

## Таблица соответствия классов

| Старый класс | Новый класс |
|-------------|------------|
| `Vis\Builder\CmsServiceProvider` | `Linecore\Cms\CmsServiceProvider` |
| `Vis\Builder\Definitions\Resource` | `Linecore\Cms\Definitions\Resource` |
| `Vis\Builder\Definitions\BaseTree` | `Linecore\Cms\Definitions\BaseTree` |
| `Vis\Builder\Fields\*` | `Linecore\Cms\Fields\*` |
| `Vis\Builder\Services\Actions` | `Linecore\Cms\Services\Actions` |
| `Vis\Builder\Services\Listing` | `Linecore\Cms\Services\Listing` |
| `Vis\Builder\Services\ButtonBase` | `Linecore\Cms\Services\ButtonBase` |
| `Vis\Builder\User` | `Linecore\Cms\User` |
| `Vis\Builder\Setting` | `Linecore\Cms\Setting` |

---

## Поддержка

При возникновении проблем обращайтесь:
- Email: sales@linecore.com

---

*Linecore CMS © 2024*
