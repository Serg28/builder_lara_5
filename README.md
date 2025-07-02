Generate a password for admin
```json
   php artisan admin:generatePassword
```
Публикация конфига админпанели
```json
   php artisan vendor:publish --provider='Vis\Builder\BuilderServiceProvider' --tag=builder-cms-config
```