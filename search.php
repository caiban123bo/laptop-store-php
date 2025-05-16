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

    function addToFavorite(maLaptop) {
        alert('Chức năng yêu thích chưa được triển khai. MaLaptop: ' + maLaptop);
    }
</script>
<?php
// Lấy từ khóa tìm kiếm
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Initialize query components
$where_clauses = ["l.TrangThai = 'ConHang'"];
$params = [];
$types = '';

// Thêm điều kiện tìm kiếm theo từ khóa
if ($keyword) {
    $searchTerm = "%" . $keyword . "%";
    $where_clauses[] = "(l.TenLaptop LIKE ? OR h.TenHang LIKE ? OR l.MoTa LIKE ?)";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    $types .= 'sss';
}

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
        <h2>Kết quả tìm kiếm cho: "<?php echo htmlspecialchars($keyword); ?>"</h2>
        <?php if (empty($keyword)): ?>
            <p>Vui lòng nhập từ khóa để tìm kiếm.</p>
        <?php elseif ($laptops->num_rows === 0): ?>
            <p>Không tìm thấy sản phẩm nào phù hợp.</p>
        <?php else: ?>
            <?php while ($laptop = $laptops->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($laptop['HinhAnh'] ?: 'assets/images/default.png'); ?>"
                        alt="<?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?>">
                    <h4><?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?></h4>
                    <p class="price"><?php echo formatPrice($laptop['GiaBan']); ?></p>
                    <div class="actions">
                        <button class="add-to-cart" onclick="addToCart(<?php echo $laptop['MaLaptop']; ?>)">Thêm vào giỏ hàng</button>
                        <a href="product_detail.php?id=<?php echo $laptop['MaLaptop']; ?>" class="view-detail">Xem chi tiết</a>
                        <button class="favorite" onclick="addToFavorite(<?php echo $laptop['MaLaptop']; ?>)">Yêu thích</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>
</div>

<?php
$laptops->free();
$stmt->close();
$conn->close();
include 'footer.php';
?>