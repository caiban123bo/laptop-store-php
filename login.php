<?php
error_reporting(0);
session_start();
include 'header.php';
include 'assets/db.php';
require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';
require_once 'libs/PHPMailer/Exception.php';
require_once 'assets/mail_information.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Kiểm tra nếu đã đăng nhập và không đang trong quá trình xác minh admin
if (isset($_SESSION['user_id']) && !isset($_SESSION['awaiting_admin_verification'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Function to generate a 6-digit code
function generateVerificationCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

// Function to send verification email
function sendVerificationEmail($email, $name, $code, $isPasswordReset = false) {
    global $mail_username, $mail_password;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $mail_username;
        $mail->Password = $mail_password;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom($mail_username, 'Laptop Store');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = $isPasswordReset ? 'Password Reset Verification Code' : 'Admin Verification Code';
        $mail->Body = $isPasswordReset ? 
            "<p>Xin chào $name,</p>
            <p>Mã xác minh để đặt lại mật khẩu của bạn là: <b>$code</b></p>
            <p>Mã này có hiệu lực trong 30 phút.</p>
            <p>Trân trọng,<br>Laptop Store</p>" :
            "<p>Xin chào $name,</p>
            <p>Mã xác minh của bạn để truy cập trang quản trị là: <b>$code</b></p>
            <p>Mã này có hiệu lực trong 30 phút.</p>
            <p>Trân trọng,<br>Laptop Store</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Lỗi gửi email: {$mail->ErrorInfo}";
    }
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $tenDangNhap = trim($_POST['username']);
    $matKhau = $_POST['password'];

    if ($conn->connect_error) {
        $error = 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("
            SELECT MaNguoiDung, MatKhau, VaiTro, Email, HoTen 
            FROM NguoiDung 
            WHERE TenDangNhap = ? AND TrangThai = TRUE
        ");
        if (!$stmt) {
            $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $tenDangNhap);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && $matKhau === $user['MatKhau']) {
                if ($user['VaiTro'] === 'QuanTri') {
                    // Admin user: generate and send verification code
                    $code = generateVerificationCode();
                    $expiresAt = time() + 30 * 60; // 30 minutes from now

                    // Store code in session
                    $_SESSION['admin_verification_code'] = $code;
                    $_SESSION['admin_verification_expires'] = $expiresAt;
                    $_SESSION['user_id_temp'] = $user['MaNguoiDung'];

                    // Send email
                    $emailResult = sendVerificationEmail($user['Email'], $user['HoTen'], $code);
                    if ($emailResult !== true) {
                        $error = $emailResult;
                    } else {
                        $_SESSION['awaiting_admin_verification'] = true;
                        $success = 'Một mã xác minh đã được gửi đến email của bạn. Vui lòng nhập mã để tiếp tục.';
                    }
                } else {
                    // Regular user: log in directly
                    $_SESSION['user_id'] = $user['MaNguoiDung'];
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng, hoặc tài khoản bị khóa.';
            }
            $stmt->close();
        }
    }
}

// Handle verification code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $code = trim($_POST['code']);
    $userId = $_SESSION['user_id_temp'] ?? 0;

    if (isset($_SESSION['admin_verification_code']) && isset($_SESSION['admin_verification_expires'])) {
        if ($_SESSION['admin_verification_expires'] > time() && $_SESSION['admin_verification_code'] === $code) {
            // Code is valid
            $_SESSION['user_id'] = $userId;
            unset($_SESSION['awaiting_admin_verification']);
            unset($_SESSION['user_id_temp']);
            unset($_SESSION['admin_verification_code']);
            unset($_SESSION['admin_verification_expires']);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Mã xác minh không đúng hoặc đã hết hạn.';
        }
    } else {
        $error = 'Không tìm thấy mã xác minh. Vui lòng đăng nhập lại.';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Đăng nhập</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!isset($_SESSION['awaiting_admin_verification'])): ?>
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <input type="text" name="username" placeholder="Tên đăng nhập" required>
                <input type="password" name="password" placeholder="Mật khẩu" required>
                <button type="submit">Đăng nhập</button>
                <p><a href="forgot_password.php">Quên mật khẩu?</a></p>
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            </form>
        <?php else: ?>
            <h3>Nhập mã xác minh</h3>
            <form method="POST">
                <input type="hidden" name="verify_code" value="1">
                <input type="text" name="code" placeholder="Mã xác minh (6 chữ số)" required pattern="\d{6}">
                <button type="submit">Xác minh</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
include 'footer.php';
?>