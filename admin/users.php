<?php
require_once 'sidebar.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO NguoiDung (TenDangNhap, MatKhau, Email, HoTen, SoDienThoai, TrangThai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['email'], password_hash($_POST['mat_khau'], PASSWORD_DEFAULT), $_POST['email'],
            $_POST['ho_ten'], $_POST['so_dien_thoai'], $_POST['trang_thai'] ? 1 : 0
        ]);
        $maNguoiDung = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO DiaChi (MaNguoiDung, DiaChi, MacDinh) VALUES (?, ?, TRUE)");
        $stmt->execute([$maNguoiDung, $_POST['dia_chi']]);
    } elseif ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE NguoiDung SET HoTen=?, Email=?, SoDienThoai=?, TrangThai=? WHERE MaNguoiDung=?");
        $stmt->execute([$_POST['ho_ten'], $_POST['email'], $_POST['so_dien_thoai'], $_POST['trang_thai'] ? 1 : 0, $_POST['id']]);
        $stmt = $pdo->prepare("UPDATE DiaChi SET DiaChi=? WHERE MaNguoiDung=? AND MacDinh=TRUE");
        $stmt->execute([$_POST['dia_chi'], $_POST['id']]);
    }
    header("Location: users.php");
    exit;
}

if (isset($_GET['remove_id'])) {
    $id = $_GET['remove_id'];
    $pdo->exec("DELETE FROM NguoiDung WHERE MaNguoiDung = $id");
    header("Location: users.php");
    exit;
}

$users = $pdo->query("
    SELECT nd.*, dc.DiaChi AS default_address
    FROM NguoiDung nd
    LEFT JOIN DiaChi dc ON nd.MaNguoiDung = dc.MaNguoiDung AND dc.MacDinh = TRUE
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng</title>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>
<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <div class="header"><h1>Quản lý người dùng</h1></div>
            <form method="post">
                <input type="hidden" name="action" value="update">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ & Tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Địa chỉ</th>
                            <th>Trạng thái</th>
                            <th>Actions</ Futbol</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['MaNguoiDung']; ?><input type="hidden" name="id" value="<?php echo $user['MaNguoiDung']; ?>"></td>
                            <td><input name="ho_ten" value="<?php echo htmlspecialchars($user['HoTen']); ?>"></td>
                            <td><input name="email" value="<?php echo htmlspecialchars($user['Email']); ?>"></td>
                            <td><input name="so_dien_thoai" value="<?php echo htmlspecialchars($user['SoDienThoai']); ?>"></td>
                            <td><input name="dia_chi" value="<?php echo htmlspecialchars($user['default_address']); ?>"></td>
                            <td><input name="trang_thai" type="checkbox" <?php echo $user['TrangThai'] ? 'checked' : ''; ?> value="1"></td>
                            <td>
                                <button class="btn btn-primary" type="submit">Apply Edit</button>
                                <a class="btn btn-primary" href="?remove_id=<?php echo $user['MaNguoiDung']; ?>">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>
            <h2>Thêm người dùng</h2>
            <form method="post" class="inline">
                <input type="hidden" name="action" value="add">
                <input name="ho_ten" placeholder="Full Name" required>
                <input name="email" type="email" placeholder="Email" required>
                <input name="so_dien_thoai" placeholder="Phone" required>
                <input name="mat_khau" type="password" placeholder="Password" required>
                <input name="dia_chi" placeholder="Address">
                <input name="trang_thai" type="checkbox" checked value="1"> Active
                <button class="btn btn-primary" type="submit">Add</button>
            </form>
        </div>
    </div>
</body>
</html>