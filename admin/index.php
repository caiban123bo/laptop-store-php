<?php
session_start();
require_once '..\assets\db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Optional: fetch VaiTro once and cache it
if (!isset($_SESSION['vaitro'])) {
    $stmt = $conn->prepare("SELECT VaiTro FROM NguoiDung WHERE MaNguoiDung = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($vaitro);
    if ($stmt->fetch()) {
        $_SESSION['vaitro'] = $vaitro;
    }
    $stmt->close();
}

if ($_SESSION['vaitro'] !== 'QuanTri') {
    echo "<h3>Truy cập bị từ chối: Chỉ quản trị viên.</h3>";
    exit;
}

// PIN gate
if (!isset($_SESSION['admin_pin_ok']) || $_SESSION['admin_pin_ok'] !== true) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pin'])) {
        if ($_POST['admin_pin'] === '0000') {
            $_SESSION['admin_pin_ok'] = true;
            header("Location: index.php");
            exit;
        } else {
            $error = 'Sai mã PIN.';
        }
    }

    // Now render the PIN form (no other HTML before this!)
    ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <link rel="stylesheet" href="..\assets\admin_style.css">
    <title>PIN Quản trị</title>
</head>

<body>
    <div style="display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--bg);">
        <form method="post" name="pin_form"
            style="background: var(--card); padding: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.3); max-width: 400px; width: 100%; text-align: center; color: var(--text);">
            <h2 style="margin-top: 0; color: var(--highlight);">Xác minh mã PIN</h2>
            <p>Vui lòng nhập mã PIN để truy cập trang quản trị:</p>
            <input type="password" name="pin_code" placeholder="Nhập mã PIN" required
                style="width: 100%; padding: 0.75rem; margin: 1rem 0; border-radius: 4px; border: none; background: #444; color: #fff;">
            <button type="submit" name="verify_pin"
                style="width: 100%; padding: 0.75rem; background: var(--highlight); color: #fff; border: none; border-radius: 4px; cursor: pointer;">
                Xác minh
            </button>
        </form>
    </div>

</body>

</html>
<?php
    exit; // stop here if PIN not validated
}
?>



<?php
require_once 'sidebar.php';
require_once __DIR__ . '\..\libs\SimpleXLSXGen.php';
require_once __DIR__ . '/../libs/tcpdf/tcpdf.php';

// Fetch total product count
$stmt = $pdo->query("SELECT COUNT(*) FROM Laptop");
$totalProducts = $stmt->fetchColumn();

// Fetch total sold & revenue
$stmt = $pdo->query("SELECT SUM(SoLuong) AS total_sold, SUM(ThanhTien) AS total_revenue FROM ChiTietDonHang");
$data = $stmt->fetch(PDO::FETCH_ASSOC);
$totalSold = $data['total_sold'] ?? 0;
$totalRevenue = $data['total_revenue'] ?? 0;

