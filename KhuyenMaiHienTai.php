<?php
include 'header.php';
include 'assets/db.php';

// Thời gian hiện tại
$now = date('Y-m-d H:i:s');

// Truy vấn
$sql_khuyenmai = "SELECT * FROM KhuyenMai 
                  WHERE NgayBatDau <= ? AND NgayKetThuc >= ? AND TrangThai = 1";
$stmt = $conn->prepare($sql_khuyenmai);
if ($stmt === false) {
    die('Lỗi chuẩn bị câu lệnh SQL: ' . $conn->error);
}
$stmt->bind_param("ss", $now, $now);
$stmt->execute();
$result_khuyenmai = $stmt->get_result();
$khuyenmai_list = [];
while ($row = $result_khuyenmai->fetch_assoc()) {
    $khuyenmai_list[] = $row;
}
$stmt->close();

// Hàm định dạng giá
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . 'đ';
}

// Hàm định dạng ngày giờ
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

// Lấy danh sách laptop áp dụng cho từng sự kiện khuyến mãi
$laptop_by_khuyenmai = [];
foreach ($khuyenmai_list as $km) {
    $dieuKien = $km['DieuKien'] ?: '1=1'; // Nếu không có điều kiện, áp dụng cho tất cả
    $sql_laptop = "SELECT l.MaLaptop, l.TenLaptop, h.TenHang, l.GiaBan, l.SoLuong
                   FROM Laptop l 
                   JOIN Hang h ON l.MaHang = h.MaHang 
                   WHERE $dieuKien < l.GiaBan AND l.SoLuong > 0";
    $stmt = $conn->prepare($sql_laptop);
    if ($stmt === false) {
        die('Lỗi chuẩn bị câu lệnh SQL laptop: ' . $conn->error);
    }
    $stmt->execute();
    $result_laptop = $stmt->get_result();
    $laptops = [];
    while ($row = $result_laptop->fetch_assoc()) {
        $laptops[] = $row;
    }
    $laptop_by_khuyenmai[$km['MaKhuyenMai']] = $laptops;
    $stmt->close();
}
?>

<main>
    <h2>Các sự kiện khuyến mãi hiện tại</h2>
    <?php if (empty($khuyenmai_list)): ?>
        <p>Không có sự kiện khuyến mãi nào đang diễn ra.</p>
    <?php else: ?>
        <?php foreach ($khuyenmai_list as $km): ?>
            <div class="khuyenmai-item">
                <h3><?php echo htmlspecialchars($km['TenChuongTrinh']); ?></h3>
                <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($km['MoTa'] ?: 'Không có mô tả'); ?></p>
                <p><strong>Thời gian:</strong> Từ <?php echo formatDateTime($km['NgayBatDau']); ?> đến <?php echo formatDateTime($km['NgayKetThuc']); ?></p>
                <p><strong>Giá trị khuyến mãi:</strong> 
                    <?php 
                    echo $km['PhanTramGiam'] . '%';
                    ?>
                </p>
                <p><strong>Giảm tối đa</strong> 
                    <?php 
                    echo $km['GiamToiDa'];
                    ?>
                </p>
                <p><strong>Điều kiện áp dụng: Giảm giá áp dụng cho những sản phẩm có trị giá lớn hơn </strong> <?php echo htmlspecialchars($km['DieuKien'] ?: 'Áp dụng cho tất cả laptop'); ?></p>

                <h4>Các laptop áp dụng:</h4>
                <?php if (empty($laptop_by_khuyenmai[$km['MaKhuyenMai']])): ?>
                    <p>Không có laptop nào áp dụng được khuyến mãi này.</p>
                <?php else: ?>
                    <table class="laptop-table">
                        <thead>
                            <tr>
                                <th>Tên laptop</th>
                                <th>Hãng</th>
                                <th>Giá gốc</th>
                                <th>Giá sau khuyến mãi</th>
                                <th>Kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laptop_by_khuyenmai[$km['MaKhuyenMai']] as $laptop): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($laptop['TenLaptop']); ?></td>
                                    <td><?php echo htmlspecialchars($laptop['TenHang']); ?></td>
                                    <td><?php echo formatPrice($laptop['GiaBan']); ?></td>
                                    <td>
                                        <?php 
                                        $giaSauKhuyenMai = $laptop['GiaBan'];
                                        $giaSauKhuyenMai = $laptop['GiaBan'] * (1 - $km['PhanTramGiam'] / 100);
                                        echo formatPrice(max(0, $giaSauKhuyenMai));
                                        ?>
                                    </td>
                                    <td><?php echo $laptop['SoLuong']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f9f9f9;
    margin: 0;
    padding: 0;
}

main {
    max-width: 1200px;
    margin: 30px auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    padding: 30px;
}

h2, h3, h4 {
    color: #333;
    margin-bottom: 20px;
}

h2 {
    border-left: 4px solid #007BFF;
    padding-left: 10px;
}

h3 {
    color: #007BFF;
}

.khuyenmai-item {
    border-bottom: 1px solid #ddd;
    padding: 20px 0;
}

.khuyenmai-item:last-child {
    border-bottom: none;
}

.khuyenmai-item p {
    margin: 8px 0;
    color: #555;
    line-height: 1.6;
}

.khuyenmai-item p strong {
    color: #000;
}

.laptop-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.laptop-table th, .laptop-table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.laptop-table th {
    background-color: #f5f5f5;
    color: #333;
}

.laptop-table td {
    color: #555;
}

.laptop-table tbody tr:hover {
    background-color: #f9f9f9;
}

@media screen and (max-width: 600px) {
    main {
        margin: 20px;
        padding: 20px;
    }

    .laptop-table th, .laptop-table td {
        padding: 8px;
        font-size: 14px;
    }

    .laptop-table thead {
        display: none;
    }

    .laptop-table tbody tr {
        display: block;
        margin-bottom: 15px;
        border-bottom: 1px solid #ddd;
    }

    .laptop-table td {
        display: block;
        text-align: right;
        position: relative;
        padding-left: 50%;
        border: none;
        border-bottom: 1px solid #eee;
    }

    .laptop-table td::before {
        content: attr(data-label);
        position: absolute;
        left: 10px;
        width: 45%;
        font-weight: bold;
        color: #333;
    }

    .laptop-table td:last-child {
        border-bottom: none;
    }
}
</style>

<?php include('Footer.php'); ?>