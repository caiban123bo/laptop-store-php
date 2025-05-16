<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra đăng nhập
$maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (!$maNguoiDung) {
    echo '<div class="error-message">Vui lòng đăng nhập để xem lịch sử đơn hàng!</div>';
    include 'footer.php';
    exit;
}

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Lấy danh sách đơn hàng của người dùng
$orders = [];
$query_orders = "
    SELECT dh.MaDonHang, dh.NgayDat, dh.TongThanhToan, dh.TrangThai, dh.GhiChu, 
           pttt.TenPhuongThuc, dc.DiaChi, dc.TinhThanh, dc.QuanHuyen, dc.PhuongXa
    FROM DonHang dh
    JOIN PhuongThucThanhToan pttt ON dh.MaPhuongThuc = pttt.MaPhuongThuc
    JOIN DiaChi dc ON dh.MaDiaChi = dc.MaDiaChi
    WHERE dh.MaNguoiDung = ?
    ORDER BY dh.NgayDat DESC
";
$stmt = $conn->prepare($query_orders);
$stmt->bind_param('i', $maNguoiDung);
$stmt->execute();
$result_orders = $stmt->get_result();
while ($row = $result_orders->fetch_assoc()) {
    $orders[$row['MaDonHang']] = $row;
}
$stmt->close();

// Lấy chi tiết đơn hàng
$order_details = [];
if (!empty($orders)) {
    $order_ids = array_keys($orders);
    $query_details = "
        SELECT ctdh.MaDonHang, ctdh.MaLaptop, ctdh.SoLuong, ctdh.DonGia, ctdh.ThanhTien,
               l.TenLaptop, h.TenHang, ha.DuongDan AS HinhAnh
        FROM ChiTietDonHang ctdh
        JOIN Laptop l ON ctdh.MaLaptop = l.MaLaptop
        JOIN Hang h ON l.MaHang = h.MaHang
        LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE
        WHERE ctdh.MaDonHang IN (" . implode(',', array_fill(0, count($order_ids), '?')) . ")
    ";
    $stmt = $conn->prepare($query_details);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $result_details = $stmt->get_result();
    while ($row = $result_details->fetch_assoc()) {
        $order_details[$row['MaDonHang']][] = $row;
    }
    $stmt->close();
}

// Lấy lịch sử trạng thái đơn hàng
$order_history = [];
if (!empty($orders)) {
    $order_ids = array_keys($orders);
    $query_history = "
        SELECT lsdh.MaDonHang, lsdh.TrangThaiCu, lsdh.TrangThaiMoi, lsdh.GhiChu, lsdh.NgayCapNhat, 
               nd.HoTen AS NguoiCapNhat
        FROM LichSuDonHang lsdh
        JOIN NguoiDung nd ON lsdh.NguoiCapNhat = nd.MaNguoiDung
        WHERE lsdh.MaDonHang IN (" . implode(',', array_fill(0, count($order_ids), '?')) . ")
        ORDER BY lsdh.NgayCapNhat ASC
    ";
    $stmt = $conn->prepare($query_history);
    $stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $stmt->execute();
    $result_history = $stmt->get_result();
    while ($row = $result_history->fetch_assoc()) {
        $order_history[$row['MaDonHang']][] = $row;
    }
    $stmt->close();
}
?>

