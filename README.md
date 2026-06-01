# Code master

Веб-платформа для обучения Python: курсы, уроки, задачи, автоматическая проверка решений в Docker, рейтинг пользователей, админ-панель, websocket-уведомления через Reverb и подсказки от локальной нейросети через Ollama.

## Что входит в Docker-сборку

- `app` - Laravel/PHP-FPM приложение.
- `nginx` - веб-сервер для сайта.
- `reverb` - websocket-сервер Laravel Reverb.
- `queue-solution` - очередь проверки решений задач.
- `queue-ai` - очередь генерации подсказок нейросетью.
- `queue-docker` - очередь сборки Docker-окружений из админ-панели.
- `scheduler` - Laravel scheduler.
- `mysql` - база данных.
- `redis` - очереди и cache.
- `ollama` - локальная нейросеть для подсказок.

## Требования к серверу

На сервере должны быть установлены:

- Docker
- Docker Compose plugin
- Git

Также нужен исходящий доступ к:

- `deb.debian.org`
- `registry.npmjs.org`
- `repo.packagist.org`
- `github.com`

Иначе первая сборка не сможет скачать зависимости.

## Быстрый запуск на сервере

1. Склонируйте проект и перейдите в папку:

```bash
git clone <URL_РЕПОЗИТОРИЯ> python-learn
cd python-learn
```

2. Создайте `.env` из серверного шаблона:

```bash
cp .env.server.example .env
```

3. Заполните в `.env` минимум эти значения:

```dotenv
APP_URL=http://YOUR_DOMAIN_OR_IP
VITE_REVERB_HOST=YOUR_DOMAIN_OR_IP
DB_PASSWORD=CHANGE_ME_DB_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD
REVERB_APP_KEY=CHANGE_ME_REVERB_KEY
REVERB_APP_SECRET=CHANGE_ME_REVERB_SECRET
```

Для проверки решений внутри Docker Laravel запускает отдельные judge-контейнеры. Они должны видеть тот же `storage` volume, что и контейнер `app`. Если проект лежит, например, в папке `app-python-learn`, Docker Compose обычно создаёт volume `app-python-learn_app_storage`. Укажите это имя в двух переменных:

```dotenv
APP_STORAGE_VOLUME=app-python-learn_app_storage
JUDGE_STORAGE_VOLUME=app-python-learn_app_storage
JUDGE_DOCKER_UID=33
JUDGE_DOCKER_GID=33
```

Проверить реальное имя можно командой:

```bash
docker volume ls | grep app_storage
```

Для генерации случайных ключей можно использовать:

```bash
openssl rand -hex 16
```

4. Сгенерируйте `APP_KEY`:

```bash
docker compose run --rm app php artisan key:generate --show
```

Вставьте результат в `.env`:

```dotenv
APP_KEY=base64:...
```

5. Соберите и запустите контейнеры:

```bash
docker compose up -d --build
```

Миграции запускаются автоматически при старте `app`, если в `.env` стоит:

```dotenv
APP_RUN_MIGRATIONS=true
```

6. Соберите Docker-образы для проверки пользовательского Python-кода:

```bash
docker compose exec app php artisan judge:install-python
docker compose exec app php artisan judge:install-python-pandas
```

7. Установите модель Ollama для подсказок:

```bash
curl http://127.0.0.1:11434/api/pull -d '{"name":"qwen2.5-coder:3b","stream":false}'
```

Модель также можно установить из админ-панели в разделе `Нейросеть`.

## Важные переменные окружения

### Сайт

```dotenv
APP_URL=http://YOUR_DOMAIN_OR_IP
APP_HTTP_PORT=80
APP_DEBUG=false
APP_RUN_MIGRATIONS=true
```

### База данных

В Docker Compose база доступна приложению по имени сервиса `mysql`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=python_learn
DB_USERNAME=python_learn
DB_PASSWORD=CHANGE_ME_DB_PASSWORD
DB_ROOT_PASSWORD=CHANGE_ME_ROOT_PASSWORD
```

Не ставьте `DB_HOST=127.0.0.1` внутри Docker-сборки, иначе Laravel будет искать MySQL внутри своего контейнера.

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
QUEUE_AI_WORKERS=2
QUEUE_DOCKER_WORKERS=1
QUEUE_SOLUTION_TIMEOUT=120
QUEUE_AI_TIMEOUT=240
QUEUE_DOCKER_TIMEOUT=1500
```

### Websocket/Reverb

