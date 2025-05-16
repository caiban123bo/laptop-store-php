<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f9f9f9;
        margin: 0;
        padding: 0;
    }

    main {
        max-width: 800px;
        margin: 30px auto;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 30px;
    }

    h2, h3 {
        color: #333;
        margin-bottom: 20px;
        border-left: 4px solid #007BFF;
        padding-left: 10px;
    }

    .user-info p, .address-info p {
        margin: 10px 0;
        color: #555;
        line-height: 1.6;
    }

    .user-info p strong, .address-info p strong {
        color: #000;
    }

    button {
        padding: 10px 20px;
        border: none;
        background-color: #007BFF;
        color: white;
        border-radius: 6px;
        cursor: pointer;
        margin-right: 10px;
        transition: background 0.3s ease;
    }

    button:hover {
        background-color: #0056b3;
    }

    a button {
        text-decoration: none;
    }

    @media screen and (max-width: 600px) {
        main {
            margin: 20px;
            padding: 20px;
        }

        button {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<?php
include 'header.php';
include 'assets/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p style='padding:20px;'>Vui lòng <a href='login.php'>đăng nhập</a> để xem trang cá nhân.</p>";
    include('Footer.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Truy vấn thông tin người dùng
$sql_user = "SELECT TenDangNhap, Email, HoTen, SoDienThoai, NgayTao, VaiTro, TrangThai 
             FROM NguoiDung WHERE MaNguoiDung = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_user = $stmt->get_result();

if ($result_user->num_rows === 0) {
    echo "<p style='padding:20px;'>Không tìm thấy người dùng.</p>";
    include('Footer.php');
    exit;
}

$user = $result_user->fetch_assoc();

// Truy vấn địa chỉ mặc định
$sql_address = "SELECT HoTen, SoDienThoai, DiaChi, TinhThanh, QuanHuyen, PhuongXa 
                FROM DiaChi 
                WHERE MaNguoiDung = ? AND MacDinh = 1 
                LIMIT 1";
$stmt_addr = $conn->prepare($sql_address);
$stmt_addr->bind_param("i", $user_id);
$stmt_addr->execute();
$result_addr = $stmt_addr->get_result();
$address = $result_addr->fetch_assoc();
?>

<main style="padding: 20px;">
    <h2>Thông tin cá nhân</h2>
    <div class="user-info" style="margin-bottom: 20px;">
        <p><strong>Tên đăng nhập:</strong> <?= htmlspecialchars($user['TenDangNhap']) ?></p>
        <p><strong>Họ tên:</strong> <?= htmlspecialchars($user['HoTen']) ?: 'Chưa cập nhật' ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['Email']) ?></p>
        <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($user['SoDienThoai']) ?: 'Chưa cập nhật' ?></p>
        <p><strong>Ngày tạo tài khoản:</strong> <?= date('d/m/Y H:i', strtotime($user['NgayTao'])) ?></p>
        <p><strong>Vai trò:</strong> <?= ($user['VaiTro'] === 'QuanTri') ? 'Quản trị viên' : 'Khách hàng' ?></p>
        <p><strong>Trạng thái:</strong> <?= ($user['TrangThai']) ? 'Đang hoạt động' : 'Bị khóa' ?></p>
    </div>

    <h3>Địa chỉ mặc định</h3>
    <?php if ($address): ?>
        <div class="address-info">
            <p><strong>Họ tên:</strong> <?= htmlspecialchars($address['HoTen']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($address['SoDienThoai']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($address['DiaChi']) ?></p>
            <p><strong>Phường/Xã - Quận/Huyện - Tỉnh/Thành:</strong> <?= 
                htmlspecialchars($address['PhuongXa']) . ' - ' . 
                htmlspecialchars($address['QuanHuyen']) . ' - ' . 
                htmlspecialchars($address['TinhThanh']) ?></p>
        </div>
    <?php else: ?>
        <p>Chưa có địa chỉ mặc định.</p>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="CapNhatThongTin.php"><button>Cập nhật thông tin</button></a>
        <a href="DoiMatKhau.php"><button>Đổi mật khẩu</button></a>
    </div>
</main>

<?php include('Footer.php'); ?>