// Load category, brand, and specs list for dropdowns
$categories = $pdo->query("SELECT MaDanhMuc, TenDanhMuc FROM DanhMuc")->fetchAll(PDO::FETCH_KEY_PAIR);
$brands = $pdo->query("SELECT MaHang, TenHang FROM Hang")->fetchAll(PDO::FETCH_KEY_PAIR);
$specs = $pdo->query("SELECT MaThongSo, TenCPU, ManHinh, RAM, OCung, CardDoHoa, Pin, KhoiLuong 
                       FROM ThongSoKyThuat")->fetchAll(PDO::FETCH_ASSOC);

// Load product list
$search = $_GET['search'] ?? '';
$min = $_GET['min_price'] ?? 0;
$max = $_GET['max_price'] ?? 1000000000;

$query = "SELECT l.MaLaptop AS id, l.TenLaptop AS name, l.GiaBan AS price, l.SoLuong AS stock,
    l.MoTa AS description, h.DuongDan AS image, l.MaDanhMuc AS category_id,
    dm.TenDanhMuc AS category_name, l.MaHang AS brand_id, ha.TenHang AS brand_name,
    l.MaThongSo AS spec_id, ts.TenCPU, ts.ManHinh, ts.RAM, ts.OCung, ts.CardDoHoa, ts.Pin, ts.KhoiLuong,
    COALESCE(SUM(ct.SoLuong), 0) AS sold 
    FROM Laptop l
    LEFT JOIN (SELECT MaLaptop, SUM(SoLuong) AS SoLuong FROM ChiTietDonHang GROUP BY MaLaptop) ct
    ON l.MaLaptop = ct.MaLaptop
    LEFT JOIN DanhMuc dm ON l.MaDanhMuc = dm.MaDanhMuc
    LEFT JOIN Hang ha ON l.MaHang = ha.MaHang
    LEFT JOIN HinhAnh h ON l.MaLaptop = h.MaLaptop AND h.MacDinh = TRUE
    LEFT JOIN ThongSoKyThuat ts ON l.MaThongSo = ts.MaThongSo
    WHERE l.TenLaptop LIKE ? AND l.GiaBan BETWEEN ? AND ?
    GROUP BY l.MaLaptop";

$stmt = $pdo->prepare($query);
$stmt->execute(["%$search%", $min, $max]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination setup
$page = $_GET['page'] ?? 1;
$perPage = 5;
$totalPages = ceil(count($products) / $perPage);
$paginated = array_slice($products, ($page - 1) * $perPage, $perPage);

// Helper to get product data
function getProductData($pdo, $filter = '', $startDate = '', $endDate = '') {
    $where = '';
    $params = [];
    if ($startDate && $endDate) {
        $where = "WHERE dh.NgayDat >= :start AND dh.NgayDat <= :end";
        $params['start'] = $startDate;
        $params['end'] = $endDate;
    }
    $sql = "
        SELECT l.MaLaptop AS id, l.TenLaptop AS name, l.GiaBan AS price,
               l.SoLuong AS stock, COALESCE(SUM(ctdh.SoLuong), 0) AS sold,
               dm.TenDanhMuc AS category, l.MoTa AS description
        FROM Laptop l
        LEFT JOIN DanhMuc dm ON l.MaDanhMuc = dm.MaDanhMuc
        LEFT JOIN ChiTietDonHang ctdh ON l.MaLaptop = ctdh.MaLaptop
        LEFT JOIN DonHang dh ON ctdh.MaDonHang = dh.MaDonHang
        $where
        GROUP BY l.MaLaptop
    ";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(":" . $key, $value);
    }
    $stmt->execute();
    $products = $stmt->fetchAll();
    if ($filter === 'top3') {
        usort($products, fn($a, $b) => $b['sold'] - $a['sold']);
        return array_slice($products, 0, 3);
    } elseif ($filter === 'top10') {
        usort($products, fn($a, $b) => $b['sold'] - $a['sold']);
        return array_slice($products, 0, 10);
    }
    return $products;
}

// Export Excel
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    $filter = $_GET['filter'] ?? '';
    $start = $_GET['start_date'] ?? '';
    $end = $_GET['end_date'] ?? '';
    $products = getProductData($pdo, $filter, $start, $end);
    $rows = [["ID", "Name", "Price", "Stock", "Sold", "Category", "Description"]];
    foreach ($products as $p) {
        $rows[] = [$p['id'], $p['name'], $p['price'], $p['stock'], $p['sold'], $p['category'], $p['description']];
    }
    $xlsx = Shuchkin\SimpleXLSXGen::fromArray($rows);
    $xlsx->downloadAs("products_export.xlsx");
    exit;
}

// Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    $filter = $_GET['filter'] ?? '';
    $start = $_GET['start_date'] ?? '';
    $end = $_GET['end_date'] ?? '';
    $products = getProductData($pdo, $filter, $start, $end);
    $pdf = new TCPDF();
    $pdf->SetCreator('Shop Admin');
    $pdf->SetTitle('Product Report');
    $pdf->AddPage();
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Product Export Report', 0, 1, 'C');
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $headers = ['ID', 'Name', 'Price', 'Stock', 'Sold', 'Category', 'Description'];
    foreach ($headers as $col) {
        $pdf->Cell(28, 7, $col, 1);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', '', 9);
    foreach ($products as $p) {
        $pdf->Cell(28, 6, $p['id'], 1);
        $pdf->Cell(28, 6, substr($p['name'], 0, 15), 1);
        $pdf->Cell(28, 6, $p['price'], 1);
        $pdf->Cell(28, 6, $p['stock'], 1);
        $pdf->Cell(28, 6, $p['sold'], 1);
        $pdf->Cell(28, 6, substr($p['category'], 0, 15), 1);
        $pdf->Cell(28, 6, substr($p['description'], 0, 15), 1);
        $pdf->Ln();
    }
    $pdf->Output('products.pdf', 'D');
    exit;
}