Backend внутри Docker обращается к сервису `reverb`, а браузер пользователя подключается к публичному домену/IP:

```dotenv
BROADCAST_CONNECTION=reverb
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_PUBLIC_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="YOUR_DOMAIN_OR_IP"
VITE_REVERB_PORT="${REVERB_PUBLIC_PORT}"
VITE_REVERB_SCHEME=http
```

Если меняете `VITE_REVERB_*`, пересоберите контейнеры:

```bash
docker compose up -d --build
```

Эти значения попадают в JavaScript во время `vite build`.

### Ollama

```dotenv
OLLAMA_ENABLED=true
OLLAMA_URL=http://ollama:11434/api/chat
OLLAMA_MODEL=qwen2.5-coder:3b
OLLAMA_TIMEOUT=120
OLLAMA_PULL_TIMEOUT=600
OLLAMA_PUBLIC_PORT=11434
```

Ollama в compose опубликована только на `127.0.0.1:11434`, чтобы API модели не торчал наружу.

## Очереди

Проверка задач и подсказки ИИ вынесены в разные очереди:

- `solution-checks` - проверка решений.
- `ai-hints` - подсказки нейросети.
- `docker-builds` - сборка Docker-образов окружений из админ-панели.

Количество воркеров задается в `.env`:

```dotenv
QUEUE_SOLUTION_WORKERS=6
QUEUE_AI_WORKERS=2
QUEUE_DOCKER_WORKERS=1
```

После изменения количества воркеров перезапустите сервисы:

```bash
docker compose up -d
```

Один контейнер `queue-solution` поднимет внутри себя `QUEUE_SOLUTION_WORKERS` процессов `queue:work`, `queue-ai` поднимет `QUEUE_AI_WORKERS`, а `queue-docker` поднимет `QUEUE_DOCKER_WORKERS`.

Посмотреть логи очередей:

```bash
docker compose logs -f queue-solution
docker compose logs -f queue-ai
docker compose logs -f queue-docker
```

## Админ-панель: Docker-образы и модели ИИ

Через админ-панель можно собирать Docker-окружения для задач. В разделе окружений можно загрузить Dockerfile, указать имя образа, и сборка уйдет в очередь `docker-builds`. После успешной сборки окружение автоматически появится в списке и его можно выбрать у задачи.

Там же есть кнопки для стандартных образов:

```bash
python-learn/judge-python:3.12
python-learn/judge-python-pandas:3.12
```

Модели ИИ управляются в разделе `Нейросеть`. Админка показывает текущую модель, список установленных моделей Ollama, позволяет выбрать активную модель и скачать новую через Ollama pull.

## Проверка пользовательского кода

Laravel-контейнер использует Docker socket хоста:

```yaml
/var/run/docker.sock:/var/run/docker.sock
```

Это нужно, чтобы сервис проверки мог запускать изолированные контейнеры для пользовательских решений. На сервере Docker должен быть установлен и запущен.

Стандартные judge-образы собираются командами:

```bash
docker compose exec app php artisan judge:install-python
docker compose exec app php artisan judge:install-python-pandas
```

## Полезные команды

Статус контейнеров:

```bash
docker compose ps
```

Логи всего проекта:

```bash
docker compose logs -f
```

Логи Laravel:

```bash
docker compose exec app tail -f storage/logs/laravel.log
```

Очистить и заново собрать Laravel cache:

```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
```

Выполнить миграции:

```bash
docker compose exec app php artisan migrate --force
```

Остановить проект:

```bash
docker compose down
```

Остановить проект и удалить данные MySQL/Redis/Ollama:

```bash
docker compose down -v
```

Осторожно: `docker compose down -v` удалит данные базы, Redis и скачанные модели Ollama.

## Обновление проекта на сервере

```bash
git pull
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan optimize:clear
```

После изменения `VITE_REVERB_*`, frontend-файлов, `package.json` или `vite.config.js` обязательно нужна пересборка через `--build`.

## Локальная разработка без Docker

Если запускаете Laravel локально, а не в Docker:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run dev
composer run serve
```

`composer run serve` запускает локальный сервер Laravel с увеличенными лимитами загрузки файлов: видео до 200 МБ. Если запускать обычный `php artisan serve`, PHP может оставить стандартный лимит `upload_max_filesize = 2M`, и загрузка видео будет падать с ошибкой 422.

Для очередей локально:

```bash
php artisan queue:work --queue=solution-checks
php artisan queue:work --queue=ai-hints
```

Для websocket:

```bash
php artisan reverb:start
```
