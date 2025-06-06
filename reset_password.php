<?php
include 'header.php';
include 'assets/db.php';

$error = '';
$success = '';

if (!isset($_SESSION['reset_password_user_id']) || !isset($_SESSION['reset_password_code'])) {
    header('Location: forgot_password.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($newPassword !== $confirmPassword) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $stmt = $conn->prepare("
            UPDATE NguoiDung 
            SET MatKhau = ?
            WHERE MaNguoiDung = ?
        ");
        if (!$stmt) {
            $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
        } else {
            $stmt->bind_param('si', $newPassword, $_SESSION['reset_password_user_id']);
            if ($stmt->execute()) {
                unset($_SESSION['reset_password_code']);
                unset($_SESSION['reset_password_user_id']);
                unset($_SESSION['reset_password_expires']);
                $success = 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.';
                header('Location: login.php');
                exit;
            } else {
                $error = 'Lỗi khi cập nhật mật khẩu: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Đặt lại mật khẩu</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="reset_password" value="1">
            <input type="password" name="new_password" placeholder="Mật khẩu mới" required>
            <input type="password" name="confirm_password" placeholder="Xác nhận mật khẩu" required>
            <button type="submit">Đặt lại mật khẩu</button>
            <p><a href="login.php">Quay lại đăng nhập</a></p>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
include 'footer.php';
?>