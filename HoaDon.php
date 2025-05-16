<?php

$order = [
    'order_id' => '20250210754330677AE8BC4TC',
    'date' => '10/02/2025',
    'status' => 'pending',
    'email' => 'admin@gmail.com',
    'total' => 21500000,
    'items' => [
        [
            'name' => 'ĐÈN LED BULB GL ĐIỆN QUANG ĐQ LEDBULB...',
            'price' => 150000,
            'quantity' => 1,
            'subtotal' => 150000
        ],
        [
            'name' => 'CHUỘI ĐÈN LIỀN CÔNG TẮC ĐIỆN QUANG ...',
            'price' => 21000000,
            'quantity' => 1,
            'subtotal' => 21000000
        ]
    ]
];
$shipping_info = [
    'name' => 'Nguyễn Văn Anh',
    'address' => '123/456/789, Hẻm 32, Thành Bình Định',
    'phone' => '0123456789'
];

$billing_info = [
    'name' => 'Nguyễn Văn Anh',
    'address' => '123/456/789, Hẻm 32, Thành Bình Định',
    'phone' => '0123456789',
    'note' => 'Xin chú shop giao hàng nhanh giúp tui'
];
?>

<?php include 'Header.php'; ?>

<div class="checkout-container">
    <div class="order-details">
        <h2>Đơn hàng của bạn</h2>
        <div class="info-section">
            <p><span>Mã đơn hàng:</span> <?php echo $order['order_id']; ?></p>
            <p><span>Ngày:</span> <?php echo $order['date']; ?></p>
            <p><span>Trạng thái:</span> <?php echo $order['status']; ?></p>
            <p><span>Email:</span> <?php echo $order['email']; ?></p>
            <p><span>Phương thức:</span> Trả tiền khi nhận hàng</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Tổng</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <img src="https://via.placeholder.com/40" alt="Product Image">
                            <?php echo $item['name']; ?><br>
                            Giá: <?php echo number_format($item['price'], 0, ',', '.'); ?>đ *
                            <?php echo $item['quantity']; ?>
                        </td>
                        <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            <p class="total">Tổng cộng: <span><?php echo number_format($order['total'], 0, ',', '.'); ?>đ</span></p>
        </div>
    </div>
    <div class="row">
        <div class="shipping-info">
            <h1>Chi tiết đơn hàng</h1>
            <p>Cảm ơn bạn. Đơn hàng của bạn đã được nhận.</p>
            <p><strong>Mã đơn hàng:</strong> <?php echo $order['order_id']; ?></p>
            <p><strong>Ngày:</strong> <?php echo $order['date']; ?></p>
            <p><strong>Trạng thái:</strong> <?php echo $order['status']; ?></p>
            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
            <p><strong>Tổng:</strong> <?php echo number_format($order['total'], 0, ',', '.'); ?>đ</p>
            <p><strong>Phương thức:</strong> Trả tiền khi nhận hàng</p>
        </div>

        <div class="shipping-info">
            <h1>Địa chỉ thanh toán</h1>
            <p><strong>Họ và tên QTV:</strong> <?php echo $billing_info['name']; ?></p>
            <p><strong>Địa chỉ:</strong> <?php echo $billing_info['address']; ?></p>
            <p><strong>SĐT liên hệ:</strong> <?php echo $billing_info['phone']; ?></p>
            <p><strong>Ghi chú:</strong> <?php echo $billing_info['note']; ?></p>
            <a href="GiaoDienChinh.php">
                <button>Tiếp tục mua hàng</button>
            </a>
        </div>
    </div>
</div>

<?php include 'Footer.php'; ?>