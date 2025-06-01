<?php
require_once '..\assets\db.php';
function loadSidebar() {
    echo '<nav class="sidebar">
            <h2><a href="../index.php" style="text-decoration:none;color:white">Menu</a></h2>
            <ul>
                <li><a href="index.php">Sản phẩm</a></li>
                <li><a href="order_details.php">Lịch sử đặt hàng</a></li>
                <li><a href="promotions.php">Khuyến mãi</a></li>
                <li><a href="users.php" class="active">Khách hàng</a></li>
                <li><a href="user_opinions.php">Ý kiến khách hàng</a></li>
                <li><a href="promo_sales.php">Thông báo khuyến mãi</a></li>
                <li><a href="#">Reports (Coming Soon)</a></li>
                <li><a href="#">SuperIdol (Coming Soon)</a></li>
            </ul>
        </nav>';
}