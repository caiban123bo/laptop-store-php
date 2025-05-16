<?php
session_start();
include 'assets/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    // Bật lỗi để debug
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    if (!$maNguoiDung) {
        $response['message'] = 'Vui lòng đăng nhập để hủy đơn hàng!';
        echo json_encode($response);
        exit;
    }

    if (!isset($_POST['order_id']) || !is_numeric($_POST['order_id'])) {
        $response['message'] = 'Mã đơn hàng không hợp lệ!';
        echo json_encode($response);
        exit;
    }

    $maDonHang = intval($_POST['order_id']);
    error_log("Hủy đơn hàng: MaDonHang=$maDonHang, MaNguoiDung=$maNguoiDung");

    // Bắt đầu giao dịch để đảm bảo tính toàn vẹn dữ liệu
    $conn->begin_transaction();

    // Kiểm tra quyền hủy
    $stmt = $conn->prepare("SELECT TrangThai FROM DonHang WHERE MaDonHang = ? AND MaNguoiDung = ?");
    if ($stmt === false) {
        throw new Exception('Lỗi chuẩn bị truy vấn: ' . $conn->error);
    }
    $stmt->bind_param('ii', $maDonHang, $maNguoiDung);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $response['message'] = 'Đơn hàng không tồn tại hoặc không thuộc về bạn!';
        echo json_encode($response);
        $conn->rollback();
        exit;
    }
    $order = $result->fetch_assoc();
    $stmt->close();

    // Kiểm tra trạng thái cho phép hủy: ChoXacNhan, DaXacNhan, DangGiao
    $allowedStatuses = ['ChoXacNhan', 'DaXacNhan', 'DangGiao'];
    if (!in_array($order['TrangThai'], $allowedStatuses)) {
        $response['message'] = 'Chỉ có thể hủy đơn hàng khi trạng thái là Chờ Xác Nhận, Đã Xác Nhận hoặc Đang Giao!';
        echo json_encode($response);
        $conn->rollback();
        exit;
    }

    // Cập nhật trạng thái đơn hàng thành 'DaHuy'
    $lyDoHuy = 'Hủy bởi khách hàng';
    $stmt = $conn->prepare("UPDATE DonHang SET TrangThai = 'DaHuy', LyDoHuy = ? WHERE MaDonHang = ?");
    if ($stmt === false) {
        throw new Exception('Lỗi chuẩn bị truy vấn UPDATE: ' . $conn->error);
    }
    $stmt->bind_param('si', $lyDoHuy, $maDonHang);
    if (!$stmt->execute()) {
        throw new Exception('Lỗi thực thi truy vấn UPDATE: ' . $stmt->error);
    }
    $stmt->close();

    // Ghi lịch sử trạng thái đơn hàng
    $stmt = $conn->prepare("INSERT INTO LichSuDonHang (MaDonHang, TrangThaiCu, TrangThaiMoi, GhiChu, NguoiCapNhat) VALUES (?, ?, ?, ?, ?)");
    if ($stmt === false) {
        throw new Exception('Lỗi chuẩn bị truy vấn INSERT LichSuDonHang: ' . $conn->error);
    }
    $trangThaiCu = $order['TrangThai'];
    $trangThaiMoi = 'DaHuy';
    $stmt->bind_param('isssi', $maDonHang, $trangThaiCu, $trangThaiMoi, $lyDoHuy, $maNguoiDung);
    if (!$stmt->execute()) {
        throw new Exception('Lỗi thực thi truy vấn INSERT LichSuDonHang: ' . $stmt->error);
    }
    $stmt->close();

    // Commit giao dịch
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Đơn hàng đã được hủy thành công!';
} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Lỗi server: ' . $e->getMessage();
    error_log('Lỗi hủy đơn hàng: ' . $e->getMessage());
} finally {
    echo json_encode($response);
}

$conn->close();
?>