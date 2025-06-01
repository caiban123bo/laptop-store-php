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

// Lấy danh sách khuyến mãi
$khuyenMai = [];
$query_km = "SELECT MaKhuyenMai, TenChuongTrinh, PhanTramGiam, GiamToiDa, COALESCE(DieuKien, 0) AS DieuKien
            FROM KhuyenMai 
            WHERE TrangThai = TRUE 
            AND (NgayBatDau <= CURDATE() OR NgayBatDau IS NULL)
            AND (NgayKetThuc >= CURDATE() OR NgayKetThuc IS NULL)
            AND (SoLuong > 0 OR SoLuong IS NULL)
            ORDER BY PhanTramGiam DESC, GiamToiDa DESC";
$result_km = $conn->query($query_km);
while ($row = $result_km->fetch_assoc()) {
    $khuyenMai[] = $row;
}
error_log(print_r($khuyenMai, true));
// Lấy danh sách phương thức thanh toán
$phuongThuc = [];
$stmt = $conn->prepare("SELECT MaPhuongThuc, TenPhuongThuc FROM PhuongThucThanhToan WHERE TrangThai = TRUE");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $phuongThuc[] = $row;
}
$stmt->close();

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

// Tính toán giỏ hàng
$cart_empty = empty($_SESSION['cart']);
$total = 0;
$shipping_fee = $cart_empty ? 0 : 30000; // Phí vận chuyển giả định
$discount = 0;
$selected_promo = null;

