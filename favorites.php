<?php
include 'header.php';
include 'assets/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$maNguoiDung = intval($_SESSION['user_id']);

// Hiển thị thông báo nếu có
$success_message = '';
if (isset($_SESSION['order_success'])) {
    $success_message = $_SESSION['order_success'];
    unset($_SESSION['order_success']);
}
if ($success_message): ?>
    <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>
<script>
    setTimeout(() => {
        const messages = document.querySelectorAll('.success-message, .error-message');
        messages.forEach(msg => msg.style.opacity = '0');
    }, 3000);

    function addToCart(maLaptop) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'maLaptop=' + encodeURIComponent(maLaptop)
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.createElement('div');
            messageDiv.className = data.success ? 'success-message' : 'error-message';
            messageDiv.textContent = data.success ? 'Thêm vào giỏ hàng thành công!' : 'Lỗi: ' + (data.message || 'Không thể thêm vào giỏ hàng');
            document.querySelector('.container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        })
        .catch(error => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Lỗi kết nối: ' + error.message;
            document.querySelector('.container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        });
    }

    function removeFavorite(maLaptop, element) {
        fetch('add_to_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'maLaptop=' + encodeURIComponent(maLaptop)
        })
        .then(response => response.json())
        .then(data => {
            const messageDiv = document.createElement('div');
            messageDiv.className = data.success ? 'success-message' : 'error-message';
            messageDiv.textContent = data.success ? data.message : 'Lỗi: ' + data.message;
            document.querySelector('.container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
            if (data.success && !data.isFavorite) {
                // Xóa card khỏi DOM
                element.closest('.product-card').remove();
                // Kiểm tra nếu không còn sản phẩm nào
                const productGrid = document.querySelector('.product-grid');
                if (productGrid.querySelectorAll('.product-card').length === 0) {
                    productGrid.innerHTML = '<p>Không có sản phẩm yêu thích nào.</p>';
                }
            }
        })
        .catch(error => {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'error-message';
            messageDiv.textContent = 'Lỗi kết nối: ' + error.message;
            document.querySelector('.container').prepend(messageDiv);
            setTimeout(() => {
                messageDiv.style.opacity = '0';
            }, 3000);
        });
    }
</script>
<?php
// Truy vấn danh sách sản phẩm yêu thích
$query = "
    SELECT l.MaLaptop, l.TenLaptop, l.GiaBan, l.SoLuong, h.TenHang, d.TenDanhMuc, ha.DuongDan AS HinhAnh
    FROM YeuThich y
    JOIN Laptop l ON y.MaLaptop = l.MaLaptop
    JOIN Hang h ON l.MaHang = h.MaHang
    JOIN DanhMuc d ON l.MaDanhMuc = d.MaDanhMuc
    JOIN ThongSoKyThuat t ON l.MaThongSo = t.MaThongSo
    LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE
    WHERE y.MaNguoiDung = ? AND l.TrangThai = 'ConHang'
    ORDER BY y.NgayThem DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param('i', $maNguoiDung);
$stmt->execute();
$favorites = $stmt->get_result();

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<!-- Main Content -->
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="product-grid">
        <h2>Sản phẩm yêu thích</h2>
        <?php if ($favorites->num_rows === 0): ?>
            <p>Không có sản phẩm yêu thích nào.</p>
        <?php else: ?>
            <?php while ($laptop = $favorites->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo htmlspecialchars($laptop['HinhAnh'] ?: 'assets/images/default.png'); ?>"
                            alt="<?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?>">
                    </div>
                    <h4 class="product-title"><?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?></h4>
                    <p class="price"><?php echo formatPrice($laptop['GiaBan']); ?></p>
                    <div class="actions">
                        <button class="add-to-cart" onclick="addToCart(<?php echo $laptop['MaLaptop']; ?>)">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                        <a href="product_detail.php?id=<?php echo $laptop['MaLaptop']; ?>" class="view-detail">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </a>
                        <button class="remove-favorite" onclick="removeFavorite(<?php echo $laptop['MaLaptop']; ?>, this)">
                            <i class="fas fa-trash"></i> Xóa yêu thích
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>
</div>

<style>
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
    padding: 20px;
}

.product-grid h2 {
    font-size: 1.5em;
    margin-bottom: 20px;
    color: #333;
}

.product-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    width: 250px;
    min-height: 380px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: transform 0.2s;
    height: 300px;
}

.product-card:hover {
    transform: translateY(-5px);
}

.product-image {
    height: 180px;
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    border-radius: 5px;
}

.product-title {
    font-size: 1.1em;
    margin: 10px 0;
    height: 50px;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.price {
    color: #e44d26;
    font-weight: bold;
    margin: 5px 0;
}

.actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}

.add-to-cart, .view-detail, .remove-favorite {
    flex: 1;
    padding: 8px;
    border-radius: 5px;
    font-size: 0.9em;
    cursor: pointer;
    transition: background 0.2s;
    text-align: center;
    text-decoration: none;
    min-width: 100px;
}

.add-to-cart {
    background: #28a745;
    color: #fff;
    border: none;
}

.add-to-cart:hover {
    background: #218838;
}

.view-detail {
    background: #007bff;
    color: #fff;
    border: none;
}

.view-detail:hover {
    background: #0056b3;
}

.remove-favorite {
    background: #dc3545;
    color: #fff;
    border: none;
}

.remove-favorite:hover {
    background: #c82333;
}

.success-message, .error-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    margin: 10px auto;
    border: 1px solid #c3e6cb;
    border-radius: 5px;
    max-width: 600px;
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
$favorites->free();
$stmt->close();
$conn->close();
include 'footer.php';
?>