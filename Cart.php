<?php
include 'header.php';


$cart_empty = false;
?>

<div class="cart-container">
    <div class="cart-items">
        <?php if ($cart_empty): ?>
            <div class="empty-cart">
                <p>Chưa có sản phẩm trong giỏ hàng</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Tạm tính</th>
                        <th>Cộng giỏ hàng</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <img src="images/laptop1.jpg" alt="Laptop Dell XPS">
                            <span>Đèn pin ĐQ PFLO3 (R) pin sạc</span>
                        </td>
                        <td>300.000đ</td>
                        <td class="quantity"><input type="number" value="1" min="1"></td>
                        <td>300.000đ</td>
                        <td class="remove">Xóa</td>
                    </tr>
                    <tr>
                        <td>
                            <img src="images/laptop2.jpg" alt="Laptop MacBook Air">
                            <span>Đèn LED Bulb GL Đuôi Quang ĐQ LEDBULBGL 3W</span>
                        </td>
                        <td>150.000đ</td>
                        <td class="quantity"><input type="number" value="1" min="1"></td>
                        <td>150.000đ</td>
                        <td class="remove">Xóa</td>
                    </tr>
                    <tr>
                        <td>
                            <img src="images/phukien1.jpg" alt="Chuột không dây">
                            <span>Cầu dao chống giật và bảo mạch Hùng Lượng 128CB</span>
                        </td>
                        <td>190.000đ</td>
                        <td class="quantity"><input type="number" value="1" min="1"></td>
                        <td>190.000đ</td>
                        <td class="remove">Xóa</td>
                    </tr>
                    <tr>
                        <td>
                            <img src="images/phukien2.jpg" alt="Bàn phím cơ">
                            <span>Đèn LED Bulb Chuyên Dụng Thanh ĐQ LEDBUTL 04R8 NN</span>
                        </td>
                        <td>59.000đ</td>
                        <td class="quantity"><input type="number" value="1" min="1"></td>
                        <td>59.000đ</td>
                        <td class="remove">Xóa</td>
                    </tr>
                </tbody>
            </table>
            <div class="cart-actions">
                <button class="continue-shopping">Tiếp tục xem sản phẩm</button>
                <button class="update-cart">Cập nhật giỏ hàng</button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cart Summary -->
    <div class="cart-summary">
        <h3>Cộng giỏ hàng</h3>
        <p>Tạm tính: <?php echo $cart_empty ? '0đ' : '699.000đ'; ?></p>
        <p class="total">Tổng: <?php echo $cart_empty ? '0đ' : '699.000đ'; ?></p>
        <a href="CheckOut.php">
            <button>Tiến hành thanh toán</button>
        </a>
    </div>
</div>

<?php
include 'footer.php';
?>