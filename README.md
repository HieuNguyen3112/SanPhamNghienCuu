# 📚 Hệ Thống Quản Lý Công Trình Khoa Học Của Giảng Viên

## 📌 Giới thiệu
Hệ thống web hỗ trợ quản lý **công trình nghiên cứu khoa học của giảng viên**. Ứng dụng giúp lưu trữ, tìm kiếm và quản lý thông tin nghiên cứu một cách tập trung, phục vụ cho nhà trường và giảng viên.

Repo: https://github.com/HieuNguyen3112/SanPhamNghienCuu.git

---

## 🧑‍💻 Công nghệ sử dụng

### Frontend
- Vue.js
- Deploy: **Vercel**

### Backend
- Laravel
- Deploy: **Render**

### Database
- MySQL (Local)
- PostgreSQL (Production - Render)

### Hosting
- Domain / Hosting: **Matbao**

---

## 🚀 Tính năng chính

### Public
- Xem danh sách công trình khoa học
- Xem chi tiết công trình
- Tìm kiếm công trình nghiên cứu

### Giảng viên
- Quản lý hồ sơ cá nhân
- Thêm / sửa / xoá công trình khoa học
- Upload file minh chứng

### Quản trị viên
- Quản lý người dùng
- Quản lý danh mục công trình
- Duyệt và quản lý công trình
- Thống kê dữ liệu

---

## ⚙️ Hướng dẫn cài đặt và chạy dự án

Clone project:
```bash
git clone https://github.com/HieuNguyen3112/SanPhamNghienCuu.git
```

---

# 🖥️ Cài đặt Backend (Laravel)

## 1. Di chuyển vào thư mục backend
```bash
cd backend
```

## 2. Cài đặt package
```bash
composer install
```

## 3. Tạo file môi trường
```bash
cp .env.example .env
```

## 4. Generate key
```bash
php artisan key:generate
```

## 5. Cấu hình database trong file `.env`

### Local (MySQL)
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=root
DB_PASSWORD=
```

## 6. Chạy migrate database
```bash
php artisan migrate
```

## 7. Chạy server
```bash
php artisan serve
```

Backend chạy tại:
```
http://127.0.0.1:8000
```

---

# 🌐 Cài đặt Frontend (Vue)

## 1. Di chuyển vào thư mục frontend
```bash
cd frontend
```

## 2. Cài đặt package
```bash
npm install
```

## 3. Chạy môi trường development
```bash
npm run dev
```

Frontend chạy tại:
```
http://localhost:5173
```

---

## 🔗 Deploy

| Thành phần | Nền tảng |
|---|---|
| Frontend | Vercel |
| Backend | Render |
| Database | PostgreSQL (Render) |
| Domain | Matbao |

---

## 📂 Cấu trúc thư mục

```
SanPhamNghienCuu/
 ├── backend/   # Laravel API
 ├── frontend/  # Vue Application
 └── README.md
```

---

## 🤝 Đóng góp


## 📄 License


