<?php
include 'header.php';
include 'assets/db.php';
require_once 'libs/PHPMailer/PHPMailer.php';
require_once 'libs/PHPMailer/SMTP.php';
require_once 'libs/PHPMailer/Exception.php';
require_once 'assets/mail_information.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = '';
$success = '';

function generateVerificationCode() {
    return sprintf("%06d", mt_rand(0, 999999));
}

function sendVerificationEmail($email, $name, $code) {
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
        $mail->Subject = 'Password Reset Verification Code';
        $mail->Body = "
            <p>Xin chào $name,</p>
            <p>Mã xác minh để đặt lại mật khẩu của bạn là: <b>$code</b></p>
            <p>Mã này có hiệu lực trong 30 phút.</p>
            <p>Trân trọng,<br>Laptop Store</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Lỗi gửi email: {$mail->ErrorInfo}";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot_password'])) {
    $email = trim($_POST['email']);

    if ($conn->connect_error) {
        $error = 'Lỗi kết nối cơ sở dữ liệu: ' . $conn->connect_error;
    } else {
        $stmt = $conn->prepare("
            SELECT MaNguoiDung, HoTen 
            FROM NguoiDung 
            WHERE Email = ? AND TrangThai = TRUE
        ");
        if (!$stmt) {
            $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user) {
                $code = generateVerificationCode();
                $expiresAt = time() + 30 * 60; // 30 minutes from now

                // Store in session
                $_SESSION['reset_password_code'] = $code;
                $_SESSION['reset_password_expires'] = $expiresAt;
                $_SESSION['reset_password_user_id'] = $user['MaNguoiDung'];

                $emailResult = sendVerificationEmail($email, $user['HoTen'], $code);
                if ($emailResult !== true) {
                    $error = $emailResult;
                } else {
                    $success = 'Mã xác minh đã được gửi đến email của bạn.';
                    header('Location: verify_reset_code.php');
                    exit;
                }
            } else {
                $error = 'Email không tồn tại hoặc tài khoản bị khóa.';
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
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h2>Quên mật khẩu</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="forgot_password" value="1">
            <input type="email" name="email" placeholder="Nhập email của bạn" required>
            <button type="submit">Gửi mã xác nhận</button>
            <p><a href="login.php">Quay lại đăng nhập</a></p>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
include 'footer.php';
?>