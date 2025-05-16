<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhap = trim($_POST['username']);
    $matKhau = $_POST['password'];

    // Kiểm tra kết nối cơ sở dữ liệu
    if ($conn->connect_error) {
        $error = 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("SELECT MaNguoiDung, MatKhau FROM NguoiDung WHERE TenDangNhap = ? AND TrangThai = TRUE");
        if (!$stmt) {
            $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $tenDangNhap);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && $matKhau === $user['MatKhau']) {
                $_SESSION['user_id'] = $user['MaNguoiDung'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản bị khóa.';
            }
            $stmt->close();
        }
    }
}
?>

<div class="login-container">
    <h2>Đăng nhập</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Tên đăng nhập" required>
        <input type="password" name="password" placeholder="Mật khẩu" required>
        <button type="submit">Đăng nhập</button>
        <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
    </form>
</div>

<?php
$conn->close();
include 'footer.php';
?>