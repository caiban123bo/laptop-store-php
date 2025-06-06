<?php
session_start();
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    include 'assets/db.php';

    if (!isset($_POST['maLaptop']) || !is_numeric($_POST['maLaptop'])) {
        $response['message'] = 'Mã sản phẩm không hợp lệ';
        echo json_encode($response);
        exit;
    }

    $maLaptop = intval($_POST['maLaptop']);
    $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    $maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    // Kiểm tra tồn tại sản phẩm
    $stmt = $conn->prepare("SELECT GiaBan, SoLuong FROM Laptop WHERE MaLaptop = ? AND TrangThai = 'ConHang'");
    $stmt->bind_param('i', $maLaptop);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $response['message'] = 'Sản phẩm không tồn tại hoặc đã hết hàng';
        echo json_encode($response);
        exit;
    }

    $laptop = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra số lượng tồn kho
    if ($quantity > $laptop['SoLuong']) {
        $response['message'] = 'Số lượng vượt quá tồn kho';
        echo json_encode($response);
        exit;
    }

    // Thêm vào giỏ hàng
    if ($maNguoiDung) {
        // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
        $stmt = $conn->prepare("SELECT SoLuong FROM GioHang WHERE MaNguoiDung = ? AND MaLaptop = ?");
        $stmt->bind_param('ii', $maNguoiDung, $maLaptop);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $newQuantity = $existing['SoLuong'] + $quantity;
            if ($newQuantity > $laptop['SoLuong']) {
                $response['message'] = 'Số lượng vượt quá tồn kho';
                echo json_encode($response);
                exit;
            }
            $stmt = $conn->prepare("UPDATE GioHang SET SoLuong = ? WHERE MaNguoiDung = ? AND MaLaptop = ?");
            $stmt->bind_param('iii', $newQuantity, $maNguoiDung, $maLaptop);
        } else {
            $stmt = $conn->prepare("INSERT INTO GioHang (MaNguoiDung, MaLaptop, SoLuong) VALUES (?, ?, ?)");
            $stmt->bind_param('iii', $maNguoiDung, $maLaptop, $quantity);
        }
        $stmt->execute();
        $stmt->close();
    } else {
        // Sử dụng session nếu chưa đăng nhập
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (isset($_SESSION['cart'][$maLaptop])) {
            $_SESSION['cart'][$maLaptop]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$maLaptop] = [
                'name' => '',
                'price' => $laptop['GiaBan'],
                'quantity' => $quantity,
                'image' => 'assets/images/default.png'
            ];
        }
    }

    $response['success'] = true;
    echo json_encode($response);
} catch (Exception $e) {
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
    echo json_encode($response);
}

$conn->close();
?>