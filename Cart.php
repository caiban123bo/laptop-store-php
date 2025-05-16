<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra đăng nhập
$maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Lấy danh sách khuyến mãi
$khuyenMai = [];
$query_km = "SELECT MaKhuyenMai, PhanTramGiam, GiamToiDa, DieuKien 
             FROM KhuyenMai 
             WHERE TrangThai = TRUE AND NgayBatDau <= CURDATE() AND NgayKetThuc >= CURDATE() 
             AND SoLuong > 0";
$result_km = $conn->query($query_km);
while ($row = $result_km->fetch_assoc()) {
    $khuyenMai[$row['MaKhuyenMai']] = $row;
}

// Đồng bộ giỏ hàng session với bảng GioHang
if ($maNguoiDung && !empty($_SESSION['cart'])) {
    $stmt = $conn->prepare("DELETE FROM GioHang WHERE MaNguoiDung = ?");
    $stmt->bind_param('i', $maNguoiDung);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO GioHang (MaNguoiDung, MaLaptop, SoLuong) VALUES (?, ?, ?)");
    foreach ($_SESSION['cart'] as $maLaptop => $item) {
        $stmt->bind_param('iii', $maNguoiDung, $maLaptop, $item['quantity']);
        $stmt->execute();
    }
    $stmt->close();
} elseif ($maNguoiDung && empty($_SESSION['cart'])) {
    $stmt = $conn->prepare("
        SELECT g.MaLaptop, g.SoLuong, l.TenLaptop, l.GiaBan, h.TenHang, ha.DuongDan AS HinhAnh
        FROM GioHang g
        JOIN Laptop l ON g.MaLaptop = l.MaLaptop
        JOIN Hang h ON l.MaHang = h.MaHang
        LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE
        WHERE g.MaNguoiDung = ?
    ");
    $stmt->bind_param('i', $maNguoiDung);
    $stmt->execute();
    $result = $stmt->get_result();
    $_SESSION['cart'] = [];
    while ($row = $result->fetch_assoc()) {
        $_SESSION['cart'][$row['MaLaptop']] = [
            'name' => $row['TenHang'] . ' ' . $row['TenLaptop'],
            'price' => $row['GiaBan'],
            'quantity' => $row['SoLuong'],
            'image' => $row['HinhAnh'] ?: 'assets/images/default.png'
        ];
    }
    $stmt->close();
}

// Xử lý cập nhật giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $maLaptop => $quantity) {
        $quantity = max(1, intval($quantity));
        $query = "SELECT SoLuong FROM Laptop WHERE MaLaptop = ? AND TrangThai = 'ConHang'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $maLaptop);
        $stmt->execute();
        $result = $stmt->get_result();
        $laptop = $result->fetch_assoc();
        
        if ($laptop && $quantity <= $laptop['SoLuong']) {
            $_SESSION['cart'][$maLaptop]['quantity'] = $quantity;
            if ($maNguoiDung) {
                $stmt = $conn->prepare("UPDATE GioHang SET SoLuong = ? WHERE MaNguoiDung = ? AND MaLaptop = ?");
                $stmt->bind_param('iii', $quantity, $maNguoiDung, $maLaptop);
                $stmt->execute();
                $stmt->close();
            }
            echo '<div class="success-message">Đã cập nhật giỏ hàng!</div>';
        } else {
            echo '<div class="error-message">Số lượng cho sản phẩm ' . htmlspecialchars($_SESSION['cart'][$maLaptop]['name']) . ' không hợp lệ!</div>';
        }
    }
}

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $maLaptop = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$maLaptop])) {
        unset($_SESSION['cart'][$maLaptop]);
        if ($maNguoiDung) {
            $stmt = $conn->prepare("DELETE FROM GioHang WHERE MaNguoiDung = ? AND MaLaptop = ?");
            $stmt->bind_param('ii', $maNguoiDung, $maLaptop);
            $stmt->execute();
            $stmt->close();
        }
        echo '<div class="success-message">Đã xóa sản phẩm khỏi giỏ hàng!</div>';
    }
}

// Kiểm tra giỏ hàng rỗng
$cart_empty = empty($_SESSION['cart']);

