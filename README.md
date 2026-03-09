# Clinic App Day 1 🚀

## Mục tiêu Day 1
- Docker chạy được
- Laravel API chạy ở `http://localhost:8020`
- React chạy ở `http://localhost:5173`
- MySQL + Redis hoạt động
- Có route test `/api/health`

---

## Cấu trúc project

```text
clinic-app-day1/
  backend/
  frontend/
  docker/
  docker-compose.yml
```

---

## Bước 1: Tạo Laravel backend

```bash
docker run --rm -v $(pwd)/backend:/app composer:2 create-project laravel/laravel /app
```

Sau đó copy file `.env.example` trong zip thành:

```bash
cp backend/.env.example backend/.env
```

---

## Bước 2: Tạo React frontend (nếu muốn regenerate sạch)

```bash
docker run --rm -it -v $(pwd)/frontend:/app -w /app node:22-alpine sh -c "npm install"
```

---

## Bước 3: Start docker

```bash
docker compose up -d --build
```

---

## Bước 4: Cài Laravel package

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

---

## Bước 5: Thêm route health

Mở file:

```text
backend/routes/api.php
```

Copy nội dung từ:

```text
backend/routes_api_example.php
```

---

## Bước 6: Kiểm tra

### Laravel API
```text
http://localhost:8020/api/health
```

### React frontend
```text
http://localhost:5173
```

---

## Sau Day 1 📌

Tiếp theo nên tạo migration:

- doctors
- patient_sources
- patients
- visits

---

## Ghi chú
Laravel source chưa được đóng gói sẵn trong zip để zip nhẹ hơn.
Sau khi chạy create-project, backend sẽ đầy đủ.
