## Nếu upload lỗi 413 Request Entity Too Large

Cần tăng giới hạn Nginx.

Trong file:

```docker/nginx/default.conf```

đã có:

```client_max_body_size 20M;```

Nếu chưa đủ, tăng lên:

```client_max_body_size 50M;```

rồi rebuild:

```bash
docker compose down
docker compose up -d --build
```

## Nếu lưu DB được nhưng file không hiện URL

Kiểm tra:

```bash
docker compose exec app ls -l public/storage
docker compose exec app ls -l storage/app/public
```

Nếu symlink lỗi, chạy lại:

```bash
docker compose exec app php artisan storage:link
```
