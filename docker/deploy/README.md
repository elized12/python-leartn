# Запуск на сервере через Docker Compose

1. Скопируйте пример env:

```bash
cp .env.server.example .env
```

2. Заполните в `.env`:

- `APP_URL`
- `VITE_REVERB_HOST` - домен или IP, с которого браузер будет подключаться к websocket
- `DB_PASSWORD`
- `DB_ROOT_PASSWORD`
- `REVERB_APP_KEY`
- `REVERB_APP_SECRET`

Если меняете `VITE_REVERB_*`, нужно пересобрать контейнеры, потому что эти значения попадают в JS во время `vite build`.

3. Сгенерируйте ключ приложения:

```bash
docker compose run --rm app php artisan key:generate --show
```

Вставьте результат в `.env` как `APP_KEY=...`.

4. Соберите и запустите сервисы:

```bash
docker compose up -d --build
```

На сервере должен быть нормальный исходящий доступ к `deb.debian.org`, `registry.npmjs.org`, `repo.packagist.org` и `github.com`, иначе первая сборка не сможет скачать зависимости.

5. Выполните миграции:

```bash
docker compose exec app php artisan migrate --force
```

6. Соберите стандартные Docker-образы для проверки задач:

```bash
docker compose exec app php artisan judge:install-python
docker compose exec app php artisan judge:install-python-pandas
```

7. Установите модель Ollama:

```bash
docker compose exec app php artisan config:clear
curl http://127.0.0.1:11434/api/pull -d '{"name":"qwen2.5-coder:3b","stream":false}'
```

Или установите модель через админ-панель в разделе `Нейросеть`.

## Масштабирование очередей

Например, 6 воркеров проверки задач и 2 воркера ИИ:

```bash
docker compose up -d --scale queue-solution=6 --scale queue-ai=2
```

## Важное про Docker judge

Для проверки пользовательского кода Laravel-контейнер использует Docker socket хоста:

```yaml
/var/run/docker.sock:/var/run/docker.sock
```

На сервере должен быть установлен и запущен Docker.

## Порты

- сайт: `APP_HTTP_PORT`, по умолчанию `80`
- Reverb websocket: `REVERB_PUBLIC_PORT`, по умолчанию `8080`
- Ollama API: `OLLAMA_PUBLIC_PORT`, по умолчанию `11434`, доступен только с сервера через `127.0.0.1`
