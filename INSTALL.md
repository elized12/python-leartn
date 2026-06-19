# Установка и запуск Code Master

Этот файл описывает правильный запуск проекта на сервере и локально. Основной рекомендуемый способ для сервера - Docker Compose.

## Требования

На сервере должны быть установлены:

- Docker;
- Docker Compose plugin;
- Git.

Во время первой сборки серверу нужен исходящий доступ к:

- `deb.debian.org`;
- `registry.npmjs.org`;
- `repo.packagist.org`;
- `github.com`;
- Docker Hub.

## Быстрый запуск на сервере

1. Склонируйте проект:

```bash
git clone <URL_РЕПОЗИТОРИЯ> code-master
cd code-master
```

2. Создайте `.env` из серверного шаблона:

```bash
cp .env.production .env
```

3. Заполните основные значения в `.env`:

```dotenv
APP_URL=http://YOUR_DOMAIN_OR_IP
VITE_REVERB_HOST=YOUR_DOMAIN_OR_IP

DB_PASSWORD=CHANGE_ME_DB_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD

REVERB_APP_KEY=CHANGE_ME_REVERB_KEY
REVERB_APP_SECRET=CHANGE_ME_REVERB_SECRET
```

Для генерации случайных ключей можно использовать:

```bash
openssl rand -hex 32
```

4. Укажите имя Docker volume для `storage`.

В `docker-compose.yml` volume называется через переменную `APP_STORAGE_VOLUME`. По умолчанию в `.env.production` используется:

```dotenv
APP_STORAGE_VOLUME=app-python-learn_app_storage
JUDGE_STORAGE_VOLUME=app-python-learn_app_storage
```

Если папка проекта на сервере называется иначе, Docker Compose может создать volume с другим именем. Проверить можно после первого запуска:

```bash
docker volume ls | grep app_storage
```

Значения `APP_STORAGE_VOLUME` и `JUDGE_STORAGE_VOLUME` должны совпадать. Это нужно, чтобы контейнеры проверки кода видели файлы из `storage`.

5. Для Docker-сборки judge-контейнеры должны запускаться от пользователя `www-data`:

```dotenv
JUDGE_DOCKER_UID=33
JUDGE_DOCKER_GID=33
```

6. Сгенерируйте `APP_KEY`:

```bash
docker compose run --rm app php artisan key:generate --show
```

Скопируйте результат в `.env`:

```dotenv
APP_KEY=base64:...
```

7. Соберите и запустите проект:

```bash
docker compose up -d --build
```

Если в `.env` включено:

```dotenv
APP_RUN_MIGRATIONS=true
```

миграции будут выполнены автоматически при старте контейнера `app`.

8. Соберите стандартные Docker-образы для проверки Python-кода:

```bash
docker compose exec app php artisan judge:install-python
docker compose exec app php artisan judge:install-python-pandas
docker compose exec app php artisan judge:install-python-fastapi
```

9. Установите модель Ollama для ИИ-подсказок:

```bash
curl http://127.0.0.1:11434/api/pull -d '{"name":"qwen2.5-coder:3b","stream":false}'
```

Модель также можно установить из админ-панели в разделе `Нейросеть`.

## Важные переменные окружения

### Приложение

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://YOUR_DOMAIN_OR_IP
APP_HTTP_PORT=80
APP_RUN_MIGRATIONS=true
```

### База данных

В Docker Compose приложение должно обращаться к MySQL по имени сервиса `mysql`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=python_learn
DB_USERNAME=python_learn
DB_PASSWORD=CHANGE_ME_DB_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD
```

Не ставьте `DB_HOST=127.0.0.1` внутри Docker-сборки. Тогда Laravel будет искать MySQL внутри контейнера `app`, а не в сервисе `mysql`.

### Redis и очереди

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=redis

QUEUE_SOLUTION_CHECKS=solution-checks
QUEUE_AI_HINTS=ai-hints
QUEUE_DOCKER_BUILDS=docker-builds

QUEUE_SOLUTION_WORKERS=6
QUEUE_AI_WORKERS=1
QUEUE_DOCKER_WORKERS=1

QUEUE_SOLUTION_TIMEOUT=120
QUEUE_AI_TIMEOUT=240
QUEUE_DOCKER_TIMEOUT=1500
```

`queue-solution`, `queue-ai` и `queue-docker` запускают внутри себя указанное количество процессов `queue:work`.

После изменения количества воркеров перезапустите сервисы:

```bash
docker compose up -d
```

Или отдельно:

```bash
docker compose restart queue-solution queue-ai queue-docker
```

### Reverb / WebSocket

Backend внутри Docker обращается к сервису `reverb`, а браузер пользователя подключается к публичному IP или домену:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_PUBLIC_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=YOUR_DOMAIN_OR_IP
VITE_REVERB_PORT="${REVERB_PUBLIC_PORT}"
VITE_REVERB_SCHEME=http
```

