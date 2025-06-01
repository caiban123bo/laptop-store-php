<?php
require_once 'sidebar.php';
session_start();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO ChiTietDonHang (MaDonHang, MaLaptop, SoLuong, DonGia) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['ma_don_hang'], $_POST['ma_laptop'], $_POST['so_luong'], $_POST['don_gia']]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE ChiTietDonHang SET MaDonHang=?, MaLaptop=?, SoLuong=?, DonGia=? WHERE MaChiTiet=?");
        $stmt->execute([$_POST['ma_don_hang'], $_POST['ma_laptop'], $_POST['so_luong'], $_POST['don_gia'], $_POST['id']]);
    }
    header("Location: order_details.php");
    exit;
}

// Handle deletion
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM ChiTietDonHang WHERE MaChiTiet=?");
    $stmt->execute([$_GET['delete']]);
    header("Location: order_details.php");
    exit;
}

// Fetch all records
$stmt = $pdo->query("
    SELECT 
        c.MaChiTiet,
        c.MaDonHang,
        c.MaLaptop,
        l.TenLaptop,
        c.SoLuong,
        c.DonGia
    FROM ChiTietDonHang c
    JOIN Laptop l ON c.MaLaptop = l.MaLaptop
");
$orderDetails = $stmt->fetchAll();

// Fetch one record for editing
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM ChiTietDonHang WHERE MaChiTiet=?");
    $stmt->execute([$_GET['edit']]);
    $editData = $stmt->fetch();
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>
<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <div class="header">
                <h1>Quản lý đơn hàng</h1>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Order ID</th>
                        <th>Laptop ID</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderDetails as $row): ?>
                    <tr>
                        <td><?= $row['MaChiTiet'] ?></td>
                        <td><?= $row['MaDonHang'] ?></td>
                        <td><?= $row['MaLaptop'] . ' - ' . htmlspecialchars($row['TenLaptop']) ?></td>
                        <td><?= $row['SoLuong'] ?></td>
                        <td><?= number_format($row['DON_GIA'], 0, ',', '.') ?> VND</td>
                            <td>
                            <a href="?edit=<?= $row['MaChiTiet'] ?>">Edit</a> |
                            <a href="?delete=<?= $row['MaChiTiet'] ?>" onclick="return confirm('Delete this entry?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h3><?= $editData ? 'Edit' : 'Add New' ?> Order Detail</h3>
            <form method="post">
                <input type="hidden" name="action" value="<?= $editData ? 'update' : 'add' ?>">
                <?php if ($editData): ?>
                <input type="hidden" name="id" value="<?= $editData['MaChiTiet'] ?>">
                <?php endif; ?>
                <input name="ma_don_hang" placeholder="Order ID" value="<?= $editData['MaDonHang'] ?? '' ?>" required>
                <input name="ma_laptop" placeholder="Laptop ID" value="<?= $editData['MaLaptop'] ?? '' ?>" required>
                <input type="number" name="so_luong" placeholder="Quantity" value="<?= $editData['SoLuong'] ?? '' ?>" required>
                <input type="number" step="0.01" name="don_gia" placeholder="Price" value="<?= $editData['DonGia'] ?? '' ?>" required>
                <button type="submit"><?= $editData ? 'Update' : 'Add' ?> Detail</button>
            </form>
        </div>
    </div>
</body>
</html>