// Handle Add / Edit / Sell / Remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        if (empty($_POST['name']) || !is_numeric($_POST['price']) || !is_numeric($_POST['stock'])) {
            header("Location: index.php?error=Invalid input for adding product");
            exit;
        }
        try {
            $stmt = $pdo->prepare("INSERT INTO Laptop (TenLaptop, GiaBan, SoLuong, MaDanhMuc, MoTa, MaHang, MaThongSo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['name'], $_POST['price'], $_POST['stock'], $_POST['category'], $_POST['description'], $_POST['brand'], $_POST['specs']]);
            $maLaptop = $pdo->lastInsertId();
            $stmt = $pdo->prepare("INSERT INTO HinhAnh (MaLaptop, DuongDan, MacDinh) VALUES (?, ?, TRUE)");
            $stmt->execute([$maLaptop, $_POST['image']]);
            header("Location: index.php?success=Product added");
        } catch (PDOException $e) {
            header("Location: index.php?error=Database error: Unable to add product");
        }
    } elseif ($action === 'update') {
        if (empty($_POST['id']) || empty($_POST['name']) || !is_numeric($_POST['price']) || !is_numeric($_POST['stock'])) {
            header("Location: index.php?error=Invalid input for updating product");
            exit;
        }
        try {
            $stmt = $pdo->prepare("UPDATE Laptop SET TenLaptop=?, GiaBan=?, SoLuong=?, MaDanhMuc=?, MoTa=?, MaHang=?, MaThongSo=? WHERE MaLaptop=?");
            $stmt->execute([$_POST['name'], $_POST['price'], $_POST['stock'], $_POST['category'], $_POST['description'], $_POST['brand'], $_POST['specs'], $_POST['id']]);
            $stmt = $pdo->prepare("UPDATE HinhAnh SET DuongDan=? WHERE MaLaptop=? AND MacDinh=TRUE");
            $stmt->execute([$_POST['image'], $_POST['id']]);
            header("Location: index.php?success=Product updated");
        } catch (PDOException $e) {
            header("Location: index.php?error=Database error: Unable to update product");
        }
    }
    exit;
}

if (isset($_GET['sell_id'])) {
    $id = $_GET['sell_id'];
    try {
        $pdo->exec("UPDATE Laptop SET SoLuong = SoLuong - 1 WHERE MaLaptop = $id AND SoLuong > 0");
        header("Location: index.php?success=Product sold");
    } catch (PDOException $e) {
        header("Location: index.php?error=Database error: Unable to sell product");
    }
    exit;
}

if (isset($_GET['remove_id'])) {
    $id = $_GET['remove_id'];
    try {
        $pdo->exec("DELETE FROM Laptop WHERE MaLaptop = $id");
        header("Location: index.php?success=Product removed");
    } catch (PDOException $e) {
        header("Location: index.php?error=Database error: Unable to remove product");
    }
    exit;
}