Если меняете `VITE_REVERB_*`, нужна пересборка, потому что эти значения попадают в JavaScript во время `vite build`:

```bash
docker compose up -d --build
```

### Ollama

```dotenv
OLLAMA_ENABLED=true
OLLAMA_URL=http://ollama:11434/api/chat
OLLAMA_MODEL=qwen2.5-coder:3b
OLLAMA_TIMEOUT=120
OLLAMA_PULL_TIMEOUT=600
OLLAMA_PUBLIC_PORT=11434
```

В `docker-compose.yml` Ollama опубликована только на `127.0.0.1`, чтобы API модели не был открыт наружу:

```yaml
127.0.0.1:${OLLAMA_PUBLIC_PORT:-11434}:11434
```

### Проверка пользовательского кода

```dotenv
JUDGE_DOCKER_UID=33
JUDGE_DOCKER_GID=33
JUDGE_STORAGE_VOLUME=app-python-learn_app_storage
JUDGE_WALL_TIMEOUT_MULTIPLIER=10
JUDGE_WALL_TIMEOUT_GRACE_S=10
JUDGE_MIN_WALL_TIMEOUT_S=30
JUDGE_OUTPUT_LIMIT_BYTES=1048576
JUDGE_MEMORY_OVERHEAD_MB=32
JUDGE_CPU_SHARES=2048
JUDGE_NICE=
```

Laravel-контейнер использует Docker socket хоста:

```yaml
/var/run/docker.sock:/var/run/docker.sock
```

Это нужно, чтобы сервис проверки мог запускать отдельные контейнеры для решений учеников.

## Полезные команды

Проверить состояние контейнеров:

```bash
docker compose ps
```

Посмотреть все логи:

```bash
docker compose logs -f
```

Логи Laravel:

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

Логи очередей:

```bash
docker compose logs -f queue-solution
docker compose logs -f queue-ai
docker compose logs -f queue-docker
```

Очистить кэш Laravel:

```bash
docker compose exec app php artisan optimize:clear
```

Собрать production-кэш:

```bash
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Выполнить миграции вручную:

```bash
docker compose exec app php artisan migrate --force
```

Перезапустить очереди после изменения кода:

```bash
docker compose exec app php artisan queue:restart
docker compose restart queue-solution queue-ai queue-docker
```

Остановить проект:

```bash
docker compose down
```

Остановить проект и удалить данные MySQL, Redis, Ollama и storage:

```bash
docker compose down -v
```

Команда `docker compose down -v` удаляет данные. Используйте ее осторожно.

## Обновление проекта на сервере

```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan queue:restart
docker compose restart queue-solution queue-ai queue-docker
```

После изменения frontend-файлов, `package.json`, `vite.config.js` или `VITE_REVERB_*` обязательно запускайте с `--build`.

## Локальная разработка без Docker

1. Установите зависимости:

```bash
composer install
npm install
```

2. Создайте `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

3. Настройте базу данных в `.env` и выполните миграции:

```bash
php artisan migrate
```

4. Запустите frontend:

```bash
npm run dev
```

5. Запустите Laravel:

```bash
composer run serve
```

`composer run serve` запускает сервер с увеличенными лимитами загрузки файлов. Это важно для локальной загрузки видео в курсах.

6. Запустите очереди:

```bash
php artisan queue:work --queue=solution-checks
php artisan queue:work --queue=ai-hints
php artisan queue:work --queue=docker-builds
```

7. Запустите Reverb:

```bash
php artisan reverb:start
```

## Частые проблемы

### Не приходят результаты проверки задачи

Перезапустите очередь проверки:

```bash
docker compose exec app php artisan queue:restart
docker compose restart queue-solution
```

Проверьте логи:

```bash
docker compose logs -f queue-solution
```

### Judge не видит `judge_runner.py` или получает `Permission denied`

Проверьте, что `APP_STORAGE_VOLUME` и `JUDGE_STORAGE_VOLUME` совпадают, а UID/GID установлены как:

```dotenv
JUDGE_DOCKER_UID=33
JUDGE_DOCKER_GID=33
```

Затем пересоздайте контейнеры:

```bash
docker compose up -d --build
```

### WebSocket не подключается

Проверьте:

```dotenv
VITE_REVERB_HOST=YOUR_DOMAIN_OR_IP
REVERB_PUBLIC_PORT=8080
```

После изменения `VITE_REVERB_*` выполните:

```bash
docker compose up -d --build
```

### Ollama возвращает ошибку модели

Проверьте, что модель скачана:

```bash
curl http://127.0.0.1:11434/api/tags
```

Если модели нет, скачайте ее:

```bash
curl http://127.0.0.1:11434/api/pull -d '{"name":"qwen2.5-coder:3b","stream":false}'
```
