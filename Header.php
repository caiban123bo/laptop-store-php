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
        <div class="logo">SALE LEP</div>
        <form action="search.php" method="GET" class="search-bar">
            <input type="text" name="keyword" placeholder="Nhập từ khóa..." required>
            <button type="submit">Tìm kiếm</button>
        </form>
        <div class="user-actions">
            <a href="Cart.php">Giỏ hàng</a>
            <a href="favorites.php">Yêu thích</a>
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
            <li><a href="OrderHistory.php">Lịch sử đơn hàng</a></li>
            <li><a href="about.php">Giới thiệu về nhóm</a></li>
            <li><a href="KhuyenMaiHienTai.php">Sự kiện khuyến mãi</a></li>
            <li><a href="TrangCaNhan.php">Trang cá nhân</a></li>
            <li><a href="admin\index.php">Admin/Dashboard</a></li>

        </ul>
    </nav>
</body>
</html>