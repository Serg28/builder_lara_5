# Использование ветки laravel-11-12-support

## 🎯 Прямое использование ветки

Вы можете использовать ветку `laravel-11-12-support` напрямую без мержа в основную ветку. Это позволяет:
- Тестировать Laravel 11 & 12 поддержку независимо
- Не затрагивать стабильную версию
- Легко переключаться между версиями

## 📦 Настройка Composer

### Вариант 1: Указание конкретной ветки
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support"
    }
}
```

### Вариант 2: Использование алиаса версии
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support as 4.0.x-dev"
    }
}
```

### Вариант 3: Для разных окружений
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "vis/builder_lara_5": "dev-laravel-11-12-support"
    },
    "require-dev": {
        "vis/builder_lara_5": "dev-version6"
    }
}
```

## 🔄 Команды установки

### Новая установка
```bash
composer require "vis/builder_lara_5:dev-laravel-11-12-support"
```

### Обновление существующей установки
```bash
# Обновить composer.json как показано выше, затем:
composer update vis/builder_lara_5
```

### Принудительное обновление
```bash
composer update vis/builder_lara_5 --with-all-dependencies
```

## 🧪 Тестирование

### Проверка установленной версии
```bash
composer show vis/builder_lara_5
```

### Проверка совместимости
```bash
php vendor/vis/builder_lara_5/check-compatibility.php
```

## 🔀 Переключение между ветками

### На Laravel 11/12 версию
```json
"vis/builder_lara_5": "dev-laravel-11-12-support"
```

### Обратно на стабильную версию
```json
"vis/builder_lara_5": "3.*"
```

### На конкретную ветку
```json
"vis/builder_lara_5": "dev-version6"
```

## ⚠️ Важные моменты

### Стабильность
- `dev-laravel-11-12-support` - разрабатываемая ветка
- Может содержать незавершенные изменения
- Рекомендуется для тестирования и разработки

### Кэширование Composer
```bash
# Очистить кэш при переключении веток
composer clear-cache
composer update vis/builder_lara_5
```

### Блокировка версии
```bash
# Зафиксировать конкретный коммит
composer require "vis/builder_lara_5:dev-laravel-11-12-support#ec23e680"
```

## 📋 Пример полной настройки

```json
{
    "name": "your-project/laravel-app",
    "type": "project",
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Serg28/builder_lara_5"
        }
    ],
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "vis/builder_lara_5": "dev-laravel-11-12-support"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

## 🚀 Быстрый старт

1. **Обновите composer.json**:
   ```json
   "vis/builder_lara_5": "dev-laravel-11-12-support"
   ```

2. **Установите/обновите пакет**:
   ```bash
   composer update vis/builder_lara_5
   ```

3. **Проверьте совместимость**:
   ```bash
   php vendor/vis/builder_lara_5/check-compatibility.php
   ```

4. **Запустите автоматическое обновление**:
   ```bash
   bash vendor/vis/builder_lara_5/upgrade-to-laravel-11-12.sh
   ```

## 🔧 Отладка

### Проблемы с установкой
```bash
composer why-not vis/builder_lara_5 dev-laravel-11-12-support
```

### Просмотр доступных версий
```bash
composer show vis/builder_lara_5 --all
```

### Информация о пакете
```bash
composer info vis/builder_lara_5
```

---

**Преимущества использования ветки:**
- ✅ Независимое тестирование
- ✅ Быстрое переключение версий  
- ✅ Доступ к последним изменениям
- ✅ Возможность отката