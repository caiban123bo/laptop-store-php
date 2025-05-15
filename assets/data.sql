-- Insert brands into Hang table
INSERT INTO Hang (TenHang, HinhAnh, MoTa) VALUES
('MSI', 'path_or_url_to_msi_logo', 'MSI is a leading brand for gaming laptops'),
('Acer', 'path_or_url_to_acer_logo', 'Acer is known for powerful gaming and productivity laptops'),
('Lenovo', 'path_or_url_to_lenovo_logo', 'Lenovo offers reliable laptops for business and personal use'),
('Asus', 'path_or_url_to_asus_logo', 'Asus produces high-performance laptops for various categories'),
('HP', 'path_or_url_to_hp_logo', 'HP offers a range of laptops for personal and business use'),
('Apple', 'path_or_url_to_apple_logo', 'Apple is renowned for premium ultrabooks and performance');

-- Insert categories into DanhMuc table
INSERT INTO DanhMuc (TenDanhMuc, MoTa) VALUES
('Gaming', 'Laptops designed for gaming with high-performance hardware'),
('Ultrabook', 'Slim, lightweight laptops with long battery life'),
('Workstation', 'High-end laptops for professional work environments'),
('Business', 'Laptops designed for business use, focusing on productivity');

-- Insert laptops' technical specifications into ThongSoKyThuat table (unique for each laptop)
INSERT INTO ThongSoKyThuat (TenCPU, Dong, TheHe, KienTruc, RAM, OCung, CardDoHoa, ManHinh, HeDieuHanh, KhoiLuong, KichThuoc, Pin, Nam) VALUES
('Ultra 9-285HX', 'i9', '13th', 'x86', '96GB', '6TB SSD', 'GeForce RTX™ 5090', '18 inches', 'Windows 11', '5kg', '40x30x2 cm', '10 hours', 2024),  -- MaThongSo = 1
('Ultra 9-285HX', 'i9', '13th', 'x86', '64GB', '6TB SSD', 'GeForce RTX™ 5080', '18 inches', 'Windows 11', '5kg', '40x30x2 cm', '10 hours', 2024),  -- MaThongSo = 2
('Ultra 9 275HX', 'i9', '13th', 'x86', '16GB', '1TB SSD', 'GeForce RTX™ 5080', '16 inches', 'Windows 11', '2.5kg', '35x25x2 cm', '8 hours', 2024),  -- MaThongSo = 3
('Ultra 5-226V', 'i5', '12th', 'x86', '16GB', '1TB SSD', 'GeForce RTX™ 4070', '14 inches', 'Windows 11', '1.5kg', '32x22x1.5 cm', '10 hours', 2024),  -- MaThongSo = 4
('Ryzen AI 9 HX 370', 'Ryzen 9', 'HX', 'x86', '32GB', '2TB SSD', 'GeForce RTX™ 4070', '16 inches', 'Windows 11', '2.8kg', '36x25x2 cm', '9 hours', 2024),  -- MaThongSo = 5
('Ryzen AI 9 HX 370', 'Ryzen 7', 'Zen 4', 'x86', '32GB', '1TB SSD', 'GeForce RTX™ 4060', '15.6 inches', 'Windows 11 + Office', '2.2kg', '35x25x1.8 cm', '8 hours', 2024),  -- MaThongSo = 6
('Ultra 9-185H', 'i9', 'Ultra', 'x86', '64GB', '2TB SSD', 'GeForce RTX™ 4080', '18 inches', 'Windows 11', '5kg', '40x30x2.5 cm', '10 hours', 2024),  -- MaThongSo = 7
('i9-13900H', 'i9', '13th', 'x86', '32GB', '2TB SSD', 'GeForce RTX™ 4070', '16 inches', 'Windows 11', '3kg', '37x26x2 cm', '9 hours', 2024),  -- MaThongSo = 8
('AMD', 'Ryzen 5', 'Zen 4', 'x86', '16GB', '512GB SSD', 'GeForce RTX™ 3050', '15 inches', 'Windows 11', '2kg', '36x25x1.8 cm', '8 hours', 2024),  -- MaThongSo = 9
('Intel', 'i5', '12th', 'x86', '8GB', '512GB SSD', 'Onboard', '15.6 inches', 'Windows 11', '1.7kg', '37x26x1.8 cm', '7 hours', 2024),  -- MaThongSo = 10
('AMD', 'Ryzen 5', 'Zen 4', 'x86', '16GB', '512GB SSD', 'GeForce RTX™ 2050', '15 inches', 'Windows 11', '1.8kg', '36x25x2 cm', '6 hours', 2024),  -- MaThongSo = 11
('Intel', 'i7', '12th', 'x86', '16GB', '512GB SSD', 'GeForce RTX™ 3050', '16 inches', 'Windows 11', '2.1kg', '35x24x2 cm', '8 hours', 2024),  -- MaThongSo = 12
('Intel', 'i9', '13th', 'x86', '32GB', '2TB SSD', 'GeForce RTX™ 4070', '16 inches', 'Windows 11', '3kg', '37x26x2.1 cm', '9 hours', 2024),  -- MaThongSo = 13
('Intel', 'i9', '13th', 'x86', '64GB', '2TB SSD', 'GeForce RTX™ 4080', '18 inches', 'Windows 11', '5kg', '40x30x2.5 cm', '10 hours', 2024),  -- MaThongSo = 14
('Intel', 'i5', '12th', 'x86', '16GB', '512GB SSD', 'GeForce RTX™ 3050', '15.6 inches', 'Windows 11', '1.7kg', '37x26x1.8 cm', '7 hours', 2024);  -- MaThongSo = 15
-- Insert technical specifications into ThongSoKyThuat table
INSERT INTO ThongSoKyThuat (TenCPU, Dong, TheHe, KienTruc, RAM, OCung, CardDoHoa, ManHinh, HeDieuHanh, KhoiLuong, KichThuoc, Pin, Nam)
VALUES
('i7-13700HX', 'i7', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 4070', '16" Full HD/ IPS/ 144Hz', 'Windows 11', '2.1kg', '36.0 x 25.0 x 2.3 cm', '6-Cell 90Whr', 2023),
('i5-13450HX', 'i5', '13th', 'x86', '12GB', '512GB', 'GeForce RTX™ 3050', '15.6" Full HD/ IPS/ 144Hz', 'Windows 11', '1.8kg', '35.0 x 24.0 x 2.0 cm', '4-Cell 56Whr', 2023),
('i7-13620H', 'i7', '12th', 'x86', '24GB', '512GB', 'Intel UHD', '14" WUXGA/ IPS', 'Windows 11', '1.6kg', '32.0 x 22.0 x 1.9 cm', '3-Cell 50Whr', 2023),
('i5-13420H', 'i5', '12th', 'x86', '24GB', '512GB', 'Intel UHD', '15.3" WUXGA/ IPS/ 60Hz', 'Windows 11', '1.7kg', '35.5 x 24.5 x 2.1 cm', '4-Cell 60Whr', 2023),
('Snapdragon X1P 42 100', 'ARM', 'Ultra', 'ARM', '16GB', '512GB', 'Adreno', '14" 2.2K/ IPS', 'Windows 11 + Office', '1.5kg', '33.5 x 23.0 x 1.8 cm', '4-Cell 50Whr', 2023),
('U7-258V', 'Ultra', 'Ultra', 'x86', '32GB', '1TB', 'Arc 140V', '14" 2.8K/ OLED/ 120Hz', 'Windows 11 + Office', '1.6kg', '32.0 x 22.5 x 1.9 cm', '4-Cell 55Whr', 2023),
('U9-288V', 'Ultra', 'Ultra', 'x86', '32GB', '1TB', 'Arc 140V', '14" 2.8K/ OLED/ 120Hz', 'Windows 11 + Office', '1.7kg', '33.0 x 23.0 x 2.0 cm', '4-Cell 55Whr', 2023),
('Apple M2', 'i5', 'Zen 4', 'ARM', '16GB', '512GB', 'Integrated', '13.6" (2560 x 1664)', 'macOS', '1.3kg', '30.0 x 20.0 x 1.6 cm', '2-Cell 52Whr', 2022),
('Apple M2', 'i5', 'Zen 4', 'ARM', '16GB', '512GB', 'Integrated', '13.6" (2560 x 1664)', 'macOS', '1.3kg', '30.0 x 20.0 x 1.6 cm', '2-Cell 52Whr', 2022),
('Apple M3', 'i5', 'Zen 5', 'ARM', '24GB', '512GB', 'Integrated', '13.6" (2560 x 1664)', 'macOS', '1.3kg', '30.0 x 20.0 x 1.6 cm', '2-Cell 55Whr', 2023),
('Apple M3', 'i5', 'Zen 5', 'ARM', '24GB', '512GB', 'Integrated', '13.6" (2560 x 1664)', 'macOS', '1.3kg', '30.0 x 20.0 x 1.6 cm', '2-Cell 55Whr', 2023),
('Core 5 210H', 'i5', '11th', 'x86', '32GB', '512GB', 'GeForce RTX™ 3050', '16" WUXGA/ IPS/ 144Hz', 'Windows 11', '1.9kg', '36.0 x 25.5 x 2.5 cm', '4-Cell 60Whr', 2023),
('R5 8645HS', 'r5', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 3050', '16.1" Full HD/ IPS/ 165Hz', 'Windows 11', '2.0kg', '37.0 x 26.0 x 2.6 cm', '4-Cell 60Whr', 2023);
INSERT INTO ThongSoKyThuat (TenCPU, Dong, TheHe, KienTruc, RAM, OCung, CardDoHoa, ManHinh, HeDieuHanh, KhoiLuong, KichThuoc, Pin, Nam) VALUES
('i9-13900H', 'i9', '13th', 'x86', '16GB', '512GB', 'Intel UHD', '15.6" Full HD/ IPS/ 60Hz', 'Windows 11', '2kg', '15.6"', '4 cells', 2023),
('i7-13700HX', 'i7', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 4060', '16.1" Full HD/ IPS/ 165Hz', 'Windows 11', '2.2kg', '16.1"', '4 cells', 2023),
('i7-13700HX', 'i7', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 4050', '16.1" Full HD/ IPS/ 165Hz', 'Windows 11', '2.2kg', '16.1"', '4 cells', 2023),
('i5-13500HX', 'i5', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 3050', '16.1" Full HD/ IPS/ 165Hz', 'Windows 11', '2.1kg', '16.1"', '4 cells', 2023),
('i5-13500HX', 'i5', '13th', 'x86', '16GB', '512GB', 'GeForce RTX™ 4050', '16.1" Full HD/ IPS/ 165Hz', 'Windows 11', '2.1kg', '16.1"', '4 cells', 2023);


ALTER TABLE Laptop AUTO_INCREMENT = 1;
-- Insert laptops into Laptop table (using unique MaThongSo for each laptop)
INSERT INTO Laptop (TenLaptop, MaHang, MaDanhMuc, MaThongSo, GiaGoc, GiaBan, SoLuong, MoTa, LuotXem, TrangThai, NgayTao, NgayCapNhat)
VALUES
('Msi Titan 18 HX AI A2XWJG-035VN', 1, 1, 1, 179990000, 179990000, 45, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Msi Titan 18 HX AI A2XWIG-090VN', 1, 1, 2, 149990000, 149990000, 12, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Msi Vector 16 HX AI A2XWIG-062VN', 1, 1, 3, 74990000, 74990000, 28, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ACER Swift 14 AI OLED SF14-51-53P9', 2, 2, 4, 34090000, 34090000, 33, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Msi Stealth A16 AI+ A3XVGG - 208VN', 1, 1, 5, 68890000, 68890000, 19, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Asus Zenbook S 14 UX5406SA-PV140WS', 4, 2, 6, 42490000, 42490000, 7, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ASUS ROG Zephyrus G16 GA605WI 2024', 4, 1, 7, 77190000, 77190000, 41, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Asus Vivobook 15X Oled M3504YA-L1268W', 4, 2, 8, 25490000, 25490000, 24, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Acer Predator Helios Neo 14 PHN14-51-96HG', 2, 1, 9, 58390000, 58390000, 36, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ASUS TUF Gaming A14 2024', 4, 1, 10, 42490000, 42490000, 15, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Acer Gaming Aspire 7 A715-76G-5806', 2, 1, 11, 18990000, 18990000, 29, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Asus Vivobook S 16 OLED S5606MA-MX051W', 4, 2, 12, 25490000, 25490000, 8, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ASUS TUF Gaming A16 FA706IU', 4, 1, 13, 31590000, 31590000, 44, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Acer Aspire 3 A315-58-55TL', 2, 1, 14, 23990000, 23990000, 21, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('ASUS Zenbook 15 UX425E-UB73', 4, 2, 15, 47990000, 47990000, 32, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Acer Predator Helios Neo 16 - PHN16-71-74QR', 2, 1, 16, 49990000, 49990000, 42, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Lenovo LOQ 15IRX9 - 83DV012LVN', 3, 1, 17, 24490000, 24490000, 15, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Lenovo IdeaPad Slim 3 14IRH10 - 83K00009VN', 3, 2, 18, 20490000, 20490000, 28, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Lenovo IdeaPad Slim 3 15IRH10 - 83K1000GVN', 3, 2, 19, 17490000, 17490000, 33, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP OmniBook X 14-fe1010QU - B53KBPA', 5, 4, 20, 31990000, 31990000, 19, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP OmniBook Ultra Flip 14-fh0040TU - B13VHPA', 5, 4, 21, 53990000, 53990000, 7, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP OmniBook Ultra Flip 14-fh0038TU - B2CP4PA', 5, 4, 22, 57990000, 57900000, 46, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Apple MacBook Air M2 13.6"', 6, 2, 23, 27990000, 27990000, 12, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Apple MacBook Air M3 13.6"', 6, 2, 24, 34290000, 34290000, 4, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Asus Gaming K16 K3607VJ-RP131W', 4, 1, 25, 22990000, 22990000, 31, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP Victus 16-S1149AX - AZ0D4PA', 5, 1, 26, 26490000, 26490000, 22, 'placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('Acer Aspire 5 A515-58P-9841', 2, 1, 27, 20990000, 20990000, 10, 'Placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP Victus 16-R0366TX - AY8X9PA', 5, 1, 28, 35990000, 35990000, 5, 'Placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP Victus 16-r0369TX - AY8Y2PA', 5, 1, 29, 34590000, 34590000, 17, 'Placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP Victus 16-r0378TX - AY8Z4PA', 5, 1, 30, 27990000, 27990000, 29, 'Placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('HP Victus 16-R0302TX - AE0N9PA', 5, 1, 31, 30990000, 30990000, 36, 'Placeholder', FLOOR(RAND()*(999-100+1))+100, 'ConHang', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);