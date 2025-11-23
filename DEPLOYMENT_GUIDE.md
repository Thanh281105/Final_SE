# HƯỚNG DẪN DEPLOY - HOTEL MANAGEMENT SYSTEM

## 🚀 CÁC BƯỚC TRIỂN KHAI

### Bước 1: Chuẩn bị Database

1. Tạo database mới:
```sql
CREATE DATABASE bluebirdhotel;
```

2. Import database gốc:
```sql
USE bluebirdhotel;
SOURCE bluebirdhotel.sql;
```

3. Chạy migration:
```sql
SOURCE database_migration.sql;
```

### Bước 2: Cấu hình

Sửa file `config.php`:
```php
$server = "localhost";        // Đổi nếu cần
$username = "bluebird_user";  // Đổi theo database user của bạn
$password = "password";       // Đổi theo password của bạn
$database = "bluebirdhotel";  // Đổi nếu dùng tên khác
```

### Bước 3: Deploy Files

**XAMPP:**
- Copy toàn bộ project vào: `C:\xampp\htdocs\Hotel-Management-System`

**WAMP:**
- Copy toàn bộ project vào: `C:\wamp64\www\Hotel-Management-System`

**Linux/Apache:**
- Copy vào: `/var/www/html/Hotel-Management-System`
- Set permissions: `chmod -R 755 Hotel-Management-System`

### Bước 4: Kiểm tra

1. Mở browser: `http://localhost/Hotel-Management-System/index.php`
2. Test đăng ký tài khoản mới
3. Test đăng nhập
4. Test các chức năng chính

## 🔐 TÀI KHOẢN MẶC ĐỊNH

Sau khi import database, có thể dùng:

**User:**
- Email: `tusharpankhaniya2202@gmail.com`
- Password: `123`

**Admin:**
- Email: `Admin@gmail.com`
- Password: `1234`

**Lưu ý**: Sau khi login lần đầu, password sẽ được hash tự động.

## ⚠️ LƯU Ý QUAN TRỌNG

1. **PHP Version**: Cần PHP 7.4 trở lên
2. **MySQL Version**: Cần MySQL 5.7 trở lên
3. **Extensions**: Cần mysqli extension
4. **Permissions**: Đảm bảo web server có quyền đọc/ghi

## 🐛 TROUBLESHOOTING

### Lỗi kết nối database
- Kiểm tra `config.php`
- Kiểm tra MySQL service đã chạy chưa
- Kiểm tra user/password có đúng không

### Lỗi 404
- Kiểm tra đường dẫn project
- Kiểm tra .htaccess (nếu có)
- Kiểm tra Apache mod_rewrite

### Lỗi session
- Kiểm tra session.save_path trong php.ini
- Đảm bảo thư mục session có quyền ghi

## ✅ CHECKLIST TRƯỚC KHI DEMO

- [ ] Database đã import đầy đủ
- [ ] Config.php đã cấu hình đúng
- [ ] Test đăng ký thành công
- [ ] Test đăng nhập thành công
- [ ] Test đặt phòng thành công
- [ ] Test thanh toán thành công
- [ ] Test admin functions
- [ ] Test check-in/check-out
- [ ] Test reports

