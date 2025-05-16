<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <link rel="stylesheet" href="assets/css.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">SALE LED</div>
        <div class="search-bar">
            <input type="text" placeholder="Nhập từ khóa...">
            <button>Tìm kiếm</button>
        </div>
        <div class="user-actions">
            <a href="Cart.php">Giỏ hàng</a>
            <span>Yêu thích</span>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button></a>
            <?php else: ?>
                <a href="login.php"><button class="login-btn"><i class="fas fa-user"></i> Đăng nhập</button></a>
            <?php endif; ?>
        </div>
    </header>

    <nav class="nav-menu">
        <ul>
            <li><a href="index.php">Trang chủ</a></li>
            <li><a href="#">Lịch sử đơn hàng</a></li>
            <li><a href="#">Giới thiệu về nhóm</a></li>
            <li><a href="#">Sự kiện khuyến mãi</a></li>
            <li><a href="#">Trang cá nhân</a></li>
        </ul>
    </nav>