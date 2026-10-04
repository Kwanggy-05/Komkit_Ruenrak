<?php
/**
 * ==============================================================================
 * ไฟล์: remove.php
 * หน้าที่: จัดการถังขยะ (Recycle Bin / Trash) ของสมาชิก
 * รายการส่วนประกอบ:
 *   1. ตรวจสอบสิทธิ์ผู้ใช้งาน (Auth Middleware)
 *   2. การกู้คืน Snippet (Restore Action) ผ่าน $control->restoreSnippet()
 *   3. การลบ Snippet ถาวร (Hard Delete Action) ผ่าน $control->deleteSnippet()
 *   4. การตั้งค่าแบ่งหน้า (Pagination) และระบบค้นหา Snippet ที่ถูกลบ
 *   5. ดึงข้อมูล Snippet ในถังขยะจากฐานข้อมูล
 *   6. การแสดงผล UI (Navbar, Search bar, Grid รายการโค้ด, Pagination Links)
 *   7. JavaScript: changePerPage() สำหรับปรับจำนวนรายการที่แสดงต่อหน้า
 * ==============================================================================
 */

// --- ส่วนที่ 1: โหลดไฟล์ระบบและตรวจสอบการเข้าสู่ระบบของผู้ใช้ ---
require_once "layout/session.php";
require_once "db/connect.php";
require_once "layout/check_user.php";

$userId = getCurrentUserId();

// --- ส่วนที่ 2: ดึงรายการภาษาพร้อมจำนวน Snippet ---
$languageResult = $control->getAllLanguagesWithSnippetCounts();
$languages = [];
if($languageResult){
    $languages = $languageResult->fetchAll(PDO::FETCH_ASSOC);
}

// --- ส่วนที่ 3: ประมวลผลคำขอกู้คืน Snippet กลับจากถังขยะ (Restore Action) ---
if(isset($_GET['restore']) && is_numeric($_GET['restore'])){
    $control->restoreSnippet((int)$_GET['restore'], $userId);
    header('Location:remove.php');
    exit;
}

// --- ส่วนที่ 4: ประมวลผลคำขอลบ Snippet อย่างถาวร (Hard Delete Action) ---
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->deleteSnippet((int)$_GET['delete'], $userId);
    header('Location:remove.php');
    exit;
}

// --- ส่วนที่ 5: จัดการคำค้นหา, ตัวกรองภาษา และการตั้งค่าแบ่งหน้า (Pagination) ---
$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = trim($_GET['lang'] ?? '');

// กำหนดจำนวนรายการต่อหน้าที่อนุญาต [10, 15, 20, 25]
$allowedLimits = [10, 15, 20, 25];
if(isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits)){
    $itemsPerPage = (int)$_GET['limit'];
    $_SESSION['items_per_page'] = $itemsPerPage;
}elseif(isset($_SESSION['items_per_page']) && in_array((int)$_SESSION['items_per_page'], $allowedLimits)){
    $itemsPerPage = (int)$_SESSION['items_per_page'];
}else{
    $itemsPerPage = 10;
}
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// --- ส่วนที่ 6: ดึงข้อมูล Snippet ที่อยู่ในถังขยะของผู้ใช้จากฐานข้อมูล ---
$totalSnippets = $control->getTotalDeletedUserSnippetCount($userId, $searchQuery, $selectedLanguage);
$result = $control->getDeletedUserSnippetsPaginated($userId, $currentPage, $itemsPerPage, $searchQuery, $selectedLanguage);

$snippets = [];
if($result){
    $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
}

