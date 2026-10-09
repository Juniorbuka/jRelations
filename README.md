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

У терміналі перейдіть до каталогу
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

Пакет використовує три таблиці: типи зв’язків, зв’язки між ресурсами та
прив’язки типів до шаблонів. Міграції пакета зберігаються в кореневій папці
`migrations/` і створюють лише ці три таблиці.

## Налаштування

1. Відкрийте модуль **«jRelations — Зв’язки ресурсів»** у менеджері.
2. Створіть тип зв’язку: задайте латинський ключ, назви українською та
   англійською і виберіть шаблони, у ресурсах яких цей тип буде доступний.
3. Відкрийте ресурс із відповідним шаблоном, знайдіть ресурс для зв’язування,
   додайте його до списку та збережіть.

Вкладка «Зв’язки» відображається на шаблонах, призначених хоча б одному типу.
Створений тип не редагується: щоб змінити його ключ, назви або шаблони,
видаліть тип і створіть його заново. Видалення типу видаляє всі зв’язки цього
типу.

Зв’язки двосторонні й зберігаються один раз. Якщо ресурс A пов’язаний із
ресурсом B, цей зв’язок доступний під час редагування обох ресурсів.

## Виведення зв’язків у Blade

Пакет передає в Blade-перегляди змінні:

- `$jRelations` — опубліковані пов’язані ресурси, згруповані за ключем типу
  (`slug`). Назви типів, які використовуються для відображення в адмінці, у
  дані для фронтенду не входять.

Щоб отримати ресурси конкретного типу, звертайтеся до масиву за його ключем.
Наприклад, для типу з ключем `doctor`:

```blade
@foreach ($jRelations['doctor'] ?? [] as $doctor)
    <a href="{{ evo()->makeUrl($doctor->id) }}">{{ $doctor->pagetitle }}</a>
@endforeach
```

Кожний `$doctor` — екземпляр `EvolutionCMS\Models\SiteContent`: окрім полів
ресурсу, доступні й його TV-поля. TV-поля можна отримати через `$doctor->tv`:

```blade
@foreach ($jRelations['doctor'] ?? [] as $doctor)
    @php($doctorTvs = $doctor->tv->keyBy('name'))
    <article>
        <a href="{{ evo()->makeUrl($doctor->id) }}">{{ $doctor->pagetitle }}</a>
        <p>{{ $doctor->longtitle }}</p>
        @if ($doctorTvs->has('specialization'))
            <p>{{ $doctorTvs->get('specialization')['value'] }}</p>
        @endif
    </article>
@endforeach
```

`$doctor->tv` — колекція TV у форматі `name`/`value`. Замініть
`specialization` на назву TV-поля, створеного у вашому Evolution CMS. Щоб
вивести зв’язки за всіма ключами, можна підключити стандартний список:

```blade
@include('jRelations::frontend.list', ['relations' => $jRelations])
```

### Використання в контролері сторінки

Впровадьте `JRelationsService` у контролер і викличте зв’язки за ID ресурсу та
ключем типу. Отриману колекцію пов’язаних ресурсів передайте в Blade-перегляд:

```php
use Jevo\JRelations\JRelationsService;

public function show(int $id, JRelationsService $relations)
{
    return view('pages.department', [
        'doctors' => $relations->forResourceType($id, 'doctor'),
    ]);
}
```

У відповідному шаблоні `$doctors` містить моделі `SiteContent` із TV-полями,
тому для читання додаткового TV застосовується той самий підхід:

```blade
@foreach ($doctors as $doctor)
    @php($doctorTvs = $doctor->tv->keyBy('name'))
    <a href="{{ evo()->makeUrl($doctor->id) }}">{{ $doctor->pagetitle }}</a>
    <span>{{ $doctorTvs->get('specialization')['value'] ?? '' }}</span>
@endforeach
```

Для зв’язків за всіма типами використовуйте
`$relations->forResource($id)`. Обидва методи повертають лише опубліковані
пов’язані ресурси.

Пошук ресурсів у менеджері та отримання пов’язаних ресурсів на сайті побудовані
на моделі **`EvolutionCMS\Models\SiteContent`**. Пакету **DocLister не потрібно**:
jRelations не використовує DocLister і не вимагає його встановлення.

Докладніше про роботу з деревом документів через `SiteContent`:
[«Робота з деревом документів через SiteContent»](https://gist.github.com/Juniorbuka/b6f4680477338bd39bd09ae9277d766b#%D1%80%D0%BE%D0%B1%D0%BE%D1%82%D0%B0-%D0%B7-%D0%B4%D0%B5%D1%80%D0%B5%D0%B2%D0%BE%D0%BC-%D0%B4%D0%BE%D0%BA%D1%83%D0%BC%D0%B5%D0%BD%D1%82%D1%96%D0%B2-%D1%87%D0%B5%D1%80%D0%B5%D0%B7-sitecontent).

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
