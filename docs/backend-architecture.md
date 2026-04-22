# Backend Architecture

## Контуры приложения

- `api` — публичный JSON API под будущий Vue frontend
- `backend` — админка и внутренние CRUD/workflow интерфейсы
- `console` — миграции, импорт, очереди, интеграции
- `common` — общие модели, сервисы, DTO, jobs, инфраструктурные компоненты

## Инфраструктурные компоненты

- `db` — PostgreSQL
- `redis` — cache, coordination и transport для очереди
- `queue` — `yii\queue\redis\Queue`
- `storage` — локальный adapter, который потом можно заменить на S3-compatible

## Базовые bounded contexts

### Catalog

- `Song` — произведение
- `Recording` — конкретная запись/исполнение
- `SongLine` — строка оригинального текста
- `SongLineTranslation` — перевод строки
- `SongLineTransliteration` — транслитерация строки

### People

- `Artist`
- `SongAuthor`
- `RecordingArtist`

### Metadata

- `Language`
- `Tag`
- `MediaAsset`

### Automation

- jobs генерации переводов
- jobs импорта/обработки медиа
- jobs распознавания текста из audio/video в будущем

## Принципы по слоям

- API не работает напрямую с ActiveRecord наружу, а отдает response-модели/DTO.
- Файлы не сохраняются напрямую в контроллерах, только через компонент `storage`.
- Очереди не знают о транспортном слое API и получают только прикладные идентификаторы.
- Доменные сущности песен и переводов не смешиваются с сущностями конкретных медиа-записей.

## Эволюция storage

Сейчас:

- `LocalStorage`
- путь хранения: `storage/uploads`
- публичная отдача через `nginx` alias `/uploads/`

Потом:

- `S3Storage` или `R2Storage`
- тот же `StorageInterface`
- изменение только конфигурации компонента `storage`

## Эволюция AI

Сейчас:

- ручное сохранение переводов

Потом:

- enqueue job на генерацию чернового перевода
- статус `draft_ai`
- ручное ревью и публикация

Следующий логичный шаг после инфраструктуры — спроектировать первые миграции для:

- `language`
- `artist`
- `song`
- `recording`
- `song_line`
- `song_line_translation`
- `tag`