// คำนวณจำนวนหน้าทั้งหมด
$totalPages = ceil($totalSnippets / $itemsPerPage);
if($totalPages === 0) $totalPages = 1;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ถังขยะ - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/removecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="snippet_page.php" class="btn-outline">กลับหน้า Snippet</a>
        </div>
        <div class="nav-right">
            <div class="user-profile">
                <div class="avatar">
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="18" cy="18" r="18" fill="black"/>
                        <path d="M18 17C20.2091 17 22 15.2091 22 13C22 10.7909 20.2091 9 18 9C15.7909 9 14 10.7909 14 13C14 15.2091 15.7909 17 18 17Z" fill="white"/>
                        <path d="M25 25C25 21.6863 21.866 19 18 19C14.134 19 11 21.6863 11 25" stroke="white" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            </div>
            <a href="login.php" class="btn-outline" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่?');">ออกจากระบบ</a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="sidebar">
            <a href="snippet_page.php" class="btn-create">Snippet ของฉัน</a>
        </div>
        
        <div class="content">
            <div class="toolbar">
                <form method="GET" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <div class="search-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="ค้นหา Snippet...">
                    </div>
                    
                    <select name="lang" class="select-per-page" onchange="this.form.submit()">
                        <option value="">ทุกภาษา</option>
                        <?php foreach($languages as $lang): ?>
                            <option value="<?php echo htmlspecialchars($lang['language_id']); ?>" 
                                <?php echo ($selectedLanguage == $lang['language_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lang['language_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if($itemsPerPage !== 10 || isset($_GET['limit'])): ?><input type="hidden" name="limit" value="<?php echo $itemsPerPage; ?>"><?php endif; ?>
                </form>
                <div class="toolbar-actions">
                    <div class="per-page-selector">
                        <label for="limit-select">แสดง:</label>
                        <select id="limit-select" class="select-per-page" onchange="changePerPage(this.value)">
                            <?php foreach($allowedLimits as $opt): ?>
                                <option value="<?php echo $opt; ?>" <?php echo $itemsPerPage === $opt ? 'selected' : ''; ?>><?php echo $opt; ?> / หน้า</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="total-snippets">
                Snippet ในถังขยะ: <?php echo $totalSnippets; ?><?php if($searchQuery !== ''): ?> (ผลการค้นหาสำหรับ "<?php echo htmlspecialchars($searchQuery); ?>")<?php endif; ?>
            </div>
            
            <div class="snippet-grid">
                <?php if($totalSnippets === 0): ?>
                    <div class="empty-state">
                        ไม่มี Snippet ในถังขยะ
                    </div>
                <?php else: ?>
                    <?php foreach($snippets as $row): ?>
                        <?php
                            $languageName = trim((string)($row['language_name'] ?? 'Language'));
                            $languageKey = strtolower($languageName);
                            $languageClass = 'language-tag';
                            if($languageKey === 'html'){
                                $languageClass .= ' language-html';
                            }elseif($languageKey === 'python'){
                                $languageClass .= ' language-python';
                            }elseif($languageKey === 'javascript' || $languageKey === 'js'){
                                $languageClass .= ' language-javascript';
                            }elseif($languageKey === 'php'){
                                $languageClass .= ' language-php';
                            }elseif($languageKey === 'sql'){
                                $languageClass .= ' language-sql';
                            }elseif($languageKey === 'css'){
                                $languageClass .= ' language-css';
                            }
                        ?>
                        <div class="snippet-card">
                            <div class="card-left">
                                <?php
                                    $title = $row['title'] ?: 'ไม่มีชื่อเรื่อง';
                                    if(mb_strlen($title, 'UTF-8') > 30){
                                        $title = mb_substr($title, 0, 30, 'UTF-8') . '...';
                                    }
                                ?>
                                <div class="card-title"><?php echo htmlspecialchars($title); ?></div>
                                <?php if(!empty($row['tags'])): ?>
                                    <div class="tag-badge">
                                        <?php foreach(explode(', ', $row['tags']) as $tagName): ?>
                                            <span><?php echo htmlspecialchars($tagName); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if(!empty($row['created_at'])): ?>
                                    <div class="snippet-date">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span><?php echo date('d/m/Y H:i:s', strtotime($row['created_at'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                <button class="btn-view-code">
                                    <a href="viewcode.php?view=<?php echo urlencode($row['snippet_id']); ?>&from_trash=1" >ดูโค้ด</a>
                                </button>
                            </div>
                            
                            <div class="card-middle">
                                <div class="<?php echo htmlspecialchars($languageClass); ?>"><?php echo htmlspecialchars($languageName ?: 'Language'); ?></div>
                            </div>
                            
                            <div class="card-right">
                                <?php if((int)($row['user_id'] ?? 0) === (int)$userId): ?>
                                <a href="remove.php?restore=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="กู้คืน Snippet" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการกู้คืน Snippet นี้?');">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#28a745" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path></svg>
                                </a>
                                <a href="remove.php?delete=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="ลบถาวร" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบ Snippet นี้อย่างถาวร?');">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="red" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1 && count($snippets) > 0): ?>
            <div class="pagination">
                <?php
                $startPage = 1;
                $endPage = min(10, $totalPages);
                
                for($i = $startPage; $i <= $endPage; $i++){
                    $pageUrl = 'remove.php?page=' . $i;
                    if($searchQuery !== '') $pageUrl .= '&q=' . urlencode($searchQuery);
                    if($selectedLanguage !== '') $pageUrl .= '&lang=' . urlencode($selectedLanguage);
                    if($itemsPerPage !== 10 || isset($_GET['limit'])) $pageUrl .= '&limit=' . $itemsPerPage;
                    
                    if($i == $currentPage){
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $pageUrl . '">' . $i . '</a>';
                    }
                }
                
                if($totalPages > 10 && $currentPage < $totalPages){
                    $nextPageUrl = 'remove.php?page=' . ($currentPage + 1);
                    if($searchQuery !== '') $nextPageUrl .= '&q=' . urlencode($searchQuery);
                    if($selectedLanguage !== '') $nextPageUrl .= '&lang=' . urlencode($selectedLanguage);
                    if($itemsPerPage !== 10 || isset($_GET['limit'])) $nextPageUrl .= '&limit=' . $itemsPerPage;
                    echo '<a href="' . $nextPageUrl . '" class="next-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </a>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <script>
        /**
         * ฟังก์ชัน: changePerPage
         * ส่วนที่: JavaScript สำหรับควบคุมการแบ่งหน้า (Client-side Pagination)
         * หน้าที่: รีโหลดหน้าเว็บพร้อมเปลี่ยนค่าพารามิเตอร์ limit (จำนวนรายการต่อหน้า) และรีเซ็ตกลับหน้า 1
         * พารามิเตอร์: val (จำนวนที่เลือก เช่น 10, 15, 20, 25)
         */
        function changePerPage(val) {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', val);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>
