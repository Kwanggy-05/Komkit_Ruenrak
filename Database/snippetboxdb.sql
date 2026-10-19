-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 06:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `snippetboxdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `bookmark_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `snippet_id` int(11) NOT NULL,
  `bookmarked_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `bookmarks`
--

INSERT INTO `bookmarks` (`bookmark_id`, `user_id`, `snippet_id`, `bookmarked_at`) VALUES
(1, 2, 1, '2026-10-12 01:08:53'),
(2, 3, 1, '2026-10-12 13:37:58');

-- --------------------------------------------------------

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `language_id` int(11) NOT NULL,
  `language_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `language`
--

INSERT INTO `language` (`language_id`, `language_name`) VALUES
(1, 'Python'),
(2, 'PHP'),
(3, 'JavaScript'),
(4, 'HTML/CSS'),
(5, 'SQL'),
(7, 'Cobal');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `snippet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` enum('สแปม','เนื้อหาไม่เหมาะสม','โค้ดอันตราย','อื่นๆ') NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('รอดำเนินการ','ถูกระงับ','ถูกปฏิเสธ') DEFAULT 'รอดำเนินการ',
  `resolved_by` varchar(100) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`report_id`, `snippet_id`, `user_id`, `reason`, `details`, `status`, `resolved_by`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 25, 3, 'สแปม', '', 'ถูกระงับ', 'kwang', '2026-10-12 13:46:09', '2026-10-12 06:40:50', '2026-10-12 06:46:09');

-- --------------------------------------------------------

--
-- Table structure for table `snippet`
--

