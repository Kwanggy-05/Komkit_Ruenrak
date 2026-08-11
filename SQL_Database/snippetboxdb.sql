-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 11, 2026 at 07:50 AM
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
(4, 26, 74, '2026-07-06 22:46:36'),
(5, 26, 73, '2026-07-06 22:46:37'),
(6, 26, 72, '2026-07-06 22:46:38'),
(10, 28, 80, '2026-08-06 22:50:24'),
(11, 28, 81, '2026-08-06 22:50:25'),
(12, 26, 85, '2026-08-07 13:21:38');

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
(2, 'JavaScript'),
(3, 'PHP'),
(4, 'HTML'),
(5, 'SQL'),
(6, 'CSS'),
(7, 'Java'),
(8, 'C++');

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
  `deleted` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `snippet`
--

INSERT INTO `snippet` (`snippet_id`, `title`, `source_code`, `description`, `created_at`, `visibility`, `image_path`, `language_id`, `user_id`, `deleted`) VALUES
(61, 'รายการอาหาร', '<h1>รายการอาหาร</h1>\n    <ol type=\"I\">\n        <li>ข้าวผัด\n            <ul type=\"disc\">\n                <li>ข้าวผัดไก่</li>\n                <li>ข้าวผัดหมู</li>\n                <li>ข้าวผัดกุ้ง</li>\n            </ul>\n        </li>\n        <li>ต้มยำ\n            <ul type=\"disc\">\n                <li>ต้มยำหมู</li>\n                <li>ต้มยำไก่</li>\n                <li>ต้มยำกุ้ง</li>\n            </ul>\n        </li>\n        <li>ผัดวุ้นเส้น</li>\n    </ol>', 'การทำรายการอาหารด้วย HTML', '2026-07-06 22:28:10', 'public', '', 4, 23, 0),
(62, 'เช็กว่าไฟล์มีอยู่จริงไหมก่อนเปิดใช้งาน', 'import os\r\n\r\nfile_path = \"data/config.json\"\r\nif os.path.exists(file_path):\r\n    print(\"พร้อมใช้งานไฟล์!\")\r\nelse:\r\n    print(\"หาไฟล์ไม่เจอ กรุณาตรวจสอบอีกครั้ง\")', 'ใช้ไลบรารี os เพื่อตรวจสอบว่าไฟล์ใน Path ที่ระบุมีอยู่จริงหรือไม่ ช่วยป้องกันโปรแกรมพัง (Crash) จาก Error ประเภท FileNotFoundError', '2026-07-06 22:31:20', 'public', '', 1, 26, 0),
(63, 'ปุ่มเอฟเฟกต์ Fade ตอนเอาเมาส์ไปชี้ (Hover)', '.btn-submit {\r\n    background-color: #3498db;\r\n    transition: background-color 0.3s ease;\r\n}\r\n.btn-submit:hover {\r\n    background-color: #2980b9;\r\n}', 'สไตล์สำหรับปุ่มกดที่เปลี่ยนสีพื้นหลังให้อ่อนลงหรือเข้มขึ้นอย่างนุ่มนวลภายในเวลา 0.3 วินาที เมื่อผู้ใช้งานนำเมาส์มาวางเหนือปุ่ม ช่วยให้เว็บดูมีมิติและน่าใช้งานมากขึ้น', '2026-07-06 22:32:40', 'protected', '', 6, 23, 0),
(64, 'ลบค่าซ้ำออกจาก Array ในบรรทัดเดียว', 'const removeDuplicates = (arr) => [...new Set(arr)];\r\n\r\n// วิธีใช้:\r\nconst numbers = [1, 2, 2, 3, 4, 4, 5];\r\nconsole.log(removeDuplicates(numbers)); // ผลลัพธ์: [1, 2, 3, 4, 5]', 'ใช้ Set ซึ่งเป็น Object ที่เก็บเฉพาะค่าที่ไม่ซ้ำกัน จากนั้นใช้ Spread Operator (...) เพื่อกระจายค่ากลับมาเป็น Array ตัวใหม่แบบคลีนๆ', '2026-07-06 22:34:16', 'public', '', 1, 23, 0),
(67, 'โครงสร้างตารางแยกส่วนหัว ตัวเนื้อหา และส่วนท้าย', '<table>\r\n    <thead>\r\n        <tr>\r\n            <th>สินค้า</th>\r\n            <th>ราคา</th>\r\n        </tr>\r\n    </thead>\r\n    <tbody>\r\n        <tr>\r\n            <td>SnippetBox Pro</td>\r\n            <td>$9.99</td>\r\n        </tr>\r\n    </tbody>\r\n    <tfoot>\r\n        <tr>\r\n            <td>รวมทั้งสิ้น</td>\r\n            <td>$9.99</td>\r\n        </tr>\r\n    </tfoot>\r\n</table>', 'การเขียนตารางที่ถูกต้องตามหลัก Semantic Web โดยแยกโครงสร้างชัดเจนเป็น <thead> (หัวตาราง), <tbody> (เนื้อหา) และ <tfoot> (สรุปท้ายตาราง) ช่วยให้สไตลิ่งด้วย CSS ง่ายขึ้น และดีต่อ SEO', '2026-07-06 22:38:45', 'protected', 'uploads/1783352325_Screenshot 2026-07-06 223836.png', 4, 26, 0),
(68, 'โค้ดฝังวิดีโอจาก YouTube', '<iframe \r\n    width=\"560\" \r\n    height=\"315\" \r\n    src=\"https://www.youtube.com/embed/VIDEO_ID\" \r\n    title=\"YouTube video player\" \r\n    frameborder=\"0\" \r\n    allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" \r\n    allowfullscreen>\r\n</iframe>', 'โครงสร้างแท็ก <iframe> สำหรับนำวิดีโอจาก YouTube มาแสดงบนหน้าเว็บของเรา เพียงแค่เปลี่ยนตรงคำว่า VIDEO_ID ให้เป็นรหัสวิดีโอตัวที่ต้องการใช้งาน ก็พร้อมแสดงผลทันที ตัวโค้ดเปิดสิทธิ์ให้กดขยายเต็มจอ (allowfullscreen) ได้ด้วย', '2026-07-06 22:39:58', 'public', '', 4, 26, 0),
(69, 'ดึงข้อมูลแบบแบ่งหน้าด้วย LIMIT และ OFFSET', 'SELECT id, name, created_at \r\nFROM users \r\nORDER BY created_at DESC \r\nLIMIT 10 OFFSET 20;', 'ใช้สำหรับทำระบบแบ่งหน้าบนหน้าเว็บ (เช่น หน้าที่ 3) โดย LIMIT 10 คือดึงมาแค่ 10 แถว และ OFFSET 20 คือให้ข้าม 20 แถวแรกไปก่อน', '2026-07-06 22:41:41', 'public', '', 5, 26, 0),
(70, 'ดึงข้อมูลคำสั่งซื้อพร้อมชื่อลูกค้า', 'SELECT orders.id, users.name, orders.total_amount, orders.order_date\r\nFROM orders\r\nINNER JOIN users ON orders.user_id = users.id\r\nWHERE orders.status = \'completed\';', 'ใช้ INNER JOIN เพื่อผูกตารางคำสั่งซื้อ (orders) เข้ากับตารางผู้ใช้งาน (users) โดยจับคู่จาก ID ที่ตรงกัน เพื่อดึงชื่อลูกค้ามาแสดงคู่กับยอดเงินแทนที่จะเห็นแค่รหัสตัวเลข', '2026-07-06 22:42:10', 'private', '', 5, 26, 0),
(71, 'ค้นหาข้อมูลด้วย Keyword (ค้นหาคำบางส่วน)', 'SELECT id, title, description \r\nFROM snippets \r\nWHERE title LIKE \'%javascript%\' OR description LIKE \'%javascript%\';', 'ใช้เครื่องหมาย % ร่วมกับโอเปอเรเตอร์ LIKE เพื่อค้นหาข้อความที่มีคำว่า \"javascript\" อยู่ตรงส่วนไหนก็ได้ (ข้างหน้า ข้างหลัง หรือตรงกลาง) เหมาะสำหรับทำช่องค้นหา (Search Box) บนเว็บไซต์', '2026-07-06 22:42:40', 'public', '', 5, 26, 0),
(72, 'นับจำนวนออเดอร์และยอดขายรวม แยกตามหมวดหมู่สินค้า', 'SELECT category, \r\n       COUNT(id) AS total_orders, \r\n       SUM(price) AS total_revenue\r\nFROM products\r\nGROUP BY category\r\nHAVING SUM(price) > 5000;', 'ใช้ GROUP BY เพื่อจับกลุ่มสินค้าตามหมวดหมู่ จากนั้นใช้ COUNT เพื่อนับชิ้น และ SUM เพื่อรวมยอดขาย ส่วน HAVING จะทำหน้าที่เหมือน WHERE แต่ใช้กรองเฉพาะกลุ่มที่คำนวณผลรวมเสร็จแล้ว (ในที่นี้คือเอาเฉพาะหมวดหมู่ที่มียอดขายเกิน 5,000 บาท)', '2026-07-06 22:43:15', 'public', '', 5, 26, 0),
(73, 'ตรวจสอบและคลีนค่า Email จากฟอร์ม POST', 'if ($_SERVER[\"REQUEST_METHOD\"] === \"POST\") {\r\n    // ลบอักขระแปลกปลอมที่แปลงปลอมมากับ Email\r\n    $email = filter_input(INPUT_POST, \'email\', FILTER_SANITIZE_EMAIL);\r\n\r\n    // ตรวจสอบว่าฟอร์แมตถูกต้องไหม\r\n    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {\r\n        echo \"Email ถูกต้อง: \" . htmlspecialchars($email);\r\n    } else {\r\n        echo \"รูปแบบ Email ไม่ถูกต้อง!\";\r\n    }\r\n}', 'ใช้ filter_input และ filter_var ในการทำความสะอาดและตรวจสอบความถูกต้องของข้อความที่ส่งมาจากฟอร์ม ช่วยป้องกันช่องโหว่ความปลอดภัยเบื้องต้นได้ดีมาก', '2026-07-06 22:44:58', 'public', '', 3, 26, 0),
(74, 'ส่ง Request ไปดึงข้อมูลจาก API ต่างแดนด้วย cURL', '$ch = curl_init();\r\ncurl_setopt($ch, CURLOPT_URL, \"https://api.example.com/data\");\r\ncurl_setopt($ch, CURLOPT_RETURNTRANSFER, true);\r\ncurl_setopt($ch, CURLOPT_TIMEOUT, 10);\r\n\r\n$response = curl_exec($ch);\r\n\r\nif (curl_errno($ch)) {\r\n    echo \'Error:\' . curl_error($ch);\r\n} else {\r\n    $data = json_decode($response, true); // แปลง JSON เป็น Array ของ PHP\r\n    print_r($data);\r\n}\r\ncurl_close($ch);', 'บล็อกคำสั่งมาตรฐานสำหรับการใช้หน่วย cURL เพื่อเชื่อมต่อไปยัง API อื่นๆ โดยสั่งให้ส่งค่ากลับมาเป็น String (CURLOPT_RETURNTRANSFER) และนำมาแปลงเป็น Array ด้วย json_decode เพื่อเอาไปใช้งานต่อ', '2026-07-06 22:45:36', 'protected', '', 3, 26, 0),
(75, 'เปลี่ยน Array ที่ซ้อนกันหลายๆ ชั้นให้แบนราบในบรรทัดเดียว', 'const deeplyNested = [1, [2, [3, [4]], 5]];\r\n\r\n// ใช้ flat(Infinity) เพื่อยุบทุกชั้นไม่ว่าจะลึกแค่ไหน\r\nconst flatArray = deeplyNested.flat(Infinity);\r\n\r\nconsole.log(flatArray); // ผลลัพธ์: [1, 2, 3, 4, 5]', 'เมธอด .flat() เป็นฟังก์ชันสำเร็จรูปของ JavaScript โดยการใส่พารามิเตอร์ Infinity จะช่วยสั่งให้ทลายโครงสร้างอาร์เรย์ที่ซ้อนกันอยู่ทุกชั้นออกมาเป็นอาร์เรย์มิติเดียวแบบง่ายๆ', '2026-07-07 00:59:43', 'public', 'uploads/1785323582_Screenshot 2026-07-26 003538.png', 5, 26, 0),
(80, 'if else', 'let score = 20;\r\nlet pass;\r\npass = score>50 ? \"ผ่านเกณฑ์\" : \"ไม่ผ่านเกณฑ์\";\r\nconsole.log(pass);', 'if else แบบย่อ', '2026-07-30 15:53:48', 'private', '', 2, 28, 0),
(81, 'if ซ้อน if', 'let age= 99;\r\nif(age<=15){\r\n    if(age == 15){\r\n        console.log(\"ม.3\");\r\n    }\r\n    else if(age == 14){\r\n        console.log(\"ม.2\");\r\n    }\r\n    else if(age == 13){\r\n        console.log(\"ม.1\");\r\n    }else{\r\n        console.log(\"ประถม\");\r\n    }\r\n}else{\r\n    console.log(\"ม.ปลาย / มหาลัย\");\r\n}', 'การทำเงื่อนไขซ้อนเงื่อนไข', '2026-07-30 16:07:24', 'protected', '', 2, 28, 0),
(82, 'swith case', 'let status = 8;\r\nlet a;\r\nswitch(status){\r\n    case 0 : a = \"ปิดไฟ\";\r\n    break;\r\n    case 1 : a = \"เปิดไฟ\";\r\n    break;\r\n    default : a = \"ไม่พบข้อมูล\"\r\n}\r\nconsole.log(a);', 'การทำ switch case กำหนดเงือ่นไขและแสดงค่าออกมา', '2026-07-30 16:25:43', 'public', 'uploads/1785403543_Screenshot 2026-07-30 162528.png', 2, 28, 0),
(83, 'ตรวจสอบเลขคู่ เลขคี่', 'let number = 45;\r\nif(number%2 == 0){\r\n    console.log(\"เลขคู่\");\r\n}else{\r\n    console.log(\"เลขคี่\");\r\n}', 'ใช้ if else ในการตรวจเลขคู่ เลขคี่', '2026-07-30 16:36:15', 'public', '', 2, 28, 1),
(85, 'useLocalStorage Custom Hook', 'import { useState, useEffect } from \'react\';\r\n\r\nfunction useLocalStorage(key, initialValue) {\r\n  const [storedValue, setStoredValue] = useState(() => {\r\n    try {\r\n      const item = window.localStorage.getItem(key);\r\n      return item ? JSON.parse(item) : initialValue;\r\n    } catch (error) {\r\n      console.error(error);\r\n      return initialValue;\r\n    }\r\n  });\r\n\r\n  useEffect(() => {\r\n    try {\r\n      window.localStorage.setItem(key, JSON.stringify(storedValue));\r\n    } catch (error) {\r\n      console.error(error);\r\n    }\r\n  }, [key, storedValue]);\r\n\r\n  return [storedValue, setStoredValue];\r\n}', 'Custom Hook ใน React สำหรับซิงค์ State เข้ากับ LocalStorage ใน Browser แบบอัตโนมัติ', '2026-08-07 13:18:03', 'protected', 'uploads/1786083483_Frame 49.png', 2, 30, 0);

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
(62, 26),
(67, 26),
(68, 26),
(69, 26),
(70, 26),
(71, 26),
(72, 26),
(73, 26),
(73, 28),
(74, 26),
(75, 26),
(80, 28),
(81, 28),
(85, 26),
(85, 30);

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
(61, 10),
(62, 1),
(63, 11),
(69, 6),
(73, 7),
(75, 8),
(75, 13),
(80, 8),
(81, 8),
(83, 15),
(85, 5),
(85, 8),
(85, 10),
(85, 12),
(85, 15),
(85, 16);

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
(1, 'python'),
(2, 'otp'),
(3, 'random'),
(4, 'utility'),
(5, 'frontend'),
(6, 'SQL'),
(7, 'PHP'),
(8, 'JavaScript'),
(10, 'HTML'),
(11, 'CSS'),
(12, 'JavaScript Array'),
(13, 'Array'),
(15, 'if else'),
(16, 'Java');

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
(23, 'คมกฤษ', 'รื่นรักษ์', 'kwang007', '0583573f1efcb2395b3058ca023b84e5', '2026-07-04 20:52:41', 'user', '', ''),
(26, 'Kevin', 'Vance', 'kevin', '$2y$10$AbGJu7CkWRrQ.1NcjQebteEL29fyU5J5glFKXOgDS5U41AB35N.j2', '2026-07-04 21:59:06', 'user', NULL, NULL),
(28, 'James', 'Taylor', 'james', '$2y$10$hJRDPUN077Q8laKL1yeBLOoayTVcqDcw2B87dTYseP7HjALWUh4Jq', '2026-07-29 18:59:03', 'user', 'ชื่อตัวละครหรือการ์ตูนตัวโปรดของคุณคืออะไร?', 'โดเรม่อน'),
(29, 'Mason', 'Reed', 'mason', '$2y$10$qif9.r3zI/gDyL/b8o6M8..i7G/QrO8RxDBtu2Oho3sUyDPi72NX2', '2026-08-04 19:18:20', 'admin', 'ชื่อตัวละครหรือการ์ตูนตัวโปรดของคุณคืออะไร?', 'อันปังแมน'),
(30, 'ไก่', 'ไข่', 'kai', '$2y$10$ePda8k5wrQCRxtXApynGSOO4LYhPy0OzJiei63lmKJ2bnC5iZuLuK', '2026-08-07 13:11:29', 'user', 'ชื่อตัวละครหรือการ์ตูนตัวโปรดของคุณคืออะไร?', 'โดเรม่อน');

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
-- Indexes for table `snippet`
--
ALTER TABLE `snippet`
  ADD PRIMARY KEY (`snippet_id`),
  ADD KEY `fk_snippet_language` (`language_id`),
  ADD KEY `fk_snippet_user` (`user_id`);

--
-- Indexes for table `snippet_like`
--
ALTER TABLE `snippet_like`
  ADD PRIMARY KEY (`snippet_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `bookmark_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `language`
--
ALTER TABLE `language`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `snippet`
--
ALTER TABLE `snippet`
  MODIFY `snippet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `tag`
--
ALTER TABLE `tag`
  MODIFY `tag_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
-- Constraints for table `snippet`
--
ALTER TABLE `snippet`
  ADD CONSTRAINT `fk_snippet_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`),
  ADD CONSTRAINT `fk_snippet_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `snippet_like`
--
ALTER TABLE `snippet_like`
  ADD CONSTRAINT `snippet_like_ibfk_1` FOREIGN KEY (`Snippet_id`) REFERENCES `snippet` (`snippet_id`),
  ADD CONSTRAINT `snippet_like_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `snippet_tag`
--
ALTER TABLE `snippet_tag`
  ADD CONSTRAINT `fk_snippettag_snippet` FOREIGN KEY (`snippet_id`) REFERENCES `snippet` (`snippet_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_snippettag_tag` FOREIGN KEY (`tag_id`) REFERENCES `tag` (`tag_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
