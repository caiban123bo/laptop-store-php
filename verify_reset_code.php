<?php
include 'header.php';
include 'assets/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_reset_code'])) {
    $code = trim($_POST['code']);

    if (isset($_SESSION['reset_password_code']) && isset($_SESSION['reset_password_expires'])) {
        if ($_SESSION['reset_password_expires'] > time() && $_SESSION['reset_password_code'] === $code) {
            header('Location: reset_password.php');
            exit;
        } else {
            $error = 'Mã xác minh không đúng hoặc đã hết hạn.';
        }
    } else {
        $error = 'Không tìm thấy mã xác minh. Vui lòng thử lại.';
        header('Location: forgot_password.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác minh mã đặt lại mật khẩu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Xác minh mã</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="verify_reset_code" value="1">
            <input type="text" name="code" placeholder="Nhập mã xác minh (6 chữ số)" required pattern="\d{6}">
            <button type="submit">Xác minh</button>
            <p><a href="forgot_password.php">Gửi lại mã xác minh</a></p>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
include 'footer.php';
?>