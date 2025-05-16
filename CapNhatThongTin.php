<?php
include 'header.php';
include 'assets/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p style='padding:20px;'>Vui lòng <a href='login.php'>đăng nhập</a> để cập nhật thông tin.</p>";
    include('Footer.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Truy vấn thông tin người dùng
$sql_user = "SELECT HoTen, SoDienThoai FROM NguoiDung WHERE MaNguoiDung = ?";
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
$stmt->close();

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
$stmt_addr->close();

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hoTen = trim($_POST['ho_ten']);
    $soDienThoai = trim($_POST['so_dien_thoai']);
    $diaChiHoTen = trim($_POST['dia_chi_ho_ten']);
    $diaChiSoDienThoai = trim($_POST['dia_chi_so_dien_thoai']);
    $diaChi = trim($_POST['dia_chi']);
    $tinhThanh = trim($_POST['tinh_thanh']);
    $quanHuyen = trim($_POST['quan_huyen']);
    $phuongXa = trim($_POST['phuong_xa']);

    // Kiểm tra dữ liệu
    if (empty($hoTen)) {
        $error = 'Vui lòng nhập họ tên!';
    } elseif (empty($soDienThoai) || !preg_match('/^[0-9]{10,11}$/', $soDienThoai)) {
        $error = 'Số điện thoại không hợp lệ!';
    } elseif (empty($diaChiHoTen)) {
        $error = 'Vui lòng nhập họ tên người nhận cho địa chỉ!';
    } elseif (empty($diaChiSoDienThoai) || !preg_match('/^[0-9]{10,11}$/', $diaChiSoDienThoai)) {
        $error = 'Số điện thoại người nhận không hợp lệ!';
    } elseif (empty($diaChi) || empty($tinhThanh) || empty($quanHuyen) || empty($phuongXa)) {
        $error = 'Vui lòng nhập đầy đủ thông tin địa chỉ!';
    }

    if (!$error) {
        // Cập nhật thông tin người dùng
        $stmt = $conn->prepare("UPDATE NguoiDung SET HoTen = ?, SoDienThoai = ? WHERE MaNguoiDung = ?");
        $stmt->bind_param('ssi', $hoTen, $soDienThoai, $user_id);
        if (!$stmt->execute()) {
            $error = 'Lỗi cập nhật thông tin người dùng: ' . $stmt->error;
        }
        $stmt->close();

        // Cập nhật hoặc thêm địa chỉ mặc định
        if ($address) {
            $stmt = $conn->prepare("UPDATE DiaChi SET HoTen = ?, SoDienThoai = ?, DiaChi = ?, TinhThanh = ?, QuanHuyen = ?, PhuongXa = ? 
                                    WHERE MaNguoiDung = ? AND MacDinh = 1");
            $stmt->bind_param('ssssssi', $diaChiHoTen, $diaChiSoDienThoai, $diaChi, $tinhThanh, $quanHuyen, $phuongXa, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO DiaChi (MaNguoiDung, HoTen, SoDienThoai, DiaChi, TinhThanh, QuanHuyen, PhuongXa, MacDinh) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param('issssss', $user_id, $diaChiHoTen, $diaChiSoDienThoai, $diaChi, $tinhThanh, $quanHuyen, $phuongXa);
        }
        if (!$stmt->execute()) {
            $error = 'Lỗi cập nhật địa chỉ: ' . $stmt->error;
        } else {
            $success = 'Cập nhật thông tin thành công!';
        }
        $stmt->close();
    }
}
?>

<main>
    <h2>Cập nhật thông tin cá nhân</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="update-form">
        <h3>Thông tin cá nhân</h3>
        <div class="form-group">
            <label for="ho_ten">Họ tên</label>
            <input type="text" name="ho_ten" id="ho_ten" value="<?php echo htmlspecialchars($user['HoTen'] ?: ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="so_dien_thoai">Số điện thoại</label>
            <input type="text" name="so_dien_thoai" id="so_dien_thoai" value="<?php echo htmlspecialchars($user['SoDienThoai'] ?: ''); ?>" required>
        </div>

        <h3>Địa chỉ mặc định</h3>
        <div class="form-group">
            <label for="dia_chi_ho_ten">Họ tên người nhận</label>
            <input type="text" name="dia_chi_ho_ten" id="dia_chi_ho_ten" value="<?php echo htmlspecialchars($address['HoTen'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="dia_chi_so_dien_thoai">Số điện thoại người nhận</label>
            <input type="text" name="dia_chi_so_dien_thoai" id="dia_chi_so_dien_thoai" value="<?php echo htmlspecialchars($address['SoDienThoai'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="dia_chi">Địa chỉ</label>
            <input type="text" name="dia_chi" id="dia_chi" value="<?php echo htmlspecialchars($address['DiaChi'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="tinh_thanh">Tỉnh/Thành</label>
            <input type="text" name="tinh_thanh" id="tinh_thanh" value="<?php echo htmlspecialchars($address['TinhThanh'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="quan_huyen">Quận/Huyện</label>
            <input type="text" name="quan_huyen" id="quan_huyen" value="<?php echo htmlspecialchars($address['QuanHuyen'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="phuong_xa">Phường/Xã</label>
            <input type="text" name="phuong_xa" id="phuong_xa" value="<?php echo htmlspecialchars($address['PhuongXa'] ?? ''); ?>" required>
        </div>
        <button type="submit">Cập nhật</button>
        <a href="TrangCaNhan.php"><button type="button">Quay lại</button></a>
    </form>
</main>

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

.update-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 5px;
    color: #555;
    font-weight: 500;
}

.form-group input {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    transition: border-color 0.3s ease;
}

.form-group input:focus {
    border-color: #007BFF;
    outline: none;
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
    background-color: #6c757d;
}

a button:hover {
    background-color: #5a6268;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 6px;
    margin-bottom: 15px;
    text-align: center;
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

<?php include('Footer.php'); ?>