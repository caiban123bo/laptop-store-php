<?php
include 'header.php';
include 'assets/db.php';

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
    SELECT l.TenLaptop, l.GiaBan, l.SoLuong, h.TenHang, h.HinhAnh, d.TenDanhMuc
    FROM Laptop l
    JOIN Hang h ON l.MaHang = h.MaHang
    JOIN DanhMuc d ON l.MaDanhMuc = d.MaDanhMuc
    JOIN ThongSoKyThuat t ON l.MaThongSo = t.MaThongSo
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
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}
?>

<!-- Main Content -->
<div class="container">
    <?php include 'sidebar.php'; ?>
    <main class="product-grid">
        <?php if ($laptops->num_rows === 0): ?>
            <p>No products available.</p>
        <?php else: ?>
            <?php while ($laptop = $laptops->fetch_assoc()): ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($laptop['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?>">
                    <h4><?php echo htmlspecialchars($laptop['TenHang'] . ' ' . $laptop['TenLaptop']); ?></h4>
                    <p class="price"><?php echo formatPrice($laptop['GiaBan']); ?></p>
                    <div class="actions">
                        <button class="add-to-cart">Giỏ hàng</button>
                        <button class="favorite">Yêu thích</button>
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