if (!$cart_empty) {
    foreach ($_SESSION['cart'] as $maLaptop => $item) {
        $item_total = $item['price'] * $item['quantity'];
        // Kiểm tra khuyến mãi cho từng sản phẩm
        $query_lkm = "SELECT km.MaKhuyenMai, km.PhanTramGiam, km.GiamToiDa, km.DieuKien, km.TenChuongTrinh 
                      FROM LaptopKhuyenMai lkm 
                      JOIN KhuyenMai km ON lkm.MaKhuyenMai = km.MaKhuyenMai 
                      WHERE lkm.MaLaptop = ? AND km.TrangThai = TRUE 
                      AND km.NgayBatDau <= CURDATE() AND km.NgayKetThuc >= CURDATE() 
                      AND km.SoLuong > 0 LIMIT 1";
        $stmt = $conn->prepare($query_lkm);
        $stmt->bind_param('i', $maLaptop);
        $stmt->execute();
        $km = $stmt->get_result()->fetch_assoc();
        if ($km && $item_total >= ($km['DieuKien'] ?? 0)) {
            $item_discount = min(
                $item_total * $km['PhanTramGiam'] / 100,
                $km['GiamToiDa']
            );
            $discount += $item_discount;
            $_SESSION['cart'][$maLaptop]['promo_name'] = $km['TenChuongTrinh'];
        } else {
            $_SESSION['cart'][$maLaptop]['promo_name'] = '';
        }
        $total += $item_total;
        $stmt->close();
    }
    // Kiểm tra khuyến mãi được chọn từ form
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promo_code']) && !empty($_POST['promo_code'])) {
        foreach ($khuyenMai as $km) {
            if ($km['MaKhuyenMai'] == $_POST['promo_code'] && $total >= ($km['DieuKien'] ?? 0)) {
                $selected_promo = $km;
                $promo_discount = min(
                    $total * $km['PhanTramGiam'] / 100,
                    $km['GiamToiDa']
                );
                $discount = max($discount, $promo_discount); // Chọn khuyến mãi tốt nhất
                break;
            }
        }
    } else {
        // Tự động chọn khuyến mãi tốt nhất nếu không có promo_code
        foreach ($khuyenMai as $km) {
            if ($total >= ($km['DieuKien'] ?? 0)) {
                $promo_discount = min(
                    $total * $km['PhanTramGiam'] / 100,
                    $km['GiamToiDa']
                );
                if (!$selected_promo || $promo_discount > $discount) {
                    $selected_promo = $km;
                    $discount += $promo_discount;
                }
            }
        }
    }
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !$cart_empty) {
    $maDiaChi = intval($_POST['address']);
    $maPhuongThuc = intval($_POST['payment_method']);
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
    
    // Kiểm tra phương thức thanh toán
    $stmt = $conn->prepare("SELECT MaPhuongThuc FROM PhuongThucThanhToan WHERE MaPhuongThuc = ? AND TrangThai = TRUE");
    $stmt->bind_param('i', $maPhuongThuc);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo '<div class="error-message">Phương thức thanh toán không hợp lệ!</div>';
        $stmt->close();
        include 'footer.php';
        exit;
    }
    $stmt->close();
    
    // Kiểm tra số lượng tồn kho
    $stock_valid = true;
    foreach ($_SESSION['cart'] as $maLaptop => $item) {
        $stmt = $conn->prepare("SELECT SoLuong FROM Laptop WHERE MaLaptop = ? AND TrangThai = 'ConHang'");
        $stmt->bind_param('i', $maLaptop);
        $stmt->execute();
        $result = $stmt->get_result();
        $laptop = $result->fetch_assoc();
        $stmt->close();
        
        if (!$laptop || $laptop['SoLuong'] < $item['quantity']) {
            $stock_valid = false;
            echo '<div class="error-message">Sản phẩm ' . htmlspecialchars($item['name']) . ' không đủ số lượng tồn kho!</div>';
        }
    }
    
    if (!$stock_valid) {
        include 'footer.php';
        exit;
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    try {
        // Thêm đơn hàng
        $tongThanhToan = $total + $shipping_fee - $discount;
        $maKhuyenMai = $selected_promo ? $selected_promo['MaKhuyenMai'] : null;
        $stmt = $conn->prepare("
            INSERT INTO DonHang (MaNguoiDung, MaDiaChi, MaKhuyenMai, MaPhuongThuc, TongTienHang, TienGiamGia, PhiVanChuyen, TongThanhToan, GhiChu, TrangThai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ChoXacNhan')
        ");
        $stmt->bind_param('iiiiiidds', $maNguoiDung, $maDiaChi, $maKhuyenMai, $maPhuongThuc, $total, $discount, $shipping_fee, $tongThanhToan, $ghiChu);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi thêm đơn hàng: " . $stmt->error);
        }
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
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm chi tiết đơn hàng: " . $stmt->error);
            }
            
            // Cập nhật số lượng laptop
            $stmt_update = $conn->prepare("UPDATE Laptop SET SoLuong = SoLuong - ? WHERE MaLaptop = ?");
            $stmt_update->bind_param('ii', $item['quantity'], $maLaptop);
            if (!$stmt_update->execute()) {
                throw new Exception("Lỗi khi cập nhật số lượng laptop: " . $stmt_update->error);
            }
            $stmt_update->close();
        }
        $stmt->close();
        
        // Thêm lịch sử đơn hàng
        $stmt = $conn->prepare("
            INSERT INTO LichSuDonHang (MaDonHang, TrangThaiMoi, GhiChu, NguoiCapNhat)
            VALUES (?, 'ChoXacNhan', 'Đơn hàng được tạo', ?)
        ");
        $stmt->bind_param('ii', $maDonHang, $maNguoiDung);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi thêm lịch sử đơn hàng: " . $stmt->error);
        }
        $stmt->close();
        
        // Cập nhật số lượng khuyến mãi nếu có
        if ($selected_promo) {
            $stmt = $conn->prepare("UPDATE KhuyenMai SET SoLuong = SoLuong - 1 WHERE MaKhuyenMai = ? AND SoLuong > 0");
            $stmt->bind_param('i', $selected_promo['MaKhuyenMai']);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi cập nhật số lượng khuyến mãi: " . $stmt->error);
            }
            $stmt->close();
        }
        
        // Xóa giỏ hàng
        $stmt = $conn->prepare("DELETE FROM GioHang WHERE MaNguoiDung = ?");
        $stmt->bind_param('i', $maNguoiDung);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi xóa giỏ hàng: " . $stmt->error);
        }
        $stmt->close();
        unset($_SESSION['cart']);
        
        $conn->commit();
        
        // Lưu MaDonHang vào session để sử dụng ở QRPayment.php
        $_SESSION['order_id'] = $maDonHang;
        
        // Chuyển hướng dựa trên phương thức thanh toán
        $stmt = $conn->prepare("SELECT TenPhuongThuc FROM PhuongThucThanhToan WHERE MaPhuongThuc = ?");
        $stmt->bind_param('i', $maPhuongThuc);
        $stmt->execute();
        $method = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($method['TenPhuongThuc'] === 'Chuyển khoản') {
            header('Location: QRPayment.php');
        } else {
            header('Location: Invoice.php?order_id=' . $maDonHang);
        }
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo '<div class="error-message">Lỗi khi đặt hàng: ' . htmlspecialchars($e->getMessage()) . '</div>';
        include 'footer.php';
        exit;
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
                    <div class="form-section payment-section">
                        <h3>Phương thức thanh toán</h3>
                        <select name="payment_method" required>
                            <?php foreach ($phuongThuc as $pt): ?>
                                <option value="<?php echo $pt['MaPhuongThuc']; ?>">
                                    <?php echo htmlspecialchars($pt['TenPhuongThuc']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-section promotion-section">
                        <h3>Chọn khuyến mãi</h3>
                        <select name="promo_code">
                            <option value="">Không sử dụng khuyến mãi</option>
                            <?php foreach ($khuyenMai as $km): ?>
                                <option value="<?php echo $km['MaKhuyenMai']; ?>">
                                    <?php echo htmlspecialchars($km['TenChuongTrinh']); ?> (Giảm <?php echo $km['PhanTramGiam']; ?>%, tối đa <?php echo formatPrice($km['GiamToiDa']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-section order-summary-section">
                        <h3>Tóm tắt đơn hàng</h3>
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th class="product-column">Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Khuyến mãi</th>
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
                                        <td><?php echo $item['promo_name'] ? htmlspecialchars($item['promo_name']) : '-'; ?></td>
                                        <td><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="summary-details">
                            <p><span>Tạm tính:</span> <?php echo formatPrice($total); ?></p>
                            <?php if ($discount > 0): ?>
                                <p><span>Giảm giá (<?php echo $selected_promo ? htmlspecialchars($selected_promo['TenChuongTrinh']) : 'Sản phẩm'; ?>):</span> <?php echo formatPrice($discount); ?></p>
                            <?php endif; ?>
                            <p><span>Phí vận chuyển:</span> <?php echo formatPrice($shipping_fee); ?></p>
                            <p class="total"><span>Tổng thanh toán:</span> <?php echo formatPrice($total + $shipping_fee - $discount); ?></p>
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

<style>
    .promotion-section select {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>

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