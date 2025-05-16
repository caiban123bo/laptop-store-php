<?php
include 'header.php';
include 'assets/db.php';

// Bật lỗi để debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo "<p style='padding:20px;'>Vui lòng <a href='login.php'>đăng nhập</a> để đổi mật khẩu.</p>";
    include('Footer.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matKhauHienTai = $_POST['mat_khau_hien_tai'];
    $matKhauMoi = $_POST['mat_khau_moi'];
    $xacNhanMatKhau = $_POST['xac_nhan_mat_khau'];

    // Debug: Ghi lại dữ liệu nhập vào
    error_log("Mật khẩu nhập: matKhauHienTai=$matKhauHienTai, matKhauMoi=$matKhauMoi");

    // Kiểm tra dữ liệu
    if (empty($matKhauHienTai) || empty($matKhauMoi) || empty($xacNhanMatKhau)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif ($matKhauMoi !== $xacNhanMatKhau) {
        $error = 'Mật khẩu mới và xác nhận mật khẩu không khớp!';
    } elseif (strlen($matKhauMoi) < 6) {
        $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    }

    if (!$error) {
        // Lấy mật khẩu hiện tại từ cơ sở dữ liệu
        $stmt = $conn->prepare("SELECT MatKhau FROM NguoiDung WHERE MaNguoiDung = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        // Debug: Ghi lại mật khẩu từ cơ sở dữ liệu
        error_log("Mật khẩu từ DB: " . $user['MatKhau']);

        // So sánh mật khẩu hiện tại trực tiếp (không băm)
        if ($matKhauHienTai !== $user['MatKhau']) {
            $error = 'Mật khẩu hiện tại không đúng!';
        } else {
            // Lưu mật khẩu mới trực tiếp (không băm)
            $stmt = $conn->prepare("UPDATE NguoiDung SET MatKhau = ? WHERE MaNguoiDung = ?");
            $stmt->bind_param("si", $matKhauMoi, $user_id);
            if ($stmt->execute()) {
                $success = 'Đổi mật khẩu thành công! Vui lòng đăng nhập lại với mật khẩu mới.';
                // Đăng xuất người dùng sau khi đổi mật khẩu
                session_destroy();
            } else {
                $error = 'Lỗi khi đổi mật khẩu: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<main>
    <h2>Đổi mật khẩu</h2>
    <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" class="password-form">
        <div class="form-group">
            <label for="mat_khau_hien_tai">Mật khẩu hiện tại</label>
            <input type="password" name="mat_khau_hien_tai" id="mat_khau_hien_tai" required>
        </div>
        <div class="form-group">
            <label for="mat_khau_moi">Mật khẩu mới</label>
            <input type="password" name="mat_khau_moi" id="mat_khau_moi" required>
        </div>
        <div class="form-group">
            <label for="xac_nhan_mat_khau">Xác nhận mật khẩu mới</label>
            <input type="password" name="xac_nhan_mat_khau" id="xac_nhan_mat_khau" required>
        </div>
        <button type="submit">Đổi mật khẩu</button>
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

h2 {
    color: #333;
    margin-bottom: 20px;
    border-left: 4px solid #007BFF;
    padding-left: 10px;
}

.password-form {
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