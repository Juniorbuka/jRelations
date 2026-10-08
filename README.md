# jRelations

**jRelations** — модуль для створення та керування двосторонніми зв’язками між
ресурсами в Evolution CMS. Наприклад, можна пов’язати лікаря з послугою та
локацією, а потім переглядати кожний зв’язок з обох ресурсів.

Модуль додає вкладку зв’язків до редактора ресурсів, дозволяє налаштувати
доступність типів зв’язків за шаблонами та надає дані для виведення у Blade.
Ядро Evolution CMS не змінюється.

## Сумісність

- Evolution CMS `^3.5.7`
- PHP `^8.3`
- Composer

## Встановлення

Після публікації GitHub-репозиторію та реєстрації його в Packagist пакет
встановлюється за назвою `jevo/jrelations`. У терміналі перейдіть до каталогу
`core` вашого сайту та виконайте:

```bash
cd ~/public_html/core
php -d="memory_limit=-1" artisan package:installrequire jevo/jrelations "^1.0"
```

Після встановлення запустіть міграції та очистіть кеш Blade:

```bash
php artisan migrate
php artisan view:clear
```

Перевірте, що Composer встановив пакет у `core/vendor/jevo/jrelations`, а в
менеджері Evolution CMS з’явився модуль **«jRelations — Зв’язки ресурсів»**.

> Перед запуском міграцій перевірте конфігурацію підключення до бази даних.
> Якщо Artisan повідомляє `APPLICATION IN PRODUCTION`, переконайтеся, що це
> потрібне середовище, перш ніж підтверджувати виконання.

## Налаштування

1. Відкрийте модуль **«jRelations — Зв’язки ресурсів»** у менеджері.
2. У блоці **«Шаблони з вкладкою «Зв’язки»»** виберіть шаблони, у ресурсах яких
   буде доступна вкладка.
3. Створіть тип зв’язку: задайте латинський ключ, назву українською та
   англійською і виберіть шаблони, для яких цей тип доступний.
4. Відкрийте ресурс із відповідним шаблоном, знайдіть ресурс для зв’язування,
   додайте його до списку та збережіть.

Для типу зв’язку можна вибрати лише шаблони, увімкнені в загальному блоці
шаблонів. Створений тип не редагується: щоб змінити його ключ, назви або
шаблони, видаліть тип і створіть його заново. Видалення типу видаляє всі
зв’язки цього типу.

Зв’язки двосторонні й зберігаються один раз. Якщо ресурс A пов’язаний із
ресурсом B, цей зв’язок доступний під час редагування обох ресурсів.

## Виведення зв’язків у Blade

Пакет передає в Blade-перегляди змінні:

- `$jRelations` — опубліковані пов’язані ресурси, згруповані за ключем типу;
- `$jRelationsLocale` — мова `uk` або `en`.

Щоб показати **всі зв’язки поточного ресурсу за всіма типами**, додайте
стандартний список у потрібне місце шаблону:

```blade
@include('jRelations::frontend.list', [
    'relations' => $jRelations,
    'locale' => $jRelationsLocale,
])
```

Стандартний шаблон проходить по кожному типу зв’язку та виводить усі пов’язані
ресурси цього типу. Його можна змінити або використати власний шаблон, наприклад:

```blade
@foreach ($jRelations as $relation)
    <section class="related-resources">
        <h2>
            {{ $jRelationsLocale === 'en' && $relation['type']->name_en
                ? $relation['type']->name_en
                : $relation['type']->name_uk }}
        </h2>

        <ul>
            @foreach ($relation['resources'] as $resource)
                <li>
                    <a href="{{ evo()->makeUrl($resource->id) }}">
                        {{ $resource->pagetitle }}
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endforeach
```

Пошук ресурсів у менеджері та отримання пов’язаних ресурсів на сайті побудовані
на моделі **`EvolutionCMS\Models\SiteContent`**. Пакету **DocLister не потрібно**:
jRelations не використовує DocLister і не вимагає його встановлення.

Або отримайте зв’язки безпосередньо через сервіс:

```php
$relations = app(\Jevo\JRelations\JRelationsService::class)
    ->forResource((int) evo()->documentIdentifier);
```

## Оновлення

Із каталогу `core` оновіть пакет:

```bash
composer update jevo/jrelations
php artisan package:discover
php artisan migrate
php artisan view:clear
```

## Видалення

Видаліть `jevo/jrelations` із `core/custom/composer.json` через команду:

```bash
php artisan package:removerequire jevo/jrelations
```

Після цього перевірте таблиці `resource_relation_*` та резервну копію бази.
Видалення пакета не повинно бути приводом видаляти ці таблиці, якщо збережені
зв’язки ще потрібні або можуть знадобитися повторно.

## Ліцензія

GPL-3.0-or-later.

## Публікація стабільної версії 1.0.0

Composer визначає версію пакета за Git-тегом; поле `version` у `composer.json`
навмисно не задається.

1. Створіть публічний GitHub-репозиторій `Juniorbuka/jRelations` і завантажте
   до нього вміст цього каталогу.
2. Створіть стабільний тег `1.0.0` і відправте його разом із гілкою `main`:

   ```bash
   git tag 1.0.0
   git push origin main
   git push origin 1.0.0
   ```

3. Додайте `https://github.com/Juniorbuka/jRelations` на Packagist як новий
   пакет і ввімкніть GitHub auto-update webhook, якщо Packagist його запропонує.
4. Переконайтеся, що Packagist показує стабільний реліз `1.0.0`. Після цього
   команда встановлення з цього README працюватиме на сайтах через Composer.

Додавання до каталогу Evolution CMS `evolution-cms-packages` для команди
`php artisan extras package` є окремим, необов’язковим кроком. Для способу
встановлення через `package:installrequire`, як у jDirectory, достатньо
публічного Git-тегу та доступного Composer-пакета в Packagist.
