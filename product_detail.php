<?php
include 'header.php';
include 'assets/db.php';

// Lấy MaLaptop từ URL
$maLaptop = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Kiểm tra trạng thái yêu thích
$maNguoiDung = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
$isFavorite = false;
if ($maNguoiDung) {
    $stmt = $conn->prepare("SELECT MaYeuThich FROM YeuThich WHERE MaNguoiDung = ? AND MaLaptop = ?");
    $stmt->bind_param('ii', $maNguoiDung, $maLaptop);
    $stmt->execute();
    $isFavorite = $stmt->get_result()->num_rows > 0;
    $stmt->close();
}

// Truy vấn thông tin laptop và tất cả hình ảnh
$query = "
    SELECT l.MaLaptop, l.TenLaptop, l.GiaBan, l.MoTa, l.SoLuong, h.TenHang, d.TenDanhMuc,
           t.TenCPU, t.Dong, t.TheHe, t.KienTruc, t.RAM, t.OCung, t.CardDoHoa, t.ManHinh, t.HeDieuHanh,
           t.KhoiLuong, t.KichThuoc, t.Pin, t.Nam, ha.DuongDan AS HinhAnh
    FROM Laptop l
    JOIN Hang h ON l.MaHang = h.MaHang
    JOIN DanhMuc d ON l.MaDanhMuc = d.MaDanhMuc
    JOIN ThongSoKyThuat t ON l.MaThongSo = t.MaThongSo
    LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE
    WHERE l.MaLaptop = ? AND l.TrangThai = 'ConHang'
";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $maLaptop);
$stmt->execute();
$result = $stmt->get_result();
$laptop = $result->fetch_assoc();

