<?php
include 'header.php';
?>
<div class="checkout-container">
    <div class="order-details">
        <h2>Thanh toán đơn hàng</h2>
        <table>
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Tạm tính</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <img src="images/laptop1.jpg" alt="Đèn pin">
                        <span>Đèn pin ĐQ PFLO3 (R) pin sạc</span>
                    </td>
                    <td>1</td>
                    <td>300.000đ</td>
                </tr>
                <tr>
                    <td>
                        <img src="images/laptop2.jpg" alt="Đèn LED Bulb">
                        <span>Đèn LED Bulb GL Đuôi Quang ĐQ LEDBULBGL 3W</span>
                    </td>
                    <td>1</td>
                    <td>150.000đ</td>
                </tr>
                <tr>
                    <td>
                        <img src="images/phukien1.jpg" alt="Cầu dao">
                        <span>Cầu dao chống giật và bảo mạch Hùng Lượng 128CB</span>
                    </td>
                    <td>1</td>
                    <td>190.000đ</td>
                </tr>
                <tr>
                    <td>
                        <img src="images/phukien2.jpg" alt="Đèn LED Bulb">
                        <span>Đèn LED Bulb Chuyên Dụng Thanh ĐQ LEDBUTL 04R8 NN</span>
                    </td>
                    <td>1</td>
                    <td>59.000đ</td>
                </tr>
            </tbody>
        </table>
        <p class="total">Tổng: 699.000đ</p>

        <div class="payment-methods">
            <h4>Hình thức thanh toán</h4>
            <label><input type="radio" name="payment" value="cod" checked> Thanh toán tiền mặt khi nhận hàng</label>
            <label><input type="radio" name="payment" value="vnpay"> Thanh toán qua VNPAY</label>
        </div>
        <div class="voucher">
            <h4>Mã Voucher</h4>
            <input type="text" placeholder="Nhập mã voucher">
            <button>Áp dụng</button>
        </div>
    </div>
    <div class="shipping-info">
        <h2>Thông tin giao hàng</h2>
        <form action="HoaDon.php" method="post">
            <label for="full-name">Họ và tên *</label>
            <input type="text" id="full-name" name="full-name" placeholder="Nhập họ và tên" required>

            <label for="phone">Số điện thoại *</label>
            <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" required>

            <label for="province">Tỉnh/Thành *</label>
            <select id="province" name="province" required>
                <option value="">Chọn tỉnh/thành</option>
                <option value="hcm">TP. Hồ Chí Minh</option>
                <option value="hn">Hà Nội</option>
                <option value="dn">Đà Nẵng</option>
            </select>

            <label for="district">Quận/Huyện *</label>
            <select id="district" name="district" required>
                <option value="">Chọn quận/huyện</option>
                <option value="q1">Quận 1</option>
                <option value="q2">Quận 2</option>
                <option value="q3">Quận 3</option>
            </select>

            <label for="ward">Phường/Xã *</label>
            <select id="ward" name="ward" required>
                <option value="">Chọn phường/xã</option>
                <option value="p1">Phường 1</option>
                <option value="p2">Phường 2</option>
                <option value="p3">Phường 3</option>
            </select>

            <label for="address">Địa chỉ *</label>
            <input type="text" id="address" name="address" placeholder="Nhập địa chỉ chi tiết" required>

            <label for="note">Ghi chú đơn hàng (tùy chọn)</label>
            <textarea id="note" name="note" rows="3" placeholder="Ghi chú (ví dụ: Giao hàng nhanh...)"></textarea>

            <button type="submit">Đặt hàng</button>
        </form>
    </div>
</div>

<?php
include 'footer.php';
?>