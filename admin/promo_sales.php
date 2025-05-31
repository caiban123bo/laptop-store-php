<?php
require 'sidebar.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';
require '../libs/PHPMailer/Exception.php';
require '../assets/mail_information.php';

use PHPMailer\PHPMailer\PHPMailer;

// 1) Fetch & check events
$events = $pdo->query("SELECT * FROM KhuyenMai WHERE MaGiamGia IS NULL")->fetchAll();
function checkEventUsability(array $e): array {
    $now = date('Y-m-d');
    $usable = true; $reason = 'Có thể sử dụng';
    if ($e['NgayKetThuc'] < $now) { $usable = false; $reason = 'Sự kiện đã hết hạn'; }
    elseif ($e['NgayBatDau'] > $now) { $usable = false; $reason = 'Sự kiện chưa bắt đầu'; }
    elseif ((int)$e['PhanTramGiam'] <= 0) { $usable = false; $reason = 'Tỷ lệ giảm không hợp lệ'; }
    $v = (int)$e['PhanTramGiam'];
    $display = "$v%";
    $msg = "Giảm $v%";
    if ($e['GiamToiDa'] > 0) {
        $display .= " (tối đa " . number_format($e['GiamToiDa'], 0, ',', '.') . " VND)";
    }
    return ['usable'=>$usable, 'reason'=>$reason, 'DISPLAY_VALUE'=>$display, 'DISCOUNT_MSG'=>$msg];
}
$checked_events = [];
foreach ($events as $e) {
    $checked_events[] = array_merge($e, checkEventUsability($e));
}