<div class="order-history-container">
    <h2>Lịch sử đơn hàng</h2>
    <?php if (empty($orders)): ?>
        <div class="empty-history">
            <p>Bạn chưa có đơn hàng nào.</p>
            <a href="index.php"><button class="continue-shopping">Tiếp tục mua sắm</button></a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-item">
                <div class="order-header">
                    <h3>Đơn hàng #<?php echo $order['MaDonHang']; ?> - Ngày đặt: <?php echo $order['NgayDat']; ?></h3>
                    <p><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order['TrangThai']); ?></p>
                    <p><strong>Tổng thanh toán:</strong> <?php echo formatPrice($order['TongThanhToan']); ?></p>
                    <?php if ($order['TrangThai'] === 'ChoXacNhan'): ?>
                        <button class="cancel-order-btn" data-order-id="<?php echo $order['MaDonHang']; ?>">Hủy đơn hàng</button>
                    <?php elseif ($order['TrangThai'] === 'DaGiao'): ?>
                        <?php if (isset($order_details[$order['MaDonHang']])): ?>
                            <?php foreach ($order_details[$order['MaDonHang']] as $detail): ?>
                                <a href="product_detail.php?id=<?php echo $detail['MaLaptop']; ?>">
                                    <button class="buy-again-btn">Mua lại: <?php echo htmlspecialchars($detail['TenHang'] . ' ' . $detail['TenLaptop']); ?></button>
                                </a>
                                <a href="review.php?order_id=<?php echo $order['MaDonHang']; ?>&laptop_id=<?php echo $detail['MaLaptop']; ?>">
                                    <button class="review-btn">Đánh giá</button>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="order-details">
                    <h4>Chi tiết đơn hàng</h4>
                    <table class="order-details-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($order_details[$order['MaDonHang']])): ?>
                                <?php foreach ($order_details[$order['MaDonHang']] as $detail): ?>
                                    <tr>
                                        <td class="product-info">
                                            <img src="<?php echo htmlspecialchars($detail['HinhAnh'] ?: 'assets/images/default.png'); ?>" 
                                                 alt="<?php echo htmlspecialchars($detail['TenHang'] . ' ' . $detail['TenLaptop']); ?>">
                                            <span><?php echo htmlspecialchars($detail['TenHang'] . ' ' . $detail['TenLaptop']); ?></span>
                                        </td>
                                        <td><?php echo formatPrice($detail['DonGia']); ?></td>
                                        <td><?php echo $detail['SoLuong']; ?></td>
                                        <td><?php echo formatPrice($detail['ThanhTien']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="order-info">
                    <p><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order['TenPhuongThuc']); ?></p>
                    <p><strong>Địa chỉ giao hàng:</strong> 
                        <?php echo htmlspecialchars($order['DiaChi'] . ', ' . $order['PhuongXa'] . ', ' . $order['QuanHuyen'] . ', ' . $order['TinhThanh']); ?>
                    </p>
                    <?php if ($order['GhiChu']): ?>
                        <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['GhiChu']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="order-history">
                    <h4>Lịch sử trạng thái</h4>
                    <?php if (isset($order_history[$order['MaDonHang']])): ?>
                        <table class="order-history-table">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Trạng thái cũ</th>
                                    <th>Trạng thái mới</th>
                                    <th>Ghi chú</th>
                                    <th>Người cập nhật</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_history[$order['MaDonHang']] as $history): ?>
                                    <tr>
                                        <td><?php echo $history['NgayCapNhat']; ?></td>
                                        <td><?php echo htmlspecialchars($history['TrangThaiCu'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($history['TrangThaiMoi']); ?></td>
                                        <td><?php echo htmlspecialchars($history['GhiChu'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($history['NguoiCapNhat']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Chưa có cập nhật trạng thái.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
.order-history-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.order-item {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #fff;
}

.order-header {
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.order-header h3 {
    margin: 0;
    font-size: 1.2em;
}

.order-header button {
    margin-right: 10px;
    padding: 5px 10px;
    background-color: #dc3545;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.order-header button:hover {
    background-color: #c82333;
}

.order-header a button {
    background-color: #28a745;
    margin-right: 10px;
}

.order-header a button:hover {
    background-color: #218838;
}

.order-details-table, .order-history-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.order-details-table th, .order-details-table td,
.order-history-table th, .order-history-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

.order-details-table th, .order-history-table th {
    background-color: #f5f5f5;
}

.order-details-table .product-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.order-details-table img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 4px;
}

.order-info {
    margin-top: 15px;
}

.order-history {
    margin-top: 15px;
}

.empty-history {
    text-align: center;
    padding: 20px;
}

.continue-shopping {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}

.continue-shopping:hover {
    background-color: #0056b3;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cancelButtons = document.querySelectorAll('.cancel-order-btn');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.getAttribute('data-order-id');
            if (confirm('Bạn có chắc muốn hủy đơn hàng #' + orderId + '?')) {
                const data = new FormData();
                data.append('order_id', orderId);

                fetch('cancel_order.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => {
                    console.log('Phản hồi thô:', response);
                    return response.json();
                })
                .then(data => {
                    console.log('Dữ liệu JSON:', data);
                    if (data.success) {
                        alert('Đã hủy đơn hàng thành công!');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Lỗi fetch:', error);
                    alert('Lỗi kết nối: ' + error.message);
                });
            }
        });
    });
});
</script>

<?php
$conn->close();
include 'footer.php';
?>