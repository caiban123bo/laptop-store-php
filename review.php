<?php
include 'header.php';
include 'assets/db.php';

// Bật lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

$maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$laptop_id = isset($_GET['laptop_id']) ? intval($_GET['laptop_id']) : 0;

$error = '';
$success = '';

if (!$maNguoiDung || !$order_id || !$laptop_id) {
    $error = 'Thông tin không hợp lệ!';
} else {
    // Lấy thông tin sản phẩm để hiển thị
    $stmt = $conn->prepare("SELECT l.TenLaptop, h.TenHang, ha.DuongDan AS HinhAnh 
                            FROM Laptop l 
                            JOIN Hang h ON l.MaHang = h.MaHang 
                            LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE 
                            WHERE l.MaLaptop = ?");
    $stmt->bind_param('i', $laptop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $diem = intval($_POST['rating']);
        $noiDung = trim($_POST['content']);
        $tieuDe = "Đánh giá của khách hàng ($maNguoiDung)";

        // Debug: Kiểm tra dữ liệu đầu vào
        error_log("Dữ liệu POST: rating=$diem, title=$tieuDe, content=$noiDung");

        if ($diem < 1 || $diem > 5) {
            $error = 'Điểm đánh giá phải từ 1 đến 5!';
        } elseif (empty($noiDung)) {
            $error = 'Vui lòng điền nội dung đánh giá!';
        } else {
            $stmt = $conn->prepare("INSERT INTO DanhGia (MaNguoiDung, MaLaptop, MaDonHang, DiemDanhGia, TieuDe, NoiDung) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                $error = 'Lỗi chuẩn bị truy vấn: ' . $conn->error;
            } else {
                $stmt->bind_param('iiisis', $maNguoiDung, $laptop_id, $order_id, $diem, $tieuDe, $noiDung);
                if ($stmt->execute()) {
                    $success = 'Đánh giá của bạn đã được gửi thành công! Cảm ơn bạn.';
                } else {
                    $error = 'Lỗi khi thực thi truy vấn: ' . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<div class="review-container">
    <div class="review-card">
        <h2>Đánh giá sản phẩm</h2>
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($product): ?>
            <div class="product-preview">
                <img src="<?php echo htmlspecialchars($product['HinhAnh'] ?: 'assets/images/default.png'); ?>" 
                     alt="<?php echo htmlspecialchars($product['TenHang'] . ' ' . $product['TenLaptop']); ?>">
                <h3><?php echo htmlspecialchars($product['TenHang'] . ' ' . $product['TenLaptop']); ?></h3>
            </div>
            <form method="POST" class="review-form">
                <div class="form-group">
                    <label for="rating">Điểm đánh giá (1-5)</label>
                    <select name="rating" id="rating" required>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?> sao</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tiêu đề đánh giá</label>
                    <p><strong>Đánh giá của khách hàng (<?php echo $maNguoiDung; ?>)</strong></p>
                </div>
                <div class="form-group">
                    <label for="content">Nội dung đánh giá</label>
                    <textarea name="content" id="content" placeholder="Viết nhận xét của bạn..." required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>
                <button type="submit" class="submit-btn">Gửi đánh giá</button>
            </form>
        <?php else: ?>
            <div class="error-message">Sản phẩm không tồn tại!</div>
        <?php endif; ?>
    </div>
</div>

<style>
.review-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
}

.review-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-bottom: 20px;
}

.review-card h2 {
    color: #28a745;
    text-align: center;
    margin-bottom: 20px;
}

.product-preview {
    text-align: center;
    margin-bottom: 20px;
}

.product-preview img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.product-preview h3 {
    margin: 10px 0;
    color: #333;
    font-size: 1.2em;
}

.review-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
}

.form-group input,
.form-group select,
.form-group textarea {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1em;
    transition: border-color 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #28a745;
    outline: none;
}

.form-group textarea {
    height: 150px;
    resize: vertical;
}

.submit-btn {
    padding: 12px;
    background-color: #28a745;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s;
}

.submit-btn:hover {
    background-color: #218838;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 15px;
    text-align: center;
}

@media (max-width: 600px) {
    .review-card {
        padding: 15px;
    }
    .product-preview img {
        width: 100px;
        height: 100px;
    }
    .submit-btn {
        padding: 10px;
    }
}
</style>

<?php
$conn->close();
include 'footer.php';
?>
