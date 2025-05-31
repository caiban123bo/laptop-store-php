<?php
require 'sidebar.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';
require '../libs/PHPMailer/Exception.php';
require '../assets/mail_information.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle status update and email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_processed'])) {
    $opinionId = $_POST['opinion_id'];
    $customMessage = $_POST['custom_message'] ?? '';
    $stmt = $pdo->prepare("SELECT dg.*, nd.Email, nd.HoTen FROM DanhGia dg 
                           JOIN NguoiDung nd ON dg.MaNguoiDung = nd.MaNguoiDung 
                           WHERE dg.MaDanhGia = ?");
    $stmt->execute([$opinionId]);
    $feedback = $stmt->fetch();

    if ($feedback) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $mail_username;
            $mail->Password = $mail_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            $mail->setFrom($mail_username, 'Admin WebQuanAoNhom2');
            $mail->addAddress($feedback['Email'], $feedback['HoTen']);
            $mail->isHTML(true);
            $mail->Subject = 'Thank you for your feedback';
            $message = !empty($customMessage) ? $customMessage : 'Default thank you message...'; // Shortened for brevity
            $mail->Body = nl2br($message);
            $mail->send();

            $update = $pdo->prepare("UPDATE DanhGia SET TrangThai = 'DaPheDuyet' WHERE MaDanhGia = ?");
            $update->execute([$opinionId]);

            header("Location: user_opinions.php");
            exit;
        } catch (Exception $e) {
            echo "Email could not be sent. Error: {$mail->ErrorInfo}";
        }
    }
}

// Fetch all feedback
$stmt = $pdo->query("SELECT dg.*, nd.HoTen, nd.Email FROM DanhGia dg 
                     JOIN NguoiDung nd ON dg.MaNguoiDung = nd.MaNguoiDung 
                     ORDER BY NgayDanhGia DESC");
$opinions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ý kiến khách hàng</title>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>
<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <h1>Ý kiến khách hàng</h1>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ & tên</th>
                        <th>Email</th>
                        <th>Nội dung</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($opinions as $op): ?>
                    <tr>
                        <td><?= $op['MaDanhGia'] ?></td>
                        <td><?= htmlspecialchars($op['HoTen']) ?></td>
                        <td><?= htmlspecialchars($op['Email']) ?></td>
                        <td><?= htmlspecialchars($op['NoiDung']) ?></td>
                        <td><?= $op['NgayDanhGia'] ?></td>
                        <td><?= $op['TrangThai'] ?></td>
                        <td>
                            <?php if ($op['TrangThai'] === 'ChoPheDuyet'): ?>
                            <form method="post" style="display:inline-block;">
                                <input type="hidden" name="opinion_id" value="<?= $op['MaDanhGia'] ?>">
                                <textarea name="custom_message" placeholder="Optional custom email..."></textarea>
                                <button type="submit" name="mark_processed" class="btn">Mark as Processed & Send Email</button>
                            </form>
                            <?php else: ?>
                            Processed
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($opinions)): ?>
                    <tr>
                        <td colspan="7">No feedback available.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>