// 2) Fetch & check coupons
$coupons = $pdo->query("SELECT k.*, 
    (SELECT COUNT(*) FROM DonHang d WHERE d.MaKhuyenMai = k.MaKhuyenMai AND d.TrangThai != 'DaHuy') AS used 
    FROM KhuyenMai k WHERE k.MaGiamGia IS NOT NULL")->fetchAll();
function checkCouponUsability(array $c): array {
    $now = date('Y-m-d');
    $usable = true; $reason = 'Có thể sử dụng';
    if ($c['used'] >= $c['SoLuong']) { $usable = false; $reason = 'Hết lượt sử dụng'; }
    elseif ($c['NgayKetThuc'] < $now) { $usable = false; $reason = 'Mã đã hết hạn'; }
    elseif ((int)$c['PhanTramGiam'] <= 0 && $c['GiamToiDa'] <= 0) { $usable = false; $reason = 'Giá trị giảm không hợp lệ'; }
    $display = $c['PhanTramGiam'] > 0 ? "{$c['PhanTramGiam']}%" : number_format($c['GiamToiDa'], 0, ',', '.') . " VND";
    $msg = $c['PhanTramGiam'] > 0 ? "Giảm {$c['PhanTramGiam']}%" : "Giảm " . number_format($c['GiamToiDa'], 0, ',', '.') . " VND";
    if ($c['PhanTramGiam'] > 0 && $c['GiamToiDa'] > 0) {
        $display .= " (tối đa " . number_format($c['GiamToiDa'], 0, ',', '.') . " VND)";
    }
    return ['usable'=>$usable, 'reason'=>$reason, 'DISPLAY_VALUE'=>$display, 'DISCOUNT_MSG'=>$msg];
}
$checked_coupons = [];
foreach ($coupons as $c) {
    $checked_coupons[] = array_merge($c, checkCouponUsability($c));
}

// 3) Fetch user favorites
$favs = $pdo->query("
    SELECT y.MaNguoiDung, u.Email, u.HoTen, l.TenLaptop, l.GiaBan
    FROM YeuThich y
    JOIN NguoiDung u ON y.MaNguoiDung = u.MaNguoiDung
    JOIN Laptop l ON y.MaLaptop = l.MaLaptop
")->fetchAll();

$user_favs = [];
foreach ($favs as $f) {
    $uid = $f['MaNguoiDung'];
    $user_favs[$uid]['name'] = $f['HoTen'];
    $user_favs[$uid]['email'] = $f['Email'];
    $user_favs[$uid]['products'][] = ['name' => $f['TenLaptop'], 'price' => $f['GiaBan']];
}

// 4) Best-discount helper
function getBestDiscount($price, $events, $coupons) {
    $best = ['type' => null, 'discount' => null, 'amount_saved' => 0];
    foreach ($events as $e) {
        if ($e['usable']) {
            $amt = $price * ($e['PhanTramGiam'] / 100);
            if ($e['GiamToiDa'] > 0 && $amt > $e['GiamToiDa']) $amt = $e['GiamToiDa'];
            if ($amt > $best['amount_saved']) {
                $best = ['type' => 'event', 'discount' => $e, 'amount_saved' => $amt];
            }
        }
    }
    foreach ($coupons as $c) {
        if ($c['usable']) {
            $amt = $c['PhanTramGiam'] > 0 ? $price * ($c['PhanTramGiam'] / 100) : $c['GiamToiDa'];
            if ($c['PhanTramGiam'] > 0 && $c['GiamToiDa'] > 0 && $amt > $c['GiamToiDa']) $amt = $c['GiamToiDa'];
            if ($amt > $best['amount_saved']) {
                $best = ['type' => 'coupon', 'discount' => $c, 'amount_saved' => $amt];
            }
        }
    }
    return $best;
}

// 5) sendEmail helper
function sendEmail($to, $name, $subject, $body) {
    global $mail_username, $mail_password;
    $m = new PHPMailer(true);
    try {
        $m->isSMTP(); $m->Host = 'smtp.gmail.com'; $m->SMTPAuth = true;
        $m->Username = $mail_username; $m->Password = $mail_password;
        $m->SMTPSecure = 'tls'; $m->Port = 587;
        $m->setFrom($mail_username, 'Shop Admin');
        $m->addAddress($to, $name);
        $m->isHTML(true); $m->Subject = $subject; $m->Body = $body;
        $m->send();
        echo "<script>alert('Email đã gửi đến $to');</script>";
    } catch (\Exception $e) {
        echo "<script>alert('Lỗi gửi email: {$m->ErrorInfo}');</script>";
    }
}

// 6) Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $uid = $_POST['user_id'];
    $email = $_POST['email'];
    $name = $_POST['username'];
    $prods = $user_favs[$uid]['products'];
    $promo_sel = $_POST['promo'] ?? 'auto';
    $event_sel = $_POST['selected_event'] ?? null;

    $body = "<p>Xin chào <b>$name</b>,</p><p>Sản phẩm bạn yêu thích:</p><ul>";
    foreach ($prods as $p) {
        $body .= "<li>{$p['name']}</li>";
    }
    $body .= "</ul>";

    $chosen = null;
    if ($promo_sel === 'auto') {
        $global_best = ['amount_saved' => 0];
        foreach ($prods as $p) {
            $b = getBestDiscount($p['price'], $checked_events, $checked_coupons);
            if ($b['amount_saved'] > $global_best['amount_saved']) {
                $global_best = $b;
            }
        }
        $chosen = $global_best;
    } elseif ($promo_sel !== 'auto') {
        foreach ($checked_coupons as $c) {
            if ($c['MaGiamGia'] == $promo_sel && $c['usable']) {
                $chosen = ['type' => 'coupon', 'discount' => $c, 'amount_saved' => 0];
                break;
            }
        }
    }
    if ($event_sel) {
        foreach ($checked_events as $e) {
            if ($e['MaKhuyenMai'] == $event_sel && $e['usable']) {
                $chosen = ['type' => 'event', 'discount' => $e, 'amount_saved' => 0];
                break;
            }
        }
    }

    if ($chosen && $chosen['discount']) {
        if ($chosen['type'] === 'event') {
            $e = $chosen['discount'];
            $body .= "<p>Sự kiện <b>{$e['TenChuongTrinh']}</b>: {$e['DISCOUNT_MSG']}</p>";
        } else {
            $c = $chosen['discount'];
            $body .= "<p>Mã <b>{$c['MaGiamGia']}</b>: {$c['DISCOUNT_MSG']}</p>";
        }
    } else {
        echo "<script>if(!confirm('Không tìm thấy khuyến mãi hợp lệ. Vẫn gửi email?')){history.back();}</script>";
    }

    sendEmail($email, $name, 'Your favorite products are on SALE!!!', $body);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>promo_sale</title>
    <link rel="stylesheet" href="..\assets\admin_style.css">
</head>
<body>
    <div class="layout">
        <?php loadSidebar(); ?>
        <div class="main-content">
            <h2>Các sự kiện khuyến mãi</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tên</th>
                        <th>Mô tả</th>
                        <th>Giảm giá</th>
                        <th>BD</th>
                        <th>KT</th>
                        <th>Trạng thái</th>
                        <th>Chọn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checked_events as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['TenChuongTrinh']) ?></td>
                        <td><?= htmlspecialchars($e['MoTa']) ?></td>
                        <td><?= $e['DISPLAY_VALUE'] ?> (<?= $e['DISCOUNT_MSG'] ?>)</td>
                        <td><?= $e['NgayBatDau'] ?></td>
                        <td><?= $e['NgayKetThuc'] ?></td>
                        <td style="color:<?= $e['usable'] ? 'green' : 'red' ?>"><?= $e['reason'] ?></td>
                        <td><?php if ($e['usable']): ?><input type="radio" name="selected_event" value="<?= $e['MaKhuyenMai'] ?>"><?php else: ?>-<?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h3>Mã giảm giá</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã</th>
                        <th>Giá trị</th>
                        <th>Hết hạn</th>
                        <th>SL dùng/SL tối đa</th>
                        <th>Trạng thái</th>
                        <th>Chọn</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($checked_coupons as $c): ?>
                    <tr>
                        <td><?= $c['MaGiamGia'] ?></td>
                        <td><?= $c['DISPLAY_VALUE'] ?> (<?= $c['DISCOUNT_MSG'] ?>)</td>
                        <td><?= $c['NgayKetThuc'] ?></td>
                        <td><?= $c['used'] ?>/<?= $c['SoLuong'] ?></td>
                        <td style="color:<?= $c['usable'] ? 'green' : 'red' ?>"><?= $c['reason'] ?></td>
                        <td><?php if ($c['usable']): ?><input type="radio" name="selected_coupon" value="<?= $c['MaGiamGia'] ?>"><?php else: ?>-<?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h2>Favorites by Users</h2>
            <?php foreach ($user_favs as $uid => $d): ?>
            <form method="post" style="border:1px solid #ccc;padding:10px;margin:10px;">
                <input type="hidden" name="user_id" value="<?= $uid ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($d['email']) ?>">
                <input type="hidden" name="username" value="<?= htmlspecialchars($d['name']) ?>">
                <p><b><?= $d['name'] ?> (<?= $d['email'] ?>)</b></p>
                <p>Favorites: <?= htmlspecialchars(implode(', ', array_column($d['products'], 'name'))) ?></p>
                <label>Mã giảm giá:
                    <select name="promo">
                        <option value="auto">Tự động</option>
                        <?php foreach ($checked_coupons as $c): if ($c['usable']): ?>
                        <option value="<?= $c['MaGiamGia'] ?>"><?= $c['MaGiamGia'] ?> (<?= $c['DISPLAY_VALUE'] ?>)</option>
                        <?php endif; endforeach; ?>
                    </select>
                </label>
                <br><br>
                <button type="submit" name="send_email">Send Promotion Email</button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>