// Lấy tất cả hình ảnh của sản phẩm
$imageQuery = "SELECT DuongDan FROM HinhAnh WHERE MaLaptop = ? ORDER BY MacDinh DESC, DuongDan";
$imageStmt = $conn->prepare($imageQuery);
$imageStmt->bind_param('i', $maLaptop);
$imageStmt->execute();
$images = $imageStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<div class="product-detail-container">
    <?php if ($laptop): ?>
        <div class="product-detail">
            <div class="product-gallery">
                <img src="<?php echo htmlspecialchars($laptop['HinhAnh'] ?: 'assets/images/default.png'); ?>" 
                     alt="<?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?>" 
                     class="main-image" id="mainImage">
                <div class="thumbnail-gallery">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image['DuongDan'] ?: 'assets/images/default.png'); ?>" 
                             class="thumbnail" 
                             onclick="changeImage(this.src)" 
                             alt="Thumbnail <?php echo $index + 1; ?>">
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?></h1>
                <div class="price-container">
                    <p class="price"><?php echo formatPrice($laptop['GiaBan']); ?></p>
                    <?php if ($maNguoiDung): ?>
                        <button class="favorite-btn" onclick="toggleFavorite(<?php echo $laptop['MaLaptop']; ?>, this)">
                            <?php echo $isFavorite ? 'Bỏ yêu thích' : 'Yêu thích'; ?>
                        </button>
                    <?php endif; ?>
                </div>
                <p class="category"><strong>Danh mục:</strong> <?php echo htmlspecialchars($laptop['TenDanhMuc']); ?></p>
                <p class="stock"><strong>Số lượng còn lại:</strong> <?php echo $laptop['SoLuong']; ?></p>
                <form id="add-to-cart-form" class="add-to-cart-form">
                    <div class="quantity-selector">
                        <label for="quantity">Số lượng:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $laptop['SoLuong']; ?>">
                    </div>
                    <input type="hidden" name="maLaptop" value="<?php echo $laptop['MaLaptop']; ?>">
                    <button type="submit" class="add-to-cart-btn">Thêm vào giỏ hàng</button>
                </form>
                <div class="product-description">
                    <h3>Mô tả sản phẩm</h3>
                    <p><?php echo nl2br(htmlspecialchars($laptop['MoTa'] ?: 'Không có mô tả.')); ?></p>
                </div>
            </div>
        </div>
        <div class="product-specs">
            <h3>Thông số kỹ thuật</h3>
            <div class="specs-frame">
                <table class="specs-table">
                    <tr><th>CPU</th><td><?php echo htmlspecialchars($laptop['TenCPU'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Dòng</th><td><?php echo htmlspecialchars($laptop['Dong'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Thế hệ</th><td><?php echo htmlspecialchars($laptop['TheHe'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Kiến trúc</th><td><?php echo htmlspecialchars($laptop['KienTruc'] ?: 'N/A'); ?></td></tr>
                    <tr><th>RAM</th><td><?php echo htmlspecialchars($laptop['RAM'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Ổ cứng</th><td><?php echo htmlspecialchars($laptop['OCung'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Card đồ họa</th><td><?php echo htmlspecialchars($laptop['CardDoHoa'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Màn hình</th><td><?php echo htmlspecialchars($laptop['ManHinh'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Hệ điều hành</th><td><?php echo htmlspecialchars($laptop['HeDieuHanh'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Khối lượng</th><td><?php echo htmlspecialchars($laptop['KhoiLuong'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Kích thước</th><td><?php echo htmlspecialchars($laptop['KichThuoc'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Pin</th><td><?php echo htmlspecialchars($laptop['Pin'] ?: 'N/A'); ?></td></tr>
                    <tr><th>Năm sản xuất</th><td><?php echo htmlspecialchars($laptop['Nam'] ?: 'N/A'); ?></td></tr>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="error-message">Sản phẩm không tồn tại hoặc đã hết hàng.</div>
    <?php endif; ?>
</div>

<script>
    // Chuyển đổi hình ảnh khi nhấp vào thumbnail
    function changeImage(src) {
        document.getElementById('mainImage').src = src;
    }

    // Tự động ẩn thông báo sau 3 giây
    setTimeout(() => {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(msg => msg.style.opacity = '0');
    }, 3000);

    // Hàm thêm/xóa sản phẩm yêu thích
    function toggleFavorite(maLaptop, element) {
        fetch('add_to_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'maLaptop=' + encodeURIComponent(maLaptop)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                element.textContent = data.isFavorite ? 'Bỏ yêu thích' : 'Yêu thích';
                const messageDiv = document.createElement('div');
                messageDiv.className = 'success-message';
                messageDiv.textContent = data.message;
                document.querySelector('.product-detail-container').prepend(messageDiv);
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                }, 3000);
            } else {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'error-message';
                messageDiv.textContent = 'Lỗi: ' + data.message;
                document.querySelector('.product-detail-container').prepend(messageDiv);
                setTimeout(() => {
                    messageDiv.style.opacity = '0';
                }, 3000);
            }
        })
        .catch(error => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Lỗi kết nối: ' + error.message;
            document.querySelector('.product-detail-container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        });
    }

    // Xử lý thêm vào giỏ hàng qua AJAX
    document.getElementById('add-to-cart-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const quantity = parseInt(document.getElementById('quantity').value);

        if (isNaN(quantity) || quantity < 1) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Số lượng không hợp lệ';
            document.querySelector('.product-detail-container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
            return;
        }

        fetch('add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.createElement('div');
            messageDiv.className = data.success ? 'success-message' : 'error-message';
            messageDiv.textContent = data.success ? 'Đã thêm ' + quantity + ' sản phẩm vào giỏ hàng!' : data.message;
            document.querySelector('.product-detail-container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        })
        .catch(error => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Lỗi kết nối: ' + error.message;
            document.querySelector('.product-detail-container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        });
    });
</script>

<style>
.product-detail-container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 20px;
}

.product-detail {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.product-gallery {
    flex: 1;
    min-width: 300px;
}

.main-image {
    width: 100%;
    height: 400px;
    object-fit: contain;
    border-radius: 8px;
}

.thumbnail-gallery {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    overflow-x: auto;
}

.thumbnail {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border: 1px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
}

.thumbnail:hover {
    border-color: #007bff;
}

.product-info {
    flex: 1;
    min-width: 300px;
}

.product-info h1 {
    font-size: 1.8em;
    margin-bottom: 10px;
}

.price-container {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.price {
    font-size: 1.5em;
    color: #e44d26;
    font-weight: bold;
    margin: 0;
}

.favorite-btn {
    background: #ff69b4;
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    transition: background 0.2s;
}

.favorite-btn:hover {
    background: #ff1493;
}

.category, .stock {
    margin: 5px 0;
}

.add-to-cart-form {
    margin: 20px 0;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.quantity-selector input {
    width: 60px;
    padding: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.add-to-cart-btn {
    background: #28a745;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 1em;
}

.add-to-cart-btn:hover {
    background: #218838;
}

.product-description {
    margin-top: 20px;
}

.product-specs {
    margin-top: 20px;
}

.specs-frame {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
}

.specs-table {
    width: 100%;
    border-collapse: collapse;
}

.specs-table th, .specs-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.specs-table th {
    width: 30%;
    background:rgb(0, 106, 213);
}

.success-message, .error-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    opacity: 1;
    transition: opacity 1s ease-out;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}
</style>

<?php
$stmt->close();
$imageStmt->close();
$conn->close();
include 'footer.php';
?>