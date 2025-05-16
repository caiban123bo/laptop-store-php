<?php
include 'header.php';
include 'assets/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$maNguoiDung = intval($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['ho_ten']);
    $soDienThoai = trim($_POST['so_dien_thoai']);
    $diaChi = trim($_POST['dia_chi']);
    $tinhThanh = trim($_POST['tinh_thanh']);
    $quanHuyen = trim($_POST['quan_huyen']);
    $phuongXa = trim($_POST['phuong_xa']);
    $macDinh = isset($_POST['mac_dinh']) ? 1 : 0;

    if ($macDinh) {
        $stmt = $conn->prepare("UPDATE DiaChi SET MacDinh = FALSE WHERE MaNguoiDung = ?");
        $stmt->bind_param('i', $maNguoiDung);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("
        INSERT INTO DiaChi (MaNguoiDung, HoTen, SoDienThoai, DiaChi, TinhThanh, QuanHuyen, PhuongXa, MacDinh)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('issssssi', $maNguoiDung, $hoTen, $soDienThoai, $diaChi, $tinhThanh, $quanHuyen, $phuongXa, $macDinh);
    $stmt->execute();
    $stmt->close();
    header('Location: CheckOut.php');
    exit;
}
?>
<div class="address-container">
    <h2>Thêm địa chỉ mới</h2>
    <form method="POST">
        <input type="text" name="ho_ten" placeholder="Họ tên" required>
        <input type="text" name="so_dien_thoai" placeholder="Số điện thoại" required>
        <input type="text" name="dia_chi" placeholder="Địa chỉ" required>
        <input type="text" name="tinh_thanh" placeholder="Tỉnh/Thành" required>
        <input type="text" name="quan_huyen" placeholder="Quận/Huyện" required>
        <input type="text" name="phuong_xa" placeholder="Phường/Xã" required>
        <label><input type="checkbox" name="mac_dinh"> Đặt làm địa chỉ mặc định</label>
        <button type="submit">Lưu địa chỉ</button>
    </form>
</div>
<?php include 'footer.php'; ?>