# GeoLyrics Backend

Backend-платформа для `geolyrics.ru` на `Yii2`:

- `api` для Vue/public frontend
- `backend` для админки
- `console` для миграций и фоновых задач
- `PostgreSQL` как основная БД
- `Redis + yii2-queue` для фона
- локальное файловое хранилище с подготовленным переключением на внешнее

## Структура

- `api` — JSON API и версии `/v1/...`
- `backend` — server-rendered админка
- `common` — общие модели, компоненты, jobs, конфиги
- `console` — миграции и CLI
- `docker` — `php-fpm` и `nginx`
- `storage/uploads` — локальное файловое хранилище
- `docs/backend-architecture.md` — целевая архитектура домена

## Быстрый старт

1. Подними контейнеры:

```bash
docker compose up --build -d
```

2. Установи php-зависимости:

```bash
docker compose exec -u $(id -u):$(id -g) php composer install --no-interaction
```

3. Прогони миграции:

```bash
docker compose exec php php yii migrate --interactive=0
```

4. Создай первого администратора:

```bash
docker compose exec php php yii user/create-admin admin admin@example.com StrongPassword123
```

5. Открой домены из раздела ниже.

## Домены

По умолчанию проект настроен на:

- API: `api.geolyrics.ge`
- Admin: `admin.geolyrics.ge`

Контейнеры сидят в отдельной docker-сети проекта `172.21.7.0/24` со статическими IP:

- `nginx`: `172.21.7.10`
- `php`: `172.21.7.11`
- `queue`: `172.21.7.12`
- `postgres`: `172.21.7.21`
- `redis`: `172.21.7.22`

Для Linux-хоста можно добавить в `/etc/hosts`:

```text
172.21.7.10 api.geolyrics.ge
172.21.7.10 admin.geolyrics.ge
```

После этого можно открывать:

- `http://api.geolyrics.ge`
- `http://admin.geolyrics.ge`

Важно: на `Docker Desktop` для macOS/Windows IP контейнеров из bridge-сети обычно не маршрутизируются с хоста. В таком окружении домены в `/etc/hosts` лучше маппить на `127.0.0.1`, а доступ оставлять через опубликованный порт:

```text
127.0.0.1 api.geolyrics.ge
127.0.0.1 admin.geolyrics.ge
```

Тогда локально использовать:

- `http://api.geolyrics.ge:8080/`
- `http://admin.geolyrics.ge:8080/`

## Локальное хранилище

Файлы сохраняются через компонент `storage` в `storage/uploads`.

Текущая реализация:

- `common/components/storage/StorageInterface.php`
- `common/components/storage/LocalStorage.php`

Чтобы потом перейти на внешний storage, достаточно добавить новый адаптер, реализующий тот же интерфейс, и заменить конфиг компонента `storage`.

## Queue

- Redis connection: `common/config/main.php`
- Queue driver: `yii\queue\redis\Queue`
- Worker service: `queue` в `docker-compose.yml`

В проект уже добавлен доменный placeholder job:

- `common/jobs/GenerateSongTranslationJob.php`

Это заготовка под будущую автоматическую генерацию переводов через LLM после ручного workflow.
