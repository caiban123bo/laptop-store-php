<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['username']);
    $matKhau = $_POST['password'];
    $email = trim($_POST['email']);
    $hoTen = trim($_POST['ho_ten']);
    $soDienThoai = trim($_POST['so_dien_thoai']);
    $diaChi = trim($_POST['dia_chi']);
    $tinhThanh = trim($_POST['tinh_thanh']);
    $quanHuyen = trim($_POST['quan_huyen']);
    $phuongXa = trim($_POST['phuong_xa']);
    $macDinh = isset($_POST['mac_dinh']) ? 1 : 0;

    // Kiểm tra dữ liệu
    if (empty($tenDangNhap) || empty($matKhau) || empty($email) || empty($hoTen) || 
        empty($soDienThoai) || empty($diaChi) || empty($tinhThanh) || 
        empty($quanHuyen) || empty($phuongXa)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } elseif (strlen($matKhau) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif (!preg_match('/^[0-9]{10,11}$/', $soDienThoai)) {
        $error = 'Số điện thoại không hợp lệ (10-11 chữ số).';
    } else {
        // Kiểm tra trùng lặp
        $stmt = $conn->prepare("SELECT MaNguoiDung FROM NguoiDung WHERE TenDangNhap = ? OR Email = ?");
        $stmt->bind_param('ss', $tenDangNhap, $email);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'Tên đăng nhập hoặc email đã được sử dụng.';
            $stmt->close();
        } else {
            $stmt->close();
            $conn->begin_transaction();
            try {
                // Thêm người dùng (lưu mật khẩu trực tiếp, không mã hóa)
                $stmt = $conn->prepare("
                    INSERT INTO NguoiDung (TenDangNhap, MatKhau, Email, HoTen, SoDienThoai, VaiTro, TrangThai)
                    VALUES (?, ?, ?, ?, ?, 'KhachHang', TRUE)
                ");
                $stmt->bind_param('sssss', $tenDangNhap, $matKhau, $email, $hoTen, $soDienThoai);
                $stmt->execute();
                $maNguoiDung = $conn->insert_id;
                $stmt->close();

                // Thêm địa chỉ
                $stmt = $conn->prepare("
                    INSERT INTO DiaChi (MaNguoiDung, HoTen, SoDienThoai, DiaChi, TinhThanh, QuanHuyen, PhuongXa, MacDinh)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('issssssi', $maNguoiDung, $hoTen, $soDienThoai, $diaChi, $tinhThanh, $quanHuyen, $phuongXa, $macDinh);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
                $success = 'Đăng ký thành công! Vui lòng đăng nhập để tiếp tục.';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Lỗi khi đăng ký: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<div class="register-container">
    <h2>Đăng ký tài khoản</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <h3>Thông tin tài khoản</h3>
        <input type="text" name="username" placeholder="Tên đăng nhập" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <input type="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        <input type="text" name="ho_ten" placeholder="Họ tên" required value="<?php echo isset($_POST['ho_ten']) ? htmlspecialchars($_POST['ho_ten']) : ''; ?>">
        <input type="text" name="so_dien_thoai" placeholder="Số điện thoại" required value="<?php echo isset($_POST['so_dien_thoai']) ? htmlspecialchars($_POST['so_dien_thoai']) : ''; ?>">
        
        <h3>Thông tin địa chỉ</h3>
        <input type="text" name="dia_chi" placeholder="Địa chỉ" required value="<?php echo isset($_POST['dia_chi']) ? htmlspecialchars($_POST['dia_chi']) : ''; ?>">
        <input type="text" name="tinh_thanh" placeholder="Tỉnh/Thành" required value="<?php echo isset($_POST['tinh_thanh']) ? htmlspecialchars($_POST['tinh_thanh']) : ''; ?>">
        <input type="text" name="quan_huyen" placeholder="Quận/Huyện" required value="<?php echo isset($_POST['quan_huyen']) ? htmlspecialchars($_POST['quan_huyen']) : ''; ?>">
        <input type="text" name="phuong_xa" placeholder="Phường/Xã" required value="<?php echo isset($_POST['phuong_xa']) ? htmlspecialchars($_POST['phuong_xa']) : ''; ?>">
        <label><input type="checkbox" name="mac_dinh" <?php echo isset($_POST['mac_dinh']) ? 'checked' : ''; ?>> Đặt làm địa chỉ mặc định</label>
        
        <button type="submit">Đăng ký</button>
        <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
    </form>
</div>

<?php
$conn->close();
include 'footer.php';
?>