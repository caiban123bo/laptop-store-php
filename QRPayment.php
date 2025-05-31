<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Kiểm tra order_id trong session
if (!isset($_SESSION['order_id']) || !is_numeric($_SESSION['order_id'])) {
    echo '<div class="error-message">Không tìm thấy đơn hàng!</div>';
    include 'footer.php';
    exit;
}
$maDonHang = intval($_SESSION['order_id']);

// Xử lý xác nhận thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    // Chuyển hướng đến Invoice.php
    header('Location: Invoice.php?order_id=' . $maDonHang);
    unset($_SESSION['order_id']); // Xóa order_id khỏi session
    exit;
}
?>

<div class="qr-payment-container">
    <h2>Thanh toán bằng chuyển khoản</h2>
    <div class="qr-payment-wrapper">
        <p>Vui lòng quét mã QR dưới đây để thực hiện thanh toán:</p>
        <img src="assets/QR/img1.jpg" alt="QR Code" class="qr-code">
        <form method="POST">
            <button type="submit" name="confirm_payment" class="confirm-btn">Xác nhận thanh toán</button>
        </form>
    </div>
</div>

<style>
.qr-payment-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
}
.qr-payment-wrapper {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    text-align: center;
}
.qr-code {
    max-width: 100%;
    height: auto;
    margin: 20px 0;
}
.confirm-btn {
    padding: 10px 20px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}
.confirm-btn:hover {
    background: #0056b3;
}
</style>

<?php
$conn->close();
include 'footer.php';
?>