// Tính tổng tiền và áp dụng khuyến mãi
$total = 0;
$discount = 0;
if (!$cart_empty) {
    foreach ($_SESSION['cart'] as $maLaptop => $item) {
        $item_total = $item['price'] * $item['quantity'];
        $query_lkm = "SELECT km.MaKhuyenMai, km.PhanTramGiam, km.GiamToiDa 
                      FROM LaptopKhuyenMai lkm 
                      JOIN KhuyenMai km ON lkm.MaKhuyenMai = km.MaKhuyenMai 
                      WHERE lkm.MaLaptop = ? AND km.TrangThai = TRUE 
                      AND km.NgayBatDau <= CURDATE() AND km.NgayKetThuc >= CURDATE() 
                      AND km.SoLuong > 0 LIMIT 1";
        $stmt = $conn->prepare($query_lkm);
        $stmt->bind_param('i', $maLaptop);
        $stmt->execute();
        $km = $stmt->get_result()->fetch_assoc();
        if ($km && $item_total >= $khuyenMai[$km['MaKhuyenMai']]['DieuKien']) {
            $item_discount = min(
                $item_total * $km['PhanTramGiam'] / 100,
                $km['GiamToiDa']
            );
            $discount += $item_discount;
        }
        $total += $item_total;
        $stmt->close();
    }
}
?>

<div class="cart-container">
    <div class="cart-items">
        <?php if ($cart_empty): ?>
            <div class="empty-cart">
                <p>Chưa có sản phẩm trong giỏ hàng</p>
                <a href="index.php"><button class="continue-shopping">Tiếp tục mua sắm</button></a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="cart-table-frame">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th class="product-column">Sản phẩm</th>
                                <th>Giá</th>
                                <th>Khuyến mãi</th>
                                <th>Số lượng</th>
                                <th>Tạm tính</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $maLaptop => $item): ?>
                                <?php
                                $item_discount = 0;
                                $query_lkm = "SELECT km.PhanTramGiam, km.GiamToiDa, km.DieuKien 
                                              FROM LaptopKhuyenMai lkm 
                                              JOIN KhuyenMai km ON lkm.MaKhuyenMai = km.MaKhuyenMai 
                                              WHERE lkm.MaLaptop = ? AND km.TrangThai = TRUE 
                                              AND km.NgayBatDau <= CURDATE() AND km.NgayKetThuc >= CURDATE() 
                                              AND km.SoLuong > 0 LIMIT 1";
                                $stmt = $conn->prepare($query_lkm);
                                $stmt->bind_param('i', $maLaptop);
                                $stmt->execute();
                                $km = $stmt->get_result()->fetch_assoc();
                                if ($km && ($item['price'] * $item['quantity']) >= $km['DieuKien']) {
                                    $item_discount = min(
                                        ($item['price'] * $item['quantity']) * $km['PhanTramGiam'] / 100,
                                        $km['GiamToiDa']
                                    );
                                }
                                $stmt->close();
                                ?>
                                <tr>
                                    <td class="product-info">
                                        <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>">
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><?php echo $item_discount ? formatPrice($item_discount) : '-'; ?></td>
                                    <td class="quantity">
                                        <input type="number" name="quantity[<?php echo $maLaptop; ?>]" 
                                               value="<?php echo $item['quantity']; ?>" min="1" max="100">
                                    </td>
                                    <td><?php echo formatPrice($item['price'] * $item['quantity'] - $item_discount); ?></td>
                                    <td class="remove">
                                        <a href="cart.php?remove=<?php echo $maLaptop; ?>" 
                                           class="remove-btn" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="cart-actions">
                    <a href="index.php"><button type="button" class="continue-shopping">Tiếp tục mua sắm</button></a>
                    <button type="submit" name="update_cart" class="update-cart">Cập nhật giỏ hàng</button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="cart-summary">
        <div class="summary-frame">
            <h3>Tổng tiền hàng</h3>
            <p>Tạm tính: <?php echo formatPrice($total); ?></p>
            <p>Giảm giá: <?php echo formatPrice($discount); ?></p>
            <p class="total">Tổng: <?php echo formatPrice($total - $discount); ?></p>
            <?php if (!$cart_empty): ?>
                <a href="CheckOut.php">
                    <button class="checkout-btn">Tiến hành thanh toán</button>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    setTimeout(() => {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(msg => msg.style.opacity = '0');
    }, 3000);
</script>

<?php
$conn->close();
include 'footer.php';
?>