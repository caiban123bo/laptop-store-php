<?php
include 'assets/db.php';

// Fetch categories
$categories = $conn->query("SELECT MaDanhMuc, TenDanhMuc FROM DanhMuc ORDER BY TenDanhMuc");
if (!$categories) die("Query failed: " . $conn->error);

// Fetch brands
$brands = $conn->query("SELECT MaHang, TenHang FROM Hang ORDER BY TenHang");
if (!$brands) die("Query failed: " . $conn->error);

// Fetch distinct values for specs
$cpu_types = $conn->query("SELECT DISTINCT Dong FROM ThongSoKyThuat WHERE Dong IS NOT NULL AND TRIM(Dong) != '' ORDER BY Dong");
$generations = $conn->query("SELECT DISTINCT TheHe FROM ThongSoKyThuat WHERE TheHe IS NOT NULL AND TRIM(TheHe) != '' ORDER BY TheHe");
$architectures = $conn->query("SELECT DISTINCT KienTruc FROM ThongSoKyThuat WHERE KienTruc IS NOT NULL ORDER BY KienTruc");
$rams = $conn->query("SELECT DISTINCT RAM FROM ThongSoKyThuat WHERE RAM IS NOT NULL ORDER BY RAM");
$storages = $conn->query("SELECT DISTINCT OCung FROM ThongSoKyThuat WHERE OCung IS NOT NULL ORDER BY OCung");
$graphics = $conn->query("SELECT DISTINCT CardDoHoa FROM ThongSoKyThuat WHERE CardDoHoa IS NOT NULL ORDER BY CardDoHoa");
$displays = $conn->query("SELECT DISTINCT ManHinh FROM ThongSoKyThuat WHERE ManHinh IS NOT NULL ORDER BY ManHinh");
$oses = $conn->query("SELECT DISTINCT HeDieuHanh FROM ThongSoKyThuat WHERE HeDieuHanh IS NOT NULL ORDER BY HeDieuHanh");

// Debug: Log CPU types and generations
$cpu_values = [];
while ($cpu = $cpu_types->fetch_assoc()) {
    $cpu_values[] = $cpu['Dong'];
}
$cpu_types->data_seek(0); // Reset cursor
error_log("CPU Types: " . print_r($cpu_values, true));

$gen_values = [];
while ($gen = $generations->fetch_assoc()) {
    $gen_values[] = $gen['TheHe'];
}
$generations->data_seek(0); // Reset cursor
error_log("Generations: " . print_r($gen_values, true));

// Get current filter values from GET
$selected_categories = isset($_GET['category']) ? (array)$_GET['category'] : [];
$selected_brands = isset($_GET['brand']) ? (array)$_GET['brand'] : [];
$selected_cpu_types = isset($_GET['cpu_type']) ? (array)$_GET['cpu_type'] : [];
$selected_generations = isset($_GET['generation']) ? (array)$_GET['generation'] : [];
$selected_architectures = isset($_GET['architecture']) ? (array)$_GET['architecture'] : [];
$selected_ram = isset($_GET['ram']) ? $_GET['ram'] : '';
$selected_storage = isset($_GET['storage']) ? $_GET['storage'] : '';
$selected_graphics = isset($_GET['graphics']) ? $_GET['graphics'] : '';
$selected_display = isset($_GET['display']) ? $_GET['display'] : '';
$selected_os = isset($_GET['os']) ? $_GET['os'] : '';
?>

<style>
.sidebar {
    background: linear-gradient(180deg, #ffffff, #f8f8f8);
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    padding: 20px;
    position: sticky;
    top: 20px;
    transition: all 0.3s ease;
}

.sidebar details {
    margin-bottom: 15px;
}

.sidebar summary {
    background: #ff0000;
    color: white;
    padding: 12px;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar summary:hover {
    background: #e60000;
}

.sidebar summary::after {
    content: '▾';
    font-size: 14px;
}

.sidebar details[open] summary::after {
    content: '▴';
}

.sidebar ul {
    padding: 10px 0;
}

.sidebar ul li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    color: #333;
    transition: color 0.2s ease;
    min-height: 24px;
}

.sidebar ul li:hover {
    color: #ff0000;
}

.sidebar ul li span {
    flex: 1;
    font-size: 14px;
}

.sidebar input[type="checkbox"] {
    appearance: none;
    width: 16px;
    height: 16px;
    border: 2px solid #ff0000;
    border-radius: 4px;
    margin-right: 10px;
    cursor: pointer;
    position: relative;
}

.sidebar input[type="checkbox"]:checked {
    background: #ff0000;
}

.sidebar input[type="checkbox"]:checked::after {
    content: '✔';
    color: white;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 12px;
}

.sidebar select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #fff;
    color: #333;
    font-size: 14px;
    margin-top: 10px;
    cursor: pointer;
    transition: border-color 0.3s ease;
}

.sidebar select:focus {
    outline: none;
    border-color: #ff0000;
}

.sidebar a {
    color: #333;
    text-decoration: none;
    display: block;
    padding: 8px 0;
    transition: color 0.2s ease;
}

.sidebar a:hover {
    color: #ff0000;
}

