<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$maNguoiDung = intval($_SESSION['user_id']);

// Kiểm tra order_id
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    echo '<div class="error-message">Không tìm thấy đơn hàng!</div>';
    include 'footer.php';
    exit;
}
$maDonHang = intval($_GET['order_id']);

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT dh.MaDonHang, dh.NgayDat, dh.TongTienHang, dh.TienGiamGia, dh.PhiVanChuyen, dh.TongThanhToan, 
           dh.GhiChu, dh.TrangThai, dc.HoTen, dc.SoDienThoai, dc.DiaChi, dc.PhuongXa, dc.QuanHuyen, dc.TinhThanh,
           km.TenChuongTrinh, pttt.TenPhuongThuc
    FROM DonHang dh
    JOIN DiaChi dc ON dh.MaDiaChi = dc.MaDiaChi
    LEFT JOIN KhuyenMai km ON dh.MaKhuyenMai = km.MaKhuyenMai
    JOIN PhuongThucThanhToan pttt ON dh.MaPhuongThuc = pttt.MaPhuongThuc
    WHERE dh.MaDonHang = ? AND dh.MaNguoiDung = ?
");
$stmt->bind_param('ii', $maDonHang, $maNguoiDung);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo '<div class="error-message">Không tìm thấy đơn hàng hoặc bạn không có quyền xem!</div>';
    include 'footer.php';
    exit;
}

// Lấy chi tiết đơn hàng
$order_items = [];
$stmt = $conn->prepare("
    SELECT ctdh.MaLaptop, ctdh.SoLuong, ctdh.DonGia, ctdh.ThanhTien, l.TenLaptop, h.TenHang
    FROM ChiTietDonHang ctdh
    JOIN Laptop l ON ctdh.MaLaptop = l.MaLaptop
    JOIN Hang h ON l.MaHang = h.MaHang
    WHERE ctdh.MaDonHang = ?
");
$stmt->bind_param('i', $maDonHang);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $order_items[] = $row;
}
$stmt->close();
?>

<div class="invoice-container">
    <h2>Hóa đơn đơn hàng #<?php echo $maDonHang; ?></h2>
    <div class="invoice-wrapper">
        <div class="invoice-details">
            <h3>Thông tin đơn hàng</h3>
            <p><strong>Mã đơn hàng:</strong> <?php echo $maDonHang; ?></p>
            <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['NgayDat'])); ?></p>
            <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['TrangThai']); ?></p>
            <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['TenPhuongThuc']); ?></p>
            <?php if ($order['GhiChu']): ?>
                <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['GhiChu']); ?></p>
            <?php endif; ?>
        </div>
        <div class="invoice-address">
            <h3>Địa chỉ giao hàng</h3>
            <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['HoTen']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['SoDienThoai']); ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['DiaChi'] . ', ' . $order['PhuongXa'] . ', ' . $order['QuanHuyen'] . ', ' . $order['TinhThanh']); ?></p>
        </div>
        <div class="invoice-items">
            <h3>Chi tiết đơn hàng</h3>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th class="product-column">Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Đơn giá</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td class="product-info"><?php echo htmlspecialchars($item['TenHang'] . ' ' . $item['TenLaptop']); ?></td>
                            <td><?php echo $item['SoLuong']; ?></td>
                            <td><?php echo formatPrice($item['DonGia']); ?></td>
                            <td><?php echo formatPrice($item['ThanhTien']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="invoice-summary">
            <h3>Tổng cộng</h3>
            <p><span>Tạm tính:</span> <?php echo formatPrice($order['TongTienHang']); ?></p>
            <?php if ($order['TienGiamGia'] > 0): ?>
                <p><span>Giảm giá (<?php echo htmlspecialchars($order['TenChuongTrinh'] ?: 'Sản phẩm'); ?>):</span> <?php echo formatPrice($order['TienGiamGia']); ?></p>
            <?php endif; ?>
            <p><span>Phí vận chuyển:</span> <?php echo formatPrice($order['PhiVanChuyen']); ?></p>
            <p class="total"><span>Tổng thanh toán:</span> <?php echo formatPrice($order['TongThanhToan']); ?></p>
        </div>
        <div class="invoice-actions">
            <a href="index.php"><button class="continue-shopping">Tiếp tục mua sắm</button></a>
        </div>
    </div>
</div>

<style>
.invoice-container {
    max-width: 1000px;
    margin: 20px auto;
    padding: 20px;
}
.invoice-wrapper {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.invoice-details, .invoice-address, .invoice-items, .invoice-summary {
    margin-bottom: 20px;
}
.invoice-details p, .invoice-address p, .invoice-summary p {
    display: flex;
    justify-content: space-between;
    margin: 10px 0;
}
.invoice-table {
    width: 100%;
    border-collapse: collapse;
}
.invoice-table th, .invoice-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}
.invoice-table th {
    background: #f4f4f4;
}
.invoice-table .product-column {
    width: 50%;
}
.invoice-summary .total {
    font-weight: bold;
    font-size: 1.2em;
}
.invoice-actions {
    text-align: center;
}
.continue-shopping {
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.continue-shopping:hover {
    background: #0056b3;
}
</style>

<?php
$conn->close();
include 'footer.php';
?>