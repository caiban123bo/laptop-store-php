<?php
include 'header.php';
include 'assets/db.php';

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
    }, 5000);

    // Hàm thêm sản phẩm vào giỏ hàng
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
            if (data.success) {
                alert('Thêm vào giỏ hàng thành công!');
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể thêm vào giỏ hàng'));
            }
        })
        .catch(error => {
            alert('Lỗi kết nối: ' + error.message);
        });
    }
</script>
<?php
// Initialize query components
$where_clauses = ["l.TrangThai = 'ConHang'"];
$params = [];
$types = '';

// Category filter
if (isset($_GET['category']) && !in_array('all', $_GET['category'])) {
    $categories = array_map('intval', $_GET['category']);
    $placeholders = implode(',', array_fill(0, count($categories), '?'));
    $where_clauses[] = "l.MaDanhMuc IN ($placeholders)";
    $params = array_merge($params, $categories);
    $types .= str_repeat('i', count($categories));
}

// Brand filter
if (isset($_GET['brand']) && !empty($_GET['brand'])) {
    $brands = array_map('intval', $_GET['brand']);
    $placeholders = implode(',', array_fill(0, count($brands), '?'));
    $where_clauses[] = "l.MaHang IN ($placeholders)";
    $params = array_merge($params, $brands);
    $types .= str_repeat('i', count($brands));
}

// Price filter
if (isset($_GET['price']) && $_GET['price'] !== 'all') {
    switch ($_GET['price']) {
        case 'under10':
            $where_clauses[] = "l.GiaBan < 10000000";
            break;
        case '10to20':
            $where_clauses[] = "l.GiaBan BETWEEN 10000000 AND 20000000";
            break;
        case '20to30':
            $where_clauses[] = "l.GiaBan BETWEEN 20000001 AND 30000000";
            break;
        case 'over30':
            $where_clauses[] = "l.GiaBan > 30000000";
            break;
    }
}

// CPU type filter
if (isset($_GET['cpu_type']) && !empty($_GET['cpu_type'])) {
    $cpu_types = array_map([$conn, 'real_escape_string'], $_GET['cpu_type']);
    $placeholders = implode(',', array_fill(0, count($cpu_types), '?'));
    $where_clauses[] = "t.Dong IN ($placeholders)";
    $params = array_merge($params, $cpu_types);
    $types .= str_repeat('s', count($cpu_types));
}

// CPU generation filter
if (isset($_GET['generation']) && !empty($_GET['generation'])) {
    $generations = array_map([$conn, 'real_escape_string'], $_GET['generation']);
    $placeholders = implode(',', array_fill(0, count($generations), '?'));
    $where_clauses[] = "t.TheHe IN ($placeholders)";
    $params = array_merge($params, $generations);
    $types .= str_repeat('s', count($generations));
}

// Architecture filter
if (isset($_GET['architecture']) && !empty($_GET['architecture'])) {
    $architectures = array_map([$conn, 'real_escape_string'], $_GET['architecture']);
    $placeholders = implode(',', array_fill(0, count($architectures), '?'));
    $where_clauses[] = "t.KienTruc IN ($placeholders)";
    $params = array_merge($params, $architectures);
    $types .= str_repeat('s', count($architectures));
}

// RAM filter
if (isset($_GET['ram']) && !empty($_GET['ram'])) {
    $where_clauses[] = "t.RAM = ?";
    $params[] = $_GET['ram'];
    $types .= 's';
}

// Storage filter
if (isset($_GET['storage']) && !empty($_GET['storage'])) {
    $where_clauses[] = "t.OCung = ?";
    $params[] = $_GET['storage'];
    $types .= 's';
}

// Graphics filter
if (isset($_GET['graphics']) && !empty($_GET['graphics'])) {
    $where_clauses[] = "t.CardDoHoa = ?";
    $params[] = $_GET['graphics'];
    $types .= 's';
}

// Display filter
if (isset($_GET['display']) && !empty($_GET['display'])) {
    $where_clauses[] = "t.ManHinh = ?";
    $params[] = $_GET['display'];
    $types .= 's';
}

// OS filter
if (isset($_GET['os']) && !empty($_GET['os'])) {
    $where_clauses[] = "t.HeDieuHanh = ?";
    $params[] = $_GET['os'];
    $types .= 's';
}

// Build and execute query
$where = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
$query = "
    SELECT l.MaLaptop, l.TenLaptop, l.GiaBan, l.SoLuong, h.TenHang, d.TenDanhMuc, ha.DuongDan AS HinhAnh
    FROM Laptop l
    JOIN Hang h ON l.MaHang = h.MaHang
    JOIN DanhMuc d ON l.MaDanhMuc = d.MaDanhMuc
    JOIN ThongSoKyThuat t ON l.MaThongSo = t.MaThongSo
    LEFT JOIN HinhAnh ha ON l.MaLaptop = ha.MaLaptop AND ha.MacDinh = TRUE
    $where
    ORDER BY l.NgayCapNhat DESC
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$laptops = $stmt->get_result();

// Function to format price in VND
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<!-- Main Content -->
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="product-grid">
        <?php if ($laptops->num_rows === 0): ?>
            <p>Không có sản phẩm nào.</p>
        <?php else: ?>
            <?php while ($laptop = $laptops->fetch_assoc()): ?>
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
    height: 300px
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

.add-to-cart, .view-detail {
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
$laptops->free();
$stmt->close();
$conn->close();
include 'footer.php';
?>