.sidebar button {
    background: linear-gradient(90deg, #ff0000, #e60000);
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    width: 100%;
    font-weight: bold;
    cursor: pointer;
    transition: transform 0.2s ease, background 0.3s ease;
}

.sidebar button:hover {
    background: linear-gradient(90deg, #e60000, #cc0000);
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        position: static;
        margin-bottom: 20px;
    }
}
</style>

<aside class="sidebar">
    <form action="index.php" method="GET">
        <details open>
            <summary>DANH MỤC</summary>
            <ul>
                <li>
                    <input type="checkbox" name="category[]" value="all" <?php echo in_array('all', $selected_categories) ? 'checked' : ''; ?>> <span>Tất Cả</span>
                </li>
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="category[]" value="<?php echo $category['MaDanhMuc']; ?>" <?php echo in_array($category['MaDanhMuc'], $selected_categories) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(trim($category['TenDanhMuc'])) ?: 'Unknown Category'; ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </details>

        <details open>
            <summary>HÃNG</summary>
            <ul>
                <?php while ($brand = $brands->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="brand[]" value="<?php echo $brand['MaHang']; ?>" <?php echo in_array($brand['MaHang'], $selected_brands) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(trim($brand['TenHang'])) ?: 'Unknown Brand'; ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </details>

        <details open>
            <summary>LỌC THEO GIÁ</summary>
            <ul>
                <li><a href="index.php?price=all">Tất Cả</a></li>
                <li><a href="index.php?price=under10">Dưới 10 triệu</a></li>
                <li><a href="index.php?price=10to20">10 - 20 triệu</a></li>
                <li><a href="index.php?price=20to30">20 - 30 triệu</a></li>
                <li><a href="index.php?price=over30">Trên 30 triệu</a></li>
            </ul>
        </details>

        <details>
            <summary>CPU</summary>
            <ul>
                <?php while ($cpu = $cpu_types->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="cpu_type[]" value="<?php echo htmlspecialchars(trim($cpu['Dong'])); ?>" <?php echo in_array(trim($cpu['Dong']), $selected_cpu_types) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(trim($cpu['Dong'])) ?: 'Unknown CPU'; ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </details>

        <details>
            <summary>THẾ HỆ CPU</summary>
            <ul>
                <?php while ($gen = $generations->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="generation[]" value="<?php echo htmlspecialchars(trim($gen['TheHe'])); ?>" <?php echo in_array(trim($gen['TheHe']), $selected_generations) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(trim($gen['TheHe'])) ?: 'Unknown Generation'; ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </details>

        <details>
            <summary>KIẾN TRÚC</summary>
            <ul>
                <?php while ($arch = $architectures->fetch_assoc()): ?>
                    <li>
                        <input type="checkbox" name="architecture[]" value="<?php echo htmlspecialchars(trim($arch['KienTruc'])); ?>" <?php echo in_array(trim($arch['KienTruc']), $selected_architectures) ? 'checked' : ''; ?>>
                        <span><?php echo htmlspecialchars(trim($arch['KienTruc'])) ?: 'Unknown Architecture'; ?></span>
                    </li>
                <?php endwhile; ?>
            </ul>
        </details>

        <details>
            <summary>RAM</summary>
            <select name="ram">
                <option value="">Tất Cả</option>
                <?php while ($ram = $rams->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars(trim($ram['RAM'])); ?>" <?php echo $selected_ram === trim($ram['RAM']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(trim($ram['RAM'])) ?: 'Unknown RAM'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </details>

        <details>
            <summary>Ổ CỨNG</summary>
            <select name="storage">
                <option value="">Tất Cả</option>
                <?php while ($storage = $storages->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars(trim($storage['OCung'])); ?>" <?php echo $selected_storage === trim($storage['OCung']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(trim($storage['OCung'])) ?: 'Unknown Storage'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </details>

        <details>
            <summary>CARD ĐỒ HỌA</summary>
            <select name="graphics">
                <option value="">Tất Cả</option>
                <?php while ($graphic = $graphics->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars(trim($graphic['CardDoHoa'])); ?>" <?php echo $selected_graphics === trim($graphic['CardDoHoa']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(trim($graphic['CardDoHoa'])) ?: 'Unknown Graphics'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </details>

        <details>
            <summary>MÀN HÌNH</summary>
            <select name="display">
                <option value="">Tất Cả</option>
                <?php while ($display = $displays->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars(trim($display['ManHinh'])); ?>" <?php echo $selected_display === trim($display['ManHinh']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(trim($display['ManHinh'])) ?: 'Unknown Display'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </details>

        <details>
            <summary>HỆ ĐIỀU HÀNH</summary>
            <select name="os">
                <option value="">Tất Cả</option>
                <?php while ($os = $oses->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars(trim($os['HeDieuHanh'])); ?>" <?php echo $selected_os === trim($os['HeDieuHanh']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(trim($os['HeDieuHanh'])) ?: 'Unknown OS'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </details>

        <button type="submit">Lọc</button>
    </form>
</aside>

<?php
$categories->free();
$brands->free();
$cpu_types->free();
$generations->free();
$architectures->free();
$rams->free();
$storages->free();
$graphics->free();
$displays->free();
$oses->free();
?>