CREATE TABLE `snippet` (
  `snippet_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `source_code` text NOT NULL,
  `description` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `visibility` enum('public','protected','private') DEFAULT 'private',
  `image_path` varchar(255) DEFAULT NULL,
  `language_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `snippet`
--

INSERT INTO `snippet` (`snippet_id`, `title`, `source_code`, `description`, `created_at`, `visibility`, `image_path`, `language_id`, `user_id`, `deleted`, `is_suspended`) VALUES
(1, 'ฟังก์ชันคำนวณและตัดเกรดนักเรียน', 'def calculate_grade(score):\n    if score >= 80:\n        return \'A\'\n    elif score >= 75:\n        return \'B+\'\n    elif score >= 70:\n        return \'B\'\n    elif score >= 65:\n        return \'C+\'\n    elif score >= 60:\n        return \'C\'\n    elif score >= 55:\n        return \'D+\'\n    elif score >= 50:\n        return \'D\'\n    else:\n        return \'F\'\n\n# ตัวอย่างการใช้งาน\nstudent_score = 78\nprint(f\"คะแนน {student_score} ได้รับเกรด: {calculate_grade(student_score)}\")', 'ฟังก์ชันรับค่าคะแนนรวม (Score) และนำมาเทียบเงื่อนไขแบบขั้นบันไดเพื่อคืนค่าเป็นเกรดตัวอักษรตั้งแต่วันถึง F', '2026-10-12 01:08:50', 'public', 'uploads/1791742130_Screenshot 2026-10-12 010836.png', 1, 2, 0, 0),
(2, 'ฟังก์ชันคำนวณเลขฟีโบนักชี (Fibonacci Sequence)', 'def fibonacci(n):\n    if n <= 0:\n        return []\n    elif n == 1:\n        return [0]\n    \n    fib = [0, 1]\n    while len(fib) < n:\n        fib.append(fib[-1] + fib[-2])\n    return fib\n\n# ตัวอย่างการใช้งาน\nprint(fibonacci(10))', 'ฟังก์ชันสำหรับสร้างลำดับเลขฟีโบนักชีตามจำนวน n ที่กำหนด โดยคืนค่าผลลัพธ์ออกมาเป็นอาเรย์ของตัวเลข', '2026-10-12 01:12:28', 'protected', '', 1, 2, 0, 0),
(3, 'ฟังก์ชันตรวจสอบเลขจำนวนเฉพาะ (Prime Number Checker)', 'def is_prime(n):\n    if n <= 1:\n        return False\n    for i in range(2, int(n**0.5) + 1):\n        if n % i == 0:\n            return False\n    return True\n\nprint(is_prime(17))  # True', 'ฟังก์ชันสำหรับตรวจสอบว่าตัวเลขที่กำหนดเป็นจำนวนเฉพาะหรือไม่ โดยใช้วิธีวนลูปตรวจสอบถึงรากที่สองของตัวเลขนั้น', '2026-10-12 01:13:00', 'public', '', 1, 2, 0, 0),
(4, 'ฟังก์ชันกลับด้านข้อความ (String Reversal)', 'def reverse_string(text):\n    return text[::-1]\n\nprint(reverse_string(\"Hello World\"))', 'ฟังก์ชันสำหรับสลับตัวอักษรในสตริงจากหลังมาหน้าโดยใช้เทคนิค Slicing ของ Python', '2026-10-12 01:13:26', 'protected', '', 1, 2, 0, 0),
(5, 'คำนวณเงินทอน', 'def get_change(amount, coins=[10, 5, 2, 1]):\n    result = {}\n    for coin in coins:\n        count = amount // coin\n        if count > 0:\n            result[coin] = count\n            amount %= coin\n    return result\n\nprint(get_change(48))', 'ฟังก์ชันคำนวณจำนวนเหรียญที่ต้องทอนให้แก่ลูกค้าจากมูลค่าเงินที่กำหนดโดยใช้หลักการ Greedy Algorithm', '2026-10-12 01:13:56', 'public', '', 1, 2, 0, 0),
(6, 'คำสั่งค้นหาพนักงานที่มีเงินเดือนสูง', 'SELECT employee_id, first_name, last_name, salary \nFROM employees \nWHERE salary > 50000 \nORDER BY salary DESC;', 'คำสั่งสำหรับดึงข้อมูลพนักงานที่มีเงินเดือนมากกว่า 50,000 บาท พร้อมจัดเรียงลำดับจากมากไปน้อย', '2026-10-12 01:14:43', 'public', '', 5, 2, 0, 0),
(7, 'ดึงยอดซื้อรวมของลูกค้าแต่ละราย', 'SELECT c.customer_name, SUM(o.total_amount) AS total_spent \nFROM customers c \nJOIN orders o ON c.customer_id = o.customer_id \nGROUP BY c.customer_name;', 'คำสั่งเชื่อมโยงตารางลูกค้าและตารางคำสั่งซื้อ เพื่อคำนวณหายอดเงินรวมที่ลูกค้าแต่ละคนเคยซื้อ', '2026-10-12 01:15:11', 'protected', '', 5, 2, 0, 0),
(8, 'ค้นหาสินค้าที่มีราคาสูงกว่าค่าเฉลี่ย', 'SELECT product_name, price \nFROM products \nWHERE price > (SELECT AVG(price) FROM products);', 'คำสั่งสำหรับคัด and กรองหาสินค้าที่มีราคาแพงกว่าราคาเฉลี่ยของสินค้าทั้งหมดในฐานข้อมูล', '2026-10-12 01:15:38', 'protected', '', 5, 2, 0, 0),
(9, 'กรองหมวดหมู่สินค้าที่มีสต็อกเกินกำหนด', 'SELECT category_id, COUNT(*) AS product_count \nFROM products \nGROUP BY category_id \nHAVING COUNT(*) > 10;', 'คำสั่งจัดกลุ่มข้อมูลสินค้าตามหมวดหมู่ และเลือกแสดงเฉพาะหมวดหมู่ที่มีจำนวนสินค้ามากกว่า 10 รายการขึ้นไป', '2026-10-12 01:16:02', 'protected', '', 5, 2, 0, 0),
(10, 'สร้างตารางข้อมูลผู้ใช้งานระบบ', 'CREATE TABLE users (\n    user_id INT PRIMARY KEY AUTO_INCREMENT,\n    username VARCHAR(50) NOT NULL UNIQUE,\n    email VARCHAR(100) NOT NULL,\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n);', 'คำสั่ง DDL สำหรับสร้างตารางเก็บข้อมูลผู้ใช้งาน พร้อมกำหนดเงื่อนไข Primary Key, Unique และค่าเริ่มต้นของเวลา', '2026-10-12 01:16:40', 'public', '', 5, 2, 0, 0),
(11, 'ปุ่มกดเอฟเฟกต์แสงนีออน (Neon Button CSS)', '\n\n<style>\n.neon-btn {\n    background: transparent;\n    color: #0ff;\n    border: 2px solid #0ff;\n    padding: 12px 24px;\n    font-size: 16px;\n    cursor: pointer;\n    box-shadow: 0 0 10px #0ff, inset 0 0 10px #0ff;\n    transition: 0.3s;\n}\n.neon-btn:hover {\n    background: #0ff;\n    color: #000;\n    box-shadow: 0 0 20px #0ff, 0 0 40px #0ff;\n}\n</style>', 'ตัวอย่างโค้ดปุ่มกด HTML พร้อมตกแต่งด้วย CSS ให้มีเอฟเฟกต์เรืองแสงและเปลี่ยนสีเมื่อนำเมาส์ไปชี้', '2026-10-12 01:17:17', 'public', '', 4, 2, 0, 0),
(12, 'จัดกล่องให้อยู่ตรงกลางหน้าจอด้วย Flexbox', '<div class=\"container\">\n    <div class=\"card\">Card Content</div>\n</div>\n\n<style>\n.container {\n    display: flex;\n    justify-content: center;\n    align-items: center;\n    height: 100vh;\n    background: #f0f2f5;\n}\n.card {\n    background: white;\n    padding: 30px;\n    border-radius: 8px;\n    box-shadow: 0 4px 6px rgba(0,0,0,0.1);\n}\n</style>', 'การใช้งาน CSS Flexbox เพื่อจัดวางการ์ดเนื้อหาให้อยู่กึ่งกลางหน้าจอพอดีทั้งแนวตั้งและแนวนอน', '2026-10-12 01:17:46', 'public', '', 4, 2, 0, 0),
(13, 'สร้างเมนูบาร์อย่างง่าย (Simple Navbar)', '<nav class=\"navbar\">\n    <a href=\"#\" class=\"logo\">MyWebsite</a>\n    <ul class=\"nav-links\">\n        <li><a href=\"#\">Home</a></li>\n        <li><a href=\"#\">About</a></li>\n        <li><a href=\"#\">Contact</a></li>\n    </ul>\n</nav>\n\n<style>\n.navbar {\n    display: flex;\n    justify-content: space-between;\n    align-items: center;\n    background: #333;\n    padding: 1rem 2rem;\n    color: white;\n}\n.nav-links {\n    list-style: none;\n    display: flex;\n    gap: 20px;\n}\n.navbar a {\n    color: white;\n    text-decoration: none;\n}\n</style>', 'โครงสร้าง HTML และ CSS สำหรับสร้างแถบนำทางเว็บไซต์ (Navigation Bar) แบบพื้นฐาน\r\n', '2026-10-12 01:18:23', 'protected', '', 4, 2, 0, 0),
(14, 'ตารางข้อมูลสลับสีแถว (Zebra Striped Table)', '<table class=\"styled-table\">\n    <thead>\n        <tr><th>Name</th><th>Age</th><th>City</th></tr>\n    </thead>\n    <tbody>\n        <tr><td>John</td><td>28</td><td>New York</td></tr>\n        <tr><td>Anna</td><td>22</td><td>London</td></tr>\n    </tbody>\n</table>\n\n<style>\n.styled-table {\n    border-collapse: collapse;\n    width: 100%;\n    font-family: sans-serif;\n}\n.styled-table th, .styled-table td {\n    padding: 12px 15px;\n    text-align: left;\n    border-bottom: 1px solid #dddddd;\n}\n.styled-table tbody tr:nth-of-type(even) {\n    background-color: #f3f3f3;\n}\n</style>', 'ตารางข้อมูล HTML ที่ตกแต่งด้วย CSS ให้ดูสะอาดตา พร้อมสลับสีพื้นหลังแถวเพื่อให้อ่านง่ายขึ้น', '2026-10-12 01:18:50', 'public', '', 4, 2, 0, 0),
(15, 'การ์ดแสดงสินค้าพร้อมเอฟเฟกต์ซูมรูปภาพ', '<div class=\"product-card\">\n    <div class=\"img-container\">\n        <img src=\"https://via.placeholder.com/300\" alt=\"Product\">\n    </div>\n    <h3>Product Title</h3>\n    <p>$99.00</p>\n</div>\n\n<style>\n.product-card {\n    width: 250px;\n    border: 1px solid #e0e0e0;\n    border-radius: 8px;\n    overflow: hidden;\n    background: #fff;\n}\n.img-container {\n    overflow: hidden;\n}\n.product-card img {\n    width: 100%;\n    transition: transform 0.5s ease;\n}\n.product-card:hover img {\n    transform: scale(1.1);\n}\n.product-card h3, .product-card p {\n    padding: 0 15px;\n    margin: 10px 0;\n}\n</style>', 'ค้ดสร้างการ์ดสินค้าที่มีลูกเล่นขยายภาพ (Zoom Effect) เล็กน้อยเมื่อวางเมาส์ทับ\r\n', '2026-10-12 01:19:34', 'protected', '', 4, 2, 0, 0),
(16, 'การเข้ารหัสรหัสผ่าน (Password Hashing)', '$password = \"my_secure_password\";\n$hashed_password = password_hash($password, PASSWORD_DEFAULT);\n\nif (password_verify(\"my_secure_password\", $hashed_password)) {\n    echo \"Password matches!\";\n}', 'การเข้ารหัสรหัสผ่านด้วยฟังก์ชัน password_hash ที่ปลอดภัย และตรวจสอบความถูกต้องด้วย password_verify', '2026-10-12 01:20:02', 'protected', '', 2, 2, 0, 0),
(17, 'เชื่อมต่อฐานข้อมูล MySQL ด้วย PDO', '$host = \'127.0.0.1\';\n$db   = \'test_db\';\n$user = \'root\';\n$pass = \'\';\n\ntry {\n    $pdo = new PDO(\"mysql:host=$host;dbname=$db;charset=utf8mb4\", $user, $pass);\n    echo \"Connected successfully\";\n} catch (\\PDOException $e) {\n    echo \"Connection failed: \" . $e->getMessage();\n}', 'ตัวอย่างการเชื่อมต่อฐานข้อมูล MySQL ผ่าน PDO พร้อมจัดการข้อผิดพลาดด้วย try-catch เพื่อความปลอดภัย', '2026-10-12 01:20:30', 'public', '', 2, 2, 0, 0),
(18, 'สร้าง API ส่งออกข้อมูลรูปแบบ JSON', 'header(\'Content-Type: application/json\');\n\n$data = [\n    \'status\' => \'success\',\n    \'code\' => 200,\n    \'message\' => \'Data retrieved successfully\',\n    \'data\' => [\'id\' => 1, \'name\' => \'John Doe\']\n];\n\necho json_encode($data);', 'การตั้งค่า Header และแปลงอาเรย์ใน PHP ให้เป็นข้อมูล JSON เพื่อใช้สร้าง Web API\r\n', '2026-10-12 01:20:59', 'public', '', 2, 2, 0, 0),
(19, 'ฟังก์ชันทำความสะอาดข้อมูลป้องกัน XSS', 'function sanitize_input($data) {\n    $data = trim($data);\n    $data = stripslashes($data);\n    $data = htmlspecialchars($data);\n    return $data;\n}\n\n$user_input = sanitize_input(\"<script>alert(\'hack\');</script>\");\necho $user_input;', 'ฟังก์ชันพื้นฐานสำหรับกรองข้อมูลที่ผู้ใช้กรอกเข้ามา เพื่อป้องกันช่องโหว่ Cross-Site Scripting (XSS)', '2026-10-12 01:21:27', 'public', '', 2, 2, 0, 0),
(20, 'ตัวอย่างการอัปโหลดไฟล์รูปภาพ', 'if (isset($_FILES[\'image\'])) {\n    $file_name = $_FILES[\'image\'][\'name\'];\n    $file_tmp = $_FILES[\'image\'][\'tmp_name\'];\n    $target_dir = \"uploads/\" . basename($file_name);\n\n    if (move_uploaded_file($file_tmp, $target_dir)) {\n        echo \"File uploaded successfully.\";\n    } else {\n        echo \"Failed to upload file.\";\n    }\n}', 'โค้ดตัวอย่างสำหรับการจัดการไฟล์ที่ถูกอัปโหลดผ่านฟอร์ม HTML และย้ายไปยังโฟลเดอร์เป้าหมายบนเซิร์ฟเวอร์', '2026-10-12 01:21:53', 'protected', '', 2, 2, 0, 0),
(21, 'ฟังก์ชันดึงข้อมูลจาก API (Fetch API)', 'async function fetchData(url) {\n    try {\n        const response = await fetch(url);\n        const data = await response.json();\n        return data;\n    } catch (error) {\n        console.error(\'Error fetching data:\', error);\n    }\n}\n\nfetchData(\'https://api.example.com/data\');', 'ฟังก์ชันสำหรับดึงข้อมูลจากเว็บเซิร์ฟเวอร์แบบ Asynchronous โดยใช้ Fetch API และจัดการข้อผิดพลาดด้วย try-catch', '2026-10-12 01:22:22', 'public', '', 3, 2, 0, 0),
(22, 'ฟังก์ชัน Debounce สำหรับช่องค้นหา', 'function debounce(func, delay) {\n    let timeoutId;\n    return function (...args) {\n        clearTimeout(timeoutId);\n        timeoutId = setTimeout(() => {\n            func.apply(this, args);\n        }, delay);\n    };\n}\n\nconst handleSearch = debounce(() => console.log(\'Searching...\'), 300);', 'เทคนิคหน่วงเวลาการเรียกใช้ฟังก์ชัน เพื่อไม่ให้ประมวลผลบ่อยเกินไป เช่น เวลาที่ผู้ใช้พิมพ์ค้นหาข้อความรัวๆ', '2026-10-12 01:22:50', 'protected', '', 3, 2, 0, 0),
(23, 'ตัวช่วยบันทึกและอ่านข้อมูล LocalStorage', 'const storage = {\n    set: (key, value) => localStorage.setItem(key, JSON.stringify(value)),\n    get: (key) => JSON.parse(localStorage.getItem(key))\n};\n\nstorage.set(\'user\', { name: \'John\', age: 30 });\nconsole.log(storage.get(\'user\'));', 'ออบเจ็กต์ช่วยอำนวยความสะดวกในการแปลงออบเจ็กต์เป็น JSON อัตโนมัติเมื่อต้องการบันทึกหรือดึงข้อมูลจาก LocalStorage', '2026-10-12 01:23:13', 'protected', '', 3, 2, 0, 0),
(24, 'การใช้งาน Filter และ Map ร่วมกัน', 'const numbers = [1, 2, 3, 4, 5, 6];\nconst processedNumbers = numbers\n    .filter(n => n % 2 === 0)\n    .map(n => n * 10);\n\nconsole.log(processedNumbers); // [20, 40, 60]', 'ตัวอย่างการใช้เมธอดสมัยใหม่ของอาเรย์ เพื่อคัดกรองเฉพาะเลขคู่แล้วนำมาคูณสิบในขั้นตอนเดียว', '2026-10-12 01:23:38', 'public', '', 3, 2, 0, 0),
(25, 'ดักจับการคลิกปุ่มบนหน้าเว็บ', 'const button = document.querySelector(\'#submit-btn\');\n\nbutton.addEventListener(\'click\', (event) => {\n    event.preventDefault();\n    console.log(\'Button was clicked!\');\n});', 'ค้ดพื้นฐานสำหรับผูกเหตุการณ์คลิกเข้ากับปุ่มบน DOM และป้องกันพฤติกรรมเริ่มต้นของฟอร์ม', '2026-10-12 01:24:00', 'protected', '', 3, 2, 0, 0),
(26, 'ฟังก์ชัน get_change', 'def get_change(amount, coins=[10, 5, 2, 1]):\n    result = {}\n    for coin in coins:\n        count = amount // coin\n        if count > 0:\n            result[coin] = count\n            amount %= coin\n    return result\n\nprint(get_change(48))', 'get change', '2026-10-12 13:32:56', 'public', 'uploads/1791786776_Screenshot 2026-10-04 040014.png', 1, 3, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `snippet_appeals`
--

CREATE TABLE `snippet_appeals` (
  `appeal_id` int(11) NOT NULL,
  `snippet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('รอดำเนินการ','อนุมัติ','ถูกปฏิเสธ') NOT NULL DEFAULT 'รอดำเนินการ',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snippet_appeals`
--

INSERT INTO `snippet_appeals` (`appeal_id`, `snippet_id`, `user_id`, `reason`, `status`, `admin_notes`, `reviewed_by`, `created_at`, `reviewed_at`) VALUES
(1, 25, 2, 'ไม่เป็นการสแปม', 'อนุมัติ', '', 'kwang', '2026-10-12 13:47:50', '2026-10-12 13:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `snippet_like`
--

CREATE TABLE `snippet_like` (
  `snippet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `snippet_like`
--

INSERT INTO `snippet_like` (`snippet_id`, `user_id`) VALUES
(1, 2),
(1, 3);

-- --------------------------------------------------------

--
-- Table structure for table `snippet_suspensions`
--

CREATE TABLE `snippet_suspensions` (
  `suspension_id` int(11) NOT NULL,
  `snippet_id` int(11) NOT NULL,
  `admin_suspended` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `suspended_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admin_unsuspended` varchar(100) DEFAULT NULL,
  `unsuspended_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `snippet_suspensions`
--

INSERT INTO `snippet_suspensions` (`suspension_id`, `snippet_id`, `admin_suspended`, `reason`, `suspended_at`, `admin_unsuspended`, `unsuspended_at`) VALUES
(1, 25, 'kwang', 'สแปม', '2026-10-12 13:46:09', 'kwang', '2026-10-12 13:48:39');

-- --------------------------------------------------------

--
-- Table structure for table `snippet_tag`
--

CREATE TABLE `snippet_tag` (
  `snippet_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `snippet_tag`
--

INSERT INTO `snippet_tag` (`snippet_id`, `tag_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 4),
(2, 5),
(3, 1),
(3, 6),
(3, 7),
(4, 1),
(4, 8),
(4, 9),
(5, 1),
(5, 4),
(5, 10),
(6, 11),
(6, 12),
(6, 13),
(7, 11),
(7, 14),
(7, 15),
(8, 11),
(8, 16),
(8, 17),
(9, 11),
(9, 18),
(9, 19),
(10, 11),
(10, 20),
(10, 21),
(11, 22),
(11, 23),
(11, 24),
(12, 22),
(12, 23),
(12, 25),
(13, 22),
(13, 23),
(14, 22),
(14, 23),
(14, 26),
(15, 22),
(15, 23),
(15, 27),
(16, 28),
(16, 29),
(16, 30),
(17, 12),
(17, 28),
(17, 31),
(18, 28),
(18, 32),
(18, 33),
(19, 28),
(19, 29),
(19, 34),
(20, 28),
(20, 35),
(20, 36),
(21, 32),
(21, 37),
(21, 38),
(22, 37),
(22, 39),
(22, 40),
(23, 37),
(23, 41),
(23, 42),
(24, 37),
(24, 43),
(24, 44),
(25, 37),
(25, 45),
(25, 46),
(26, 1),
(26, 47),
(26, 48);

-- --------------------------------------------------------

--
-- Table structure for table `tag`
--

CREATE TABLE `tag` (
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `tag`
--

INSERT INTO `tag` (`tag_id`, `tag_name`) VALUES
(1, 'Python'),
(2, 'Grading'),
(3, 'conditions'),
(4, 'Algorithms'),
(5, 'math'),
(6, 'Mathematics'),
(7, 'functions'),
(8, 'Strings'),
(9, 'basics'),
(10, 'practical'),
(11, 'SQL'),
(12, 'Database'),
(13, 'queries'),
(14, 'Joins'),
(15, 'aggregation'),
(16, 'Subquery'),
(17, 'analytics'),
(18, 'GroupBy'),
(19, 'filtering'),
(20, 'DDL'),
(21, 'databasedesign'),
(22, 'HTML'),
(23, 'CSS'),
(24, 'ui design'),
(25, 'flexbox'),
(26, 'table'),
(27, 'Card'),
(28, 'PHP'),
(29, 'Security'),
(30, 'authentication'),
(31, 'pdo'),
(32, 'API'),
(33, 'json'),
(34, 'validation'),
(35, 'FileUpload'),
(36, 'form'),
(37, 'JavaScript'),
(38, 'async'),
(39, 'Performance'),
(40, 'utility'),
(41, 'Storage'),
(42, 'frontend'),
(43, 'Array'),
(44, 'functional'),
(45, 'DOM'),
(46, 'events'),
(47, 'getter'),
(48, 'changer');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `question` varchar(255) DEFAULT NULL,
  `answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `firstname`, `lastname`, `username`, `password`, `created_at`, `role`, `question`, `answer`) VALUES
(1, 'komkit', 'ruenrak', 'kwang', '$2y$10$lOFs4ChjFQEFL3.hIoIhVef9sC/NXfjPZm1QdMxA8Og7EDqZwwiFO', '2026-10-11 23:58:17', 'admin', 'ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?', '$2y$10$iQe7YLSqHW8915XVkqSGiuKNZ2hxNvYgWpmmEmJ1m.fXexYAw1C/a'),
(2, 'สมชาย', 'ใจดี', 'somchai', '$2y$10$V78FGBLftSSltZiaozQoZ.WXzLt1QcyUbjsfs5VOH1eQeHDmWMavS', '2026-10-12 01:05:13', 'user', 'ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?', '$2y$10$D6qMv.HWZuWLdrsgfULEKeM4DEaSH0x5hUai086yhFf.0cbnYD3Yy'),
(3, 'tom', 'jimmy', 'tom123', '$2y$10$ZYvJ0quHKEWSC.Lia5.uwOEMefW9sCDdFhAANpBx8t3UoijFOgKhi', '2026-10-12 13:28:35', 'user', 'ตัวละครในภาพยนตร์ที่คุณชื่นชอบมากที่สุดคือใคร?', '$2y$10$NR1lEy1ardiizXLSP9CFOOGvyX1XkqLJ7IPY2uh.ETEBxqyUePvRu');

-- --------------------------------------------------------

--
-- Table structure for table `user_bans`
--

CREATE TABLE `user_bans` (
  `ban_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_banned` varchar(100) NOT NULL,
  `reason` text NOT NULL,
  `banned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `unbanned_at` datetime DEFAULT NULL,
  `admin_unbanned` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `user_bans`
--

INSERT INTO `user_bans` (`ban_id`, `user_id`, `admin_banned`, `reason`, `banned_at`, `unbanned_at`, `admin_unbanned`) VALUES
(1, 3, 'kwang', 'เนื้อหาไม่เหมาะสม / ความรุนแรง / การคุกคาม', '2026-10-12 13:49:30', '2026-10-12 13:50:57', 'kwang'),
(2, 3, 'kwang', 'เนื้อหาไม่เหมาะสม / ความรุนแรง / การคุกคาม', '2026-10-12 13:54:45', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`bookmark_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `snippet_id` (`snippet_id`);

--
-- Indexes for table `language`
--
ALTER TABLE `language`
  ADD PRIMARY KEY (`language_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `fk_reports_user` (`user_id`),
  ADD KEY `idx_reports_snippet_id` (`snippet_id`);

--
-- Indexes for table `snippet`
--
ALTER TABLE `snippet`
  ADD PRIMARY KEY (`snippet_id`),
  ADD KEY `fk_snippet_language` (`language_id`),
  ADD KEY `fk_snippet_user` (`user_id`);

--
-- Indexes for table `snippet_appeals`
--
ALTER TABLE `snippet_appeals`
  ADD PRIMARY KEY (`appeal_id`),
  ADD KEY `fk_snippet_appeals_snippet` (`snippet_id`),
  ADD KEY `fk_snippet_appeals_user` (`user_id`);

--
-- Indexes for table `snippet_like`
--
ALTER TABLE `snippet_like`
  ADD PRIMARY KEY (`snippet_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `snippet_suspensions`
--
ALTER TABLE `snippet_suspensions`
  ADD PRIMARY KEY (`suspension_id`),
  ADD KEY `fk_snippet_suspensions_snippet` (`snippet_id`);

--
-- Indexes for table `snippet_tag`
--
ALTER TABLE `snippet_tag`
  ADD PRIMARY KEY (`snippet_id`,`tag_id`),
  ADD KEY `fk_snippettag_tag` (`tag_id`);

--
-- Indexes for table `tag`
--
ALTER TABLE `tag`
  ADD PRIMARY KEY (`tag_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD PRIMARY KEY (`ban_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `banned_by` (`admin_banned`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `bookmark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `snippet`
--
ALTER TABLE `snippet`
  MODIFY `snippet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `snippet_appeals`
--
ALTER TABLE `snippet_appeals`
  MODIFY `appeal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `snippet_suspensions`
--
ALTER TABLE `snippet_suspensions`
  MODIFY `suspension_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_bans`
--
ALTER TABLE `user_bans`
  MODIFY `ban_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`),
  ADD CONSTRAINT `bookmarks_ibfk_2` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_snippet` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reports_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `snippet`
--
ALTER TABLE `snippet`
  ADD CONSTRAINT `fk_snippet_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`),
  ADD CONSTRAINT `fk_snippet_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `snippet_appeals`
--
ALTER TABLE `snippet_appeals`
  ADD CONSTRAINT `fk_snippet_appeals_snippet` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_snippet_appeals_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `snippet_like`
--
ALTER TABLE `snippet_like`
  ADD CONSTRAINT `snippet_like_ibfk_1` FOREIGN KEY (`Snippet_id`) REFERENCES `snippet` (`snippet_id`),
  ADD CONSTRAINT `snippet_like_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `snippet_suspensions`
--
ALTER TABLE `snippet_suspensions`
  ADD CONSTRAINT `fk_snippet_suspensions_snippet` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`) ON DELETE CASCADE;

--
-- Constraints for table `snippet_tag`
--
ALTER TABLE `snippet_tag`
  ADD CONSTRAINT `fk_snippettag_snippet` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_snippettag_tag` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user_bans`
--
ALTER TABLE `user_bans`
  ADD CONSTRAINT `user_bans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