function findProductById($id, $products) {
    foreach ($products as $p) {
        if ($p['id'] == $id) return $p;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>

<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <div class="header">
                <h1>Quản lý sản phẩm</h1>
                <button id="showChartBtn">View Sales Chart</button>
            </div>
            <div class="main">
                <?php if (isset($_GET['success'])): ?>
                <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['error'])): ?>
                <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                <div class="stats">
                    <div class="card">
                        <h3>Tổng sản phẩm</h3>
                        <p><?php echo $totalProducts; ?></p>
                    </div>
                    <div class="card">
                        <h3>Tổng đã bán</h3>
                        <p><?php echo $totalSold; ?></p>
                    </div>
                    <div class="card">
                        <h3>Tổng lợi nhuận</h3>
                        <p><?php echo number_format($totalRevenue, 0, ',', '.') . ' VND'; ?></p>
                    </div>
                </div>
                <form method="get" class="inline">
                    <input type="text" name="search" placeholder="Search..."
                        value="<?php echo htmlspecialchars($search); ?>">
                    <input type="number" step="1000" name="min_price" placeholder="Min price"
                        value="<?php echo $min; ?>">
                    <input type="number" step="1000" name="max_price" placeholder="Max price"
                        value="<?php echo $max; ?>">
                    <button class="btn btn-primary" type="submit">Filter</button>
                </form>
                <h2>Xuất sản phẩm đã bán</h2>
                <form method="get" class="inline">
                    <label>Ngày bắt đầu: <input type="date" name="start_date"></label>
                    <label>Ngày kết thúc: <input type="date" name="end_date"></label>
                    <label>Lọc:
                        <select name="filter">
                            <option value="">-- Tất cả sản phẩm --</option>
                            <option value="top3">Top 3 Most Sold</option>
                            <option value="top10">Top 10 Most Sold</option>
                        </select>
                    </label>
                    <button type="submit" name="export" value="excel">Export to Excel</button>
                    <button type="submit" name="export" value="pdf">Export to PDF</button>
                </form>
                <h2>Danh sách sản phẩm</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Giá</th>
                            <th>Còn</th>
                            <th>Đã bán</th>
                            <th>Danh mục</th>
                            <th>Hãng</th>
                            <th>Thông số</th>
                            <th>Mô tả</th>
                            <th>Hình ảnh</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginated as $p): ?>
                        <tr>
                            <form method="post">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                <td><?php echo $p['id']; ?></td>
                                <td><input name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="price" step="1000" value="<?php echo $p['price']; ?>">
                                    <?php echo number_format($p['price'], 0, ',', '.'); ?> VND
                                </td>
                                <td><input type="number" name="stock" value="<?php echo $p['stock']; ?>" required></td>
                                <td><?php echo $p['sold']; ?></td>
                                <td>
                                    <select name="category" required>
                                        <?php foreach ($categories as $id => $catName): ?>
                                        <option value="<?php echo $id; ?>"
                                            <?php echo ($p['category_id'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($catName); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="brand" required>
                                        <?php foreach ($brands as $id => $brandName): ?>
                                        <option value="<?php echo $id; ?>"
                                            <?php echo ($p['brand_id'] == $id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brandName); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <select name="specs" required>
                                        <?php foreach ($specs as $spec): ?>
                                        <option value="<?php echo $spec['MaThongSo']; ?>"
                                            <?php echo ($p['spec_id'] == $spec['MaThongSo']) ? 'selected' : ''; ?>>
                                            <?php 
                                                $specDisplay = htmlspecialchars($spec['TenCPU']) . 
                                                               ", " . htmlspecialchars($spec['CardDoHoa']) . 
                                                               ", " . htmlspecialchars($spec['ManHinh']) . 
                                                               ", " . htmlspecialchars($spec['RAM']) . 
                                                               "|" . htmlspecialchars($spec['OCung']) . 
                                                               ", Pin: " . htmlspecialchars($spec['Pin']) . 
                                                               ", " . htmlspecialchars($spec['KhoiLuong']);
                                                echo $specDisplay;
                                            ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><textarea name="description" onclick="this.rows=5;" onblur="this.rows=1;"
                                        rows="1"><?php echo htmlspecialchars($p['description']); ?></textarea></td>
                                <td><input type="text" name="image"
                                        value="<?php echo htmlspecialchars($p['image']); ?>"></td>
                                <td>
                                    <button class="btn btn-primary" type="submit">Apply Edit</button>
                                    <a class="btn btn-primary" href="?sell_id=<?php echo $p['id']; ?>">Sell</a>
                                    <a class="btn btn-primary" href="?remove_id=<?php echo $p['id']; ?>">Remove</a>
                                </td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="page-nav">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&min_price=<?php echo $min; ?>&max_price=<?php echo $max; ?>"
                        class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
                <h2>Thêm sản phẩm</h2>
                <form method="post" class="inline">
                    <input type="hidden" name="action" value="add">
                    <input name="name" placeholder="Name" required>
                    <input type="number" step="1000" name="price" placeholder="Price (VND)" required>
                    <input type="number" name="stock" placeholder="Stock" required>
                    <select name="category" required>
                        <?php foreach ($categories as $id => $catName): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($catName); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="brand" required>
                        <?php foreach ($brands as $id => $brandName): ?>
                        <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($brandName); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="specs" required>
                        <?php foreach ($specs as $spec): ?>
                        <option value="<?php echo $spec['MaThongSo']; ?>">
                            <?php 
                                $specDisplay = htmlspecialchars($spec['TenCPU']) . 
                                               ", " . htmlspecialchars($spec['CardDoHoa']) . 
                                               ", " . htmlspecialchars($spec['ManHinh']) . 
                                               ", " . htmlspecialchars($spec['RAM']) . 
                                               "|" . htmlspecialchars($spec['OCung']) . 
                                               ", Pin: " . htmlspecialchars($spec['Pin']) . 
                                               ", " . htmlspecialchars($spec['KhoiLuong']);
                                echo $specDisplay;
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <input name="description" placeholder="Description">
                    <input name="image" placeholder="Image URL or path">
                    <button class="btn btn-primary" type="submit">Add</button>
                </form>
            </div>
            <div id="chartModal">
                <div id="chartContent">
                    <button id="chartClose">×</button>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            <script>
            const modal = document.getElementById('chartModal');
            document.getElementById('showChartBtn').onclick = () => modal.style.display = 'flex';
            document.getElementById('chartClose').onclick = () => modal.style.display = 'none';
            window.onclick = e => {
                if (e.target === modal) modal.style.display = 'none';
            };
            new Chart(document.getElementById('salesChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($products, 'name')); ?>,
                    datasets: [{
                        label: 'Units Sold',
                        data: <?php echo json_encode(array_column($products, 'sold')); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            </script>
        </div>
    </div>
</body>

</html>