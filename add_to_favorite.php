<?php
session_start();
header('Content-Type: application/json');

include 'assets/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm sản phẩm yêu thích']);
    exit;
}

$maNguoiDung = intval($_SESSION['user_id']);
$maLaptop = isset($_POST['maLaptop']) ? intval($_POST['maLaptop']) : 0;

if ($maLaptop <= 0) {
    echo json_encode(['success' => false, 'message' => 'Mã laptop không hợp lệ']);
    exit;
}

// Kiểm tra xem sản phẩm có tồn tại không
$stmt = $conn->prepare("SELECT MaLaptop FROM Laptop WHERE MaLaptop = ? AND TrangThai = 'ConHang'");
$stmt->bind_param('i', $maLaptop);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã hết hàng']);
    $stmt->close();
    exit;
}
$stmt->close();

// Kiểm tra xem sản phẩm đã được yêu thích chưa
$stmt = $conn->prepare("SELECT MaYeuThich FROM YeuThich WHERE MaNguoiDung = ? AND MaLaptop = ?");
$stmt->bind_param('ii', $maNguoiDung, $maLaptop);
$stmt->execute();
$result = $stmt->get_result();
$isFavorite = $result->num_rows > 0;
$stmt->close();

if ($isFavorite) {
    // Xóa khỏi danh sách yêu thích
    $stmt = $conn->prepare("DELETE FROM YeuThich WHERE MaNguoiDung = ? AND MaLaptop = ?");
    $stmt->bind_param('ii', $maNguoiDung, $maLaptop);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'isFavorite' => false, 'message' => 'Đã xóa khỏi danh sách yêu thích']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa sản phẩm yêu thích']);
    }
} else {
    // Thêm vào danh sách yêu thích
    $stmt = $conn->prepare("INSERT INTO YeuThich (MaNguoiDung, MaLaptop) VALUES (?, ?)");
    $stmt->bind_param('ii', $maNguoiDung, $maLaptop);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'isFavorite' => true, 'message' => 'Đã thêm vào danh sách yêu thích']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm sản phẩm yêu thích']);
    }
}

$stmt->close();
$conn->close();
?>