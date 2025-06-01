<?php
require_once 'sidebar.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Event actions
    if ($action === 'inline_update_event') {
        $stmt = $pdo->prepare(
            "UPDATE KhuyenMai SET TenChuongTrinh=?, MoTa=?, PhanTramGiam=?, NgayBatDau=?, NgayKetThuc=? WHERE MaKhuyenMai=?"
        );
        $stmt->execute([
            $_POST['name'], $_POST['description'], floatval($_POST['discount']), $_POST['start'], $_POST['end'], intval($_POST['id'])
        ]);
    } elseif ($action === 'inline_update_coupon') {
        $stmt = $pdo->prepare(
            "UPDATE KhuyenMai SET MaGiamGia=?, PhanTramGiam=?, GiamToiDa=?, SoLuong=?, NgayKetThuc=? WHERE MaKhuyenMai=?"
        );
        $stmt->execute([
            $_POST['code'], floatval($_POST['percent']), floatval($_POST['max_discount']), intval($_POST['usage_limit']), $_POST['expiry'], intval($_POST['id'])
        ]);
    } elseif ($action === 'add_event') {
        $stmt = $pdo->prepare(
            "INSERT INTO KhuyenMai (TenChuongTrinh, MoTa, PhanTramGiam, GiamToiDa, NgayBatDau, NgayKetThuc, TrangThai) VALUES (?, ?, ?, ?, ?, ?, TRUE)"
        );
        // If max_discount is empty, set it to NULL; otherwise, convert to float
        $max_discount = !empty($_POST['max_discount']) ? floatval($_POST['max_discount']) : null;
        $stmt->execute([
            $_POST['name'], $_POST['description'], floatval($_POST['discount']), $max_discount, $_POST['start'], $_POST['end']
        ]);
    } elseif ($action === 'add_coupon') {
        $stmt = $pdo->prepare(
            "INSERT INTO KhuyenMai (MaGiamGia, PhanTramGiam, GiamToiDa, SoLuong, NgayBatDau, NgayKetThuc, TrangThai) VALUES (?, ?, ?, ?, CURDATE(), ?, TRUE)"
        );
        $stmt->execute([
            $_POST['code'], floatval($_POST['percent']), floatval($_POST['max_discount']), intval($_POST['usage_limit']), $_POST['expiry']
        ]);
    }
    header('Location: promotions.php');
    exit;
}

// Handle deletions
if (isset($_GET['delete_event'])) {
    $stmt = $pdo->prepare("DELETE FROM KhuyenMai WHERE MaKhuyenMai=?");
    $stmt->execute([intval($_GET['delete_event'])]);
    header('Location: promotions.php');
    exit;
}
if (isset($_GET['delete_coupon'])) {
    $stmt = $pdo->prepare("DELETE FROM KhuyenMai WHERE MaKhuyenMai=?");
    $stmt->execute([intval($_GET['delete_coupon'])]);
    header('Location: promotions.php');
    exit;
}

// Fetch data
$events = $pdo->query(
    "SELECT MaKhuyenMai AS id, TenChuongTrinh AS name, MoTa AS description, PhanTramGiam AS discount,
     DATE(NgayBatDau) AS start, DATE(NgayKetThuc) AS end 
     FROM KhuyenMai WHERE MaGiamGia IS NULL"
)->fetchAll(PDO::FETCH_ASSOC);

$coupons = $pdo->query(
    "SELECT k.MaKhuyenMai AS id, k.MaGiamGia AS code, k.PhanTramGiam AS percent, k.GiamToiDa AS max_discount, 
     k.SoLuong AS usage_limit,
     (SELECT COUNT(*) FROM DonHang d WHERE d.MaKhuyenMai = k.MaKhuyenMai AND d.TrangThai != 'DaHuy') AS used,
     DATE(k.NgayKetThuc) AS expiry
     FROM KhuyenMai k WHERE k.MaGiamGia IS NOT NULL"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khuyến mãi/sự kiện</title>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>
<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <div class="header">
                <h1>Quản lý khuyến mãi/sự kiện</h1>
                <h2>Sự kiện</h2>
            </div>
            <form method="post">
                <input type="hidden" name="action" value="inline_update_event">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Bắt đầu</th>
                            <th>Kết thúc</th>
                            <th>Mô tả</th>
                            <th>Giảm %</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td><?php echo $e['id']; ?><input type="hidden" name="id" value="<?php echo $e['id']; ?>"></td>
                            <td><input name="name" value="<?php echo htmlspecialchars($e['name']); ?>"></td>
                            <td><input type="date" name="start" value="<?php echo $e['start']; ?>"></td>
                            <td><input type="date" name="end" value="<?php echo $e['end']; ?>"></td>
                            <td><textarea name="description" rows="1" onclick="this.rows=3;" onblur="this.rows=1;">
                                <?php echo htmlspecialchars($e['description']); ?></textarea></td>
                            <td><input type="number" step="0.01" name="discount" value="<?php echo $e['discount']; ?>"></td>
                            <td>
                                <button class="btn btn-primary" type="submit">Apply Edit</button>
                                <a class="btn" href="?delete_event=<?php echo $e['id']; ?>" onclick="return confirm('Delete?');">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <h2>Mã khuyến mãi</h2>
            <form method="post">
                <input type="hidden" name="action" value="inline_update_coupon">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Giảm %</th>
                            <th>Giảm tối đa</th>
                            <th>Số lần sử dụng</th>
                            <th>Đã sử dụng</th>
                            <th>Hết hạn</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($coupons as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?><input type="hidden" name="id" value="<?php echo $c['id']; ?>"></td>
                            <td><input name="code" value="<?php echo htmlspecialchars($c['code']); ?>"></td>
                            <td><input type="number" name="percent" step="0.01" value="<?php echo $c['percent']; ?>"></td>
                            <td><input type="number" name="max_discount" step="1000" value="<?php echo $c['max_discount']; ?>"></td>
                            <td><input type="number" name="usage_limit" value="<?php echo $c['usage_limit']; ?>"></td>
                            <td><?php echo $c['used']; ?></td>
                            <td><input type="date" name="expiry" value="<?php echo $c['expiry']; ?>"></td>
                            <td>
                                <button class="btn btn-primary" type="submit">Apply Edit</button>
                                <a class="btn" href="?delete_coupon=<?php echo $c['id']; ?>" onclick="return confirm('Delete?');">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <h3>Thêm sự kiện</h3>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="add_event">
                <input name="name" placeholder="Event Name" required>
                <input type="date" name="start" required>
                <input type="date" name="end" required>
                <input name="description" placeholder="Description">
                <input type="number" step="0.01" name="discount" placeholder="Discount (%)" required>
                <input type="number" step="1000" name="max_discount" placeholder="Max Discount (VND)">
                <button class="btn btn-primary" type="submit">Add</button>
            </form>
            <h3>Thêm khuyến mãi</h3>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="add_coupon">
                <input name="code" placeholder="Code" required>
                <input type="number" step="0.01" name="percent" placeholder="Discount (%)">
                <input type="number" step="1000" name="max_discount" placeholder="Max Discount (VND)">
                <input type="number" name="usage_limit" placeholder="Usage Limit" required>
                <input type="date" name="expiry" required>
                <button class="btn btn-primary" type="submit">Add</button>
            </form>
        </div>
    </div>
</body>
</html>