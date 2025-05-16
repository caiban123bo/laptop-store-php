<?php
include 'header.php';
include 'assets/db.php';


// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$maNguoiDung = intval($_SESSION['user_id']);

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Khởi tạo giỏ hàng nếu chưa tồn tại
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Đồng bộ giỏ hàng từ cơ sở dữ liệu nếu đã đăng nhập
if ($maNguoiDung && empty($_SESSION['cart'])) {
    $stmt = $conn->prepare("
        SELECT g.MaLaptop, g.SoLuong, l.TenLaptop, l.GiaBan, h.TenHang
        FROM GioHang g
        JOIN Laptop l ON g.MaLaptop = l.MaLaptop
        JOIN Hang h ON l.MaHang = h.MaHang
        WHERE g.MaNguoiDung = ?
    ");
    $stmt->bind_param('i', $maNguoiDung);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $_SESSION['cart'][$row['MaLaptop']] = [
            'name' => $row['TenHang'] . ' ' . $row['TenLaptop'],
            'price' => $row['GiaBan'],
            'quantity' => $row['SoLuong'],
            'image' => 'assets/images/default.png' // Giả định nếu không có hình ảnh
        ];
    }
    $stmt->close();
}

// Lấy địa chỉ người dùng
$diaChi = [];
$stmt = $conn->prepare("SELECT MaDiaChi, HoTen, SoDienThoai, DiaChi, TinhThanh, QuanHuyen, PhuongXa, MacDinh 
                        FROM DiaChi WHERE MaNguoiDung = ?");
$stmt->bind_param('i', $maNguoiDung);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $diaChi[] = $row;
}
$stmt->close();

// Lấy MaPhuongThuc cho COD
$stmt = $conn->prepare("SELECT MaPhuongThuc FROM PhuongThucThanhToan WHERE TenPhuongThuc = 'Thanh toán khi nhận hàng' AND TrangThai = TRUE");
$stmt->execute();
$cod = $stmt->get_result()->fetch_assoc();
$maPhuongThuc = $cod ? $cod['MaPhuongThuc'] : 1; // Giả định MaPhuongThuc=1 nếu không tìm thấy
$stmt->close();

// Tính toán giỏ hàng
$cart_empty = empty($_SESSION['cart']);
$total = 0;
$shipping_fee = $cart_empty ? 0 : 30000; // Phí vận chuyển giả định

if (!$cart_empty) {
    foreach ($_SESSION['cart'] as $maLaptop => $item) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
    }
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !$cart_empty) {
    $maDiaChi = intval($_POST['address']);
    $ghiChu = trim($_POST['note']);
    
    // Kiểm tra địa chỉ
    $stmt = $conn->prepare("SELECT MaDiaChi FROM DiaChi WHERE MaDiaChi = ? AND MaNguoiDung = ?");
    $stmt->bind_param('ii', $maDiaChi, $maNguoiDung);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo '<div class="error-message">Địa chỉ không hợp lệ!</div>';
        $stmt->close();
        include 'footer.php';
        exit;
    }
    $stmt->close();
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    try {
        // Thêm đơn hàng
        $tongThanhToan = $total + $shipping_fee;
        $stmt = $conn->prepare("
            INSERT INTO DonHang (MaNguoiDung, MaDiaChi, MaPhuongThuc, TongTienHang, PhiVanChuyen, TongThanhToan, GhiChu, TrangThai)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'ChoXacNhan')
        ");
        $stmt->bind_param('iiiidss', $maNguoiDung, $maDiaChi, $maPhuongThuc, $total, $shipping_fee, $tongThanhToan, $ghiChu);
        $stmt->execute();
        $maDonHang = $conn->insert_id;
        $stmt->close();
        
        // Thêm chi tiết đơn hàng
        $stmt = $conn->prepare("
            INSERT INTO ChiTietDonHang (MaDonHang, MaLaptop, SoLuong, DonGia, ThanhTien)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($_SESSION['cart'] as $maLaptop => $item) {
            $thanhTien = $item['price'] * $item['quantity'];
            $stmt->bind_param('iiidd', $maDonHang, $maLaptop, $item['quantity'], $item['price'], $thanhTien);
            $stmt->execute();
            
            // Cập nhật số lượng laptop
            $stmt_update = $conn->prepare("UPDATE Laptop SET SoLuong = SoLuong - ? WHERE MaLaptop = ?");
            $stmt_update->bind_param('ii', $item['quantity'], $maLaptop);
            $stmt_update->execute();
            $stmt_update->close();
        }
        $stmt->close();
        
        // Thêm lịch sử đơn hàng
        $stmt = $conn->prepare("
            INSERT INTO LichSuDonHang (MaDonHang, TrangThaiMoi, GhiChu, NguoiCapNhat)
            VALUES (?, 'ChoXacNhan', 'Đơn hàng được tạo', ?)
        ");
        $stmt->bind_param('ii', $maDonHang, $maNguoiDung);
        $stmt->execute();
        $stmt->close();
        
        // Xóa giỏ hàng
        unset($_SESSION['cart']);
        if ($maNguoiDung) {
            $stmt = $conn->prepare("DELETE FROM GioHang WHERE MaNguoiDung = ?");
            $stmt->bind_param('i', $maNguoiDung);
            $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        // Chuyển hướng về index.php với thông báo
        $_SESSION['order_success'] = 'Đặt hàng thành công! Mã đơn hàng: ' . $maDonHang;
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo '<div class="error-message">Lỗi khi đặt hàng: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>

<div class="checkout-container">
    <?php if ($cart_empty): ?>
        <div class="empty-cart">
            <p>Giỏ hàng của bạn đang trống</p>
            <a href="index.php"><button class="continue-shopping">Tiếp tục mua sắm</button></a>
        </div>
    <?php else: ?>
        <div class="checkout-wrapper">
            <div class="checkout-form">
                <h2>Xác nhận thanh toán</h2>
                <form method="POST">
                    <div class="form-section address-section">
                        <h3>Địa chỉ giao hàng</h3>
                        <?php if (empty($diaChi)): ?>
                            <p>Chưa có địa chỉ. Vui lòng thêm địa chỉ mới.</p>
                        <?php else: ?>
                            <select name="address" required>
                                <?php foreach ($diaChi as $dc): ?>
                                    <option value="<?php echo $dc['MaDiaChi']; ?>" <?php echo $dc['MacDinh'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dc['HoTen'] . ', ' . $dc['SoDienThoai'] . ', ' . $dc['DiaChi'] . ', ' . $dc['PhuongXa'] . ', ' . $dc['QuanHuyen'] . ', ' . $dc['TinhThanh']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <p class="add-address-link"><a href="add_address.php">Thêm địa chỉ mới</a></p>
                    </div>
                    <div class="form-section order-summary-section">
                        <h3>Tóm tắt đơn hàng</h3>
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th class="product-column">Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $maLaptop => $item): ?>
                                    <tr>
                                        <td class="product-info">
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="summary-details">
                            <p><span>Tạm tính:</span> <?php echo formatPrice($total); ?></p>
                            <p><span>Phí vận chuyển:</span> <?php echo formatPrice($shipping_fee); ?></p>
                            <p class="total"><span>Tổng thanh toán:</span> <?php echo formatPrice($total + $shipping_fee); ?></p>
                        </div>
                    </div>
                    <div class="form-section note-section">
                        <h3>Ghi chú</h3>
                        <textarea name="note" placeholder="Ghi chú cho đơn hàng"></textarea>
                    </div>
                    <button type="submit" name="place_order" class="place-order-btn">Đặt hàng</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
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