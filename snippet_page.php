<?php
/**
 * ==============================================================================
 * ไฟล์: snippet_page.php
 * หน้าที่: หน้าแสดงรายการ Snippet ของฉัน (My Snippets Dashboard)
 * ส่วนประกอบในไฟล์:
 *   1. ตรวจสอบการเข้าสู่ระบบของผู้ใช้งาน (Auth Check)
 *   2. ดึงรายการภาษาพร้อมจำนวน Snippet เพื่อแสดงในตัวกรอง ($control->getAllLanguagesWithSnippetCounts())
 *   3. จัดการการลบแบบย้ายไปถังขยะ (Soft Delete via $_GET['delete'])
 *   4. การแบ่งหน้า (Pagination) และการกรองตามเงื่อนไข (บุ๊กมาร์ก, คำค้นหา, ภาษา, เฉพาะของฉัน)
 *   5. ดึงข้อมูลรายการ Snippet และข้อมูลสถิติ (ยอดไลก์, สถานะบุ๊กมาร์ก)
 *   6. ส่วนแสดงผล UI หน้าเว็บ (Navbar, Sidebar, Grid แสดงโค้ด, Pagination Links)
 *   7. สคริปต์ JavaScript สำหรับเปลี่ยนจำนวนต่อหน้า, Toast แจ้งเตือน, และการกดไลก์/บุ๊กมาร์กแบบ AJAX
 * ==============================================================================
 */

// --- ส่วนที่ 1: ตรวจสอบสิทธิ์การเข้าใช้งาน ---
require_once "layout/session.php";
require_once "db/connect.php";
require_once "layout/check_user.php";

$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = $_GET['lang'] ?? '';
$showBookmarks = isset($_GET['bookmarks']) && $_GET['bookmarks'] === '1';
$onlyOwn = empty($_GET) || (isset($_GET['only_own']) && $_GET['only_own'] === '1');
$userId = getCurrentUserId();

// --- ส่วนที่ 2: ดึงรายการภาษาพร้อมจำนวน Snippet ในแต่ละภาษา ---
$languageResult = $control->getAllLanguagesWithSnippetCounts();
$languages = [];
if($languageResult){
    $languages = $languageResult->fetchAll(PDO::FETCH_ASSOC);
}

// --- ส่วนที่ 3: จัดการการลบ Snippet ชั่วคราว (ย้ายไปถังขยะ) ---
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->softDeleteSnippet((int)$_GET['delete'], $userId);
    header('Location:remove.php');
    exit;
}

// --- ส่วนที่ 4: กำหนดค่าการแบ่งหน้า (Pagination Settings) ---
$allowedLimits = [10, 25, 50];
if(isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowedLimits)){
    $itemsPerPage = (int)$_GET['limit'];
    $_SESSION['items_per_page'] = $itemsPerPage;
}elseif(isset($_SESSION['items_per_page']) && in_array((int)$_SESSION['items_per_page'], $allowedLimits)){
    $itemsPerPage = (int)$_SESSION['items_per_page'];
}else{
    $itemsPerPage = 10;
}
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// --- ส่วนที่ 5: ดึงข้อมูล Snippet ตามเงื่อนไข (บุ๊กมาร์ก / ค้นหา / ภาษา / Snippet ของฉัน) ---
if($showBookmarks && $userId){
    $totalSnippets = $control->getTotalBookmarkedSnippetCount($userId);
    $result = $control->getBookmarkedSnippetsPaginated($userId, $currentPage, $itemsPerPage);
}elseif($searchQuery !== '' || $selectedLanguage !== ''){
    $totalSnippets = $control->getTotalUserSnippetCount($userId, $searchQuery, $onlyOwn, $selectedLanguage);
    $result = $control->getUserSnippetsPaginated($userId, $currentPage, $itemsPerPage, $searchQuery, $onlyOwn, $selectedLanguage);
}else{
    $totalSnippets = $control->getTotalUserSnippetCount($userId, '', $onlyOwn, $selectedLanguage);
    $result = $control->getUserSnippetsPaginated($userId, $currentPage, $itemsPerPage, '', $onlyOwn, $selectedLanguage);
}

$snippets = [];
if($result){
    $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
}

// คำนวณจำนวนหน้าทั้งหมด
$totalPages = ceil($totalSnippets / $itemsPerPage);
if($totalPages === 0) $totalPages = 1;

// --- ส่วนที่ 6: ดึงข้อมูล ID บุ๊กมาร์ก และยอดไลก์ของแต่ละ Snippet ---
$bookmarkedIds = [];
if($userId){
    $bookmarkedIds = $control->getBookmarkedSnippetIds($userId);
}

$likedIds = [];
$likeCounts = [];
if($userId){
    $likedIds = $control->getLikedSnippetIds($userId);
}
$snippetIds = array_column($snippets, 'snippet_id');
$likeCounts = $control->getLikeCountsForSnippets($snippetIds);

// สร้าง URL สำหรับปุ่มเปิด-ปิดการแสดงเฉพาะบุ๊กมาร์ก
$bookmarkParams = [];
if(!$showBookmarks){
    $bookmarkParams['bookmarks'] = '1';
}
if($searchQuery !== ''){
    $bookmarkParams['q'] = $searchQuery;
}
if($selectedLanguage !== ''){
    $bookmarkParams['lang'] = $selectedLanguage;
}
if($onlyOwn){
    $bookmarkParams['only_own'] = '1';
}
if($itemsPerPage !== 10 || isset($_GET['limit'])){
    $bookmarkParams['limit'] = $itemsPerPage;
}
$bookmarkUrl = 'snippet_page.php' . (!empty($bookmarkParams) ? '?' . http_build_query($bookmarkParams) : '');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snippet ของฉัน - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/snippet_pagecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <a href="snippet_page.php" class="logo-brand">
                <img src="logo/Logo_snippetBox.png" alt="Snippet Box Logo" class="logo-img">
                <span class="logo-text">Snippet Box</span>
            </a>
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
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Komkit'); ?></span>
            </div>
            <a href="login.php" class="btn-outline" onclick="return confirm('คุณต้องการออกจากระบบหรือไม่?');">ออกจากระบบ</a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="sidebar">
            <a href="createsnippet.php" class="btn-create">+ สร้าง Snippet</a>
            <a href="remove.php" class="btn-bookmark" style="margin-top: 12px; justify-content: center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                ถังขยะ
            </a>
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
                    
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: var(--secondary); cursor: pointer; user-select: none; white-space: nowrap;">
                        <input type="checkbox" name="only_own" value="1" <?php echo $onlyOwn ? 'checked' : ''; ?> onchange="this.form.submit()" style="cursor: pointer; width: 18px; height: 18px; accent-color: var(--primary); margin: 0;">
                        <span>เฉพาะ Snippet ของฉัน</span>
                    </label>

                    <?php if($showBookmarks): ?><input type="hidden" name="bookmarks" value="1"><?php endif; ?>
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
                    <a href="<?php echo htmlspecialchars($bookmarkUrl); ?>" class="btn-bookmark <?php echo $showBookmarks ? 'active' : ''; ?>">
                        <svg width="14" height="18" viewBox="0 0 384 512" fill="<?php echo $showBookmarks ? '#fff' : 'currentColor'; ?>"><path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/></svg>
                        บุ๊กมาร์ก
                    </a>
                </div>
            </div>
            
            <div class="total-snippets">
                <?php if($showBookmarks): ?>
                    Snippet ที่คุณบุ๊กมาร์กไว้: <?php echo $totalSnippets; ?>
                <?php else: ?>
                    Snippet ของคุณ: <?php echo $totalSnippets; ?><?php if($searchQuery !== ''): ?> (ผลการค้นหาสำหรับ "<?php echo htmlspecialchars($searchQuery); ?>")<?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="snippet-grid">
                <?php if($totalSnippets === 0): ?>
                    <div class="empty-state">
                        <?php if($showBookmarks): ?>
                            ยังไม่มี Snippet ที่บุ๊กมาร์กไว้
                        <?php elseif($selectedLanguage !== ''): ?>
                            ไม่มีโพสต์
                        <?php else: ?>
                            ยังไม่มี Snippet ในระบบ
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach($snippets as $row): ?>
                        <?php $isBookmarked = in_array((int)$row['snippet_id'], $bookmarkedIds); ?>
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
                        <?php 
                            $isSuspended = (int)($row['is_suspended'] ?? 0) === 1;
                        ?>
                        <div class="snippet-card" <?php if($isSuspended): ?>style="border-left: 5px solid #dc2626; background-color: #fffcfc;"<?php endif; ?>>
                            <div class="card-left">
                                <?php
                                    $title = $row['title'] ?: 'ไม่มีชื่อเรื่อง';
                                    if(mb_strlen($title, 'UTF-8') > 30){
                                        $title = mb_substr($title, 0, 30, 'UTF-8') . '...';
                                    }
                                ?>
                                <div class="card-title">
                                    <?php echo htmlspecialchars($title); ?>
                                    <?php if($isSuspended): ?>
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #dc2626; background: #fee2e2; border: 1px solid #fecaca; border-radius: 4px; padding: 2px 6px; margin-left: 8px; vertical-align: middle;">
                                            ถูกระงับ (Suspended)
                                        </span>
                                    <?php endif; ?>
                                </div>
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

                                <?php if($isSuspended): ?>
                                    <?php 
                                        $cardAppeal = $control->getLatestAppealBySnippet($row['snippet_id']);
                                    ?>
                                    <!-- Suspension Warning Box for Owner -->
                                    <div style="margin-top: 10px; background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 8px 12px; font-size: 0.85rem; color: #991b1b; line-height: 1.4;">
                                        <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                                            <span>⚠️</span> โพสต์นี้ถูกระงับการแสดงผลโดยผู้ดูแลระบบ
                                        </div>
                                        <div><strong>เหตุผล:</strong> <?php echo htmlspecialchars($row['suspension_reason'] ?: 'ทำผิดข้อกำหนดชุมชน'); ?></div>
                                        <?php if($cardAppeal && $cardAppeal['status'] === 'pending'): ?>
                                            <div style="margin-top: 4px; padding: 3px 8px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 4px; font-size: 0.78rem; color: #92400e; font-weight: 600; display: inline-block;">
                                                ⏳ ยื่นคำร้องขอถอนการระงับแล้ว (รอการพิจารณา)
                                            </div>
                                        <?php elseif($cardAppeal && $cardAppeal['status'] === 'rejected'): ?>
                                            <div style="margin-top: 4px; padding: 3px 8px; background: #fee2e2; border: 1px solid #fecaca; border-radius: 4px; font-size: 0.78rem; color: #991b1b; font-weight: 600; display: inline-block;">
                                                ❌ คำร้องถูกปฏิเสธ (คลิก "ดูโค้ด" เพื่อดูเหตุผลหรือยื่นใหม่)
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 0.75rem; color: #b91c1c; margin-top: 3px;">
                                                (คลิก "ดูโค้ด" เพื่อยื่นคำร้องขอถอนการระงับ)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <button class="btn-view-code">
                                    <a href="viewcode.php?view=<?php echo urlencode($row['snippet_id']); ?>" >ดูโค้ด</a>
                                </button>
                            </div>
                            
                            <div class="card-middle">
                                <div class="<?php echo htmlspecialchars($languageClass); ?>"><?php echo htmlspecialchars($languageName ?: 'Language'); ?></div>
                            </div>
                            
                            <div class="card-right">
                                <?php 
                                    $snippetIdInt = (int)$row['snippet_id'];
                                    $isLiked = in_array($snippetIdInt, $likedIds);
                                    $currentLikeCount = $likeCounts[$snippetIdInt] ?? 0;
                                ?>
                                <?php if(!$isSuspended): ?>
                                    <button class="like-toggle <?php echo $isLiked ? 'liked' : ''; ?>" data-snippet-id="<?php echo $snippetIdInt; ?>" title="<?php echo $isLiked ? 'ยกเลิกถูกใจ' : 'ถูกใจ'; ?>">
                                        <svg width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                                        </svg>
                                        <span class="like-count"><?php echo $currentLikeCount; ?></span>
                                    </button>
                                    <button class="bookmark-toggle <?php echo $isBookmarked ? 'bookmarked' : ''; ?>" data-snippet-id="<?php echo (int)$row['snippet_id']; ?>" title="<?php echo $isBookmarked ? 'ลบบุ๊กมาร์ก' : 'เพิ่มบุ๊กมาร์ก'; ?>">
                                        <svg width="20" height="24" viewBox="0 0 384 512" stroke-width="30">
                                            <path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/>
                                        </svg>
                                    </button>
                                    <?php if((int)($row['user_id'] ?? 0) === (int)$userId): ?>
                                    <a href="createsnippet.php?edit=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="แก้ไข">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                    </a>
                                    <a href="createsnippet.php?delete=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="ลบ" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?');">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: #dc2626; font-weight: 600; text-align: center; display: block; padding: 4px 8px; background: #fee2e2; border-radius: 4px;">
                                        ถูกระงับ
                                    </span>
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
                // Show pages 1 to 10
                $startPage = 1;
                $endPage = min(10, $totalPages);
                
                for($i = $startPage; $i <= $endPage; $i++){
                    $pageUrl = 'snippet_page.php?page=' . $i;
                    if($searchQuery !== '') $pageUrl .= '&q=' . urlencode($searchQuery);
                    if($selectedLanguage !== '') $pageUrl .= '&lang=' . urlencode($selectedLanguage);
                    if($onlyOwn) $pageUrl .= '&only_own=1';
                    if($showBookmarks) $pageUrl .= '&bookmarks=1';
                    if($itemsPerPage !== 10 || isset($_GET['limit'])) $pageUrl .= '&limit=' . $itemsPerPage;
                    
                    if($i == $currentPage){
                        echo '<span class="active">' . $i . '</span>';
                    } else {
                        echo '<a href="' . $pageUrl . '">' . $i . '</a>';
                    }
                }
                
                // Next button if there are more pages beyond 10
                if($totalPages > 10 && $currentPage < $totalPages){
                    $nextPageUrl = 'snippet_page.php?page=' . ($currentPage + 1);
                    if($searchQuery !== '') $nextPageUrl .= '&q=' . urlencode($searchQuery);
                    if($selectedLanguage !== '') $nextPageUrl .= '&lang=' . urlencode($selectedLanguage);
                    if($onlyOwn) $nextPageUrl .= '&only_own=1';
                    if($showBookmarks) $nextPageUrl .= '&bookmarks=1';
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
        // --- ส่วนที่ 8: สคริปต์ JavaScript จัดการการทำงานฝั่ง Client ในหน้า Snippet ของฉัน ---

        /**
         * ฟังก์ชัน: changePerPage
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: จัดการการแบ่งหน้า]
         * หน้าที่: เปลี่ยนจำนวนรายการที่แสดงต่อหน้า (10, 25, 50) และรีเฟรชกลับหน้า 1
         * พารามิเตอร์: val
         */
        function changePerPage(val) {
            const url = new URL(window.location.href);
            url.searchParams.set('limit', val);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }

        /**
         * ฟังก์ชัน: showToast
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: กล่องข้อความแจ้งเตือน]
         * หน้าที่: แสดงกล่องข้อความ Toast ชั่วคราว 2 วินาที
         * พารามิเตอร์: message
         */
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        // จัดการการกดปุ่มถูกใจ (Like Toggle) บนการ์ด Snippet ด้วย AJAX (like_action.php)
        document.querySelectorAll('.like-toggle').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const snippetId = this.dataset.snippetId;
                
                try {
                    const formData = new FormData();
                    formData.append('snippet_id', snippetId);
                    
                    const response = await fetch('like_action.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    const countEl = this.querySelector('.like-count');
                    
                    if(data.status === 'added') {
                        this.classList.add('liked');
                        this.title = 'ยกเลิกถูกใจ';
                        countEl.textContent = data.count;
                    } else if(data.status === 'removed') {
                        this.classList.remove('liked');
                        this.title = 'ถูกใจ';
                        countEl.textContent = data.count;
                        showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                    }
                } catch(err) {
                    showToast('⚠ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                }
            });
        });

        // จัดการการกดปุ่มบุ๊กมาร์ก (Bookmark Toggle) บนการ์ด Snippet ด้วย AJAX (bookmark_action.php)
        document.querySelectorAll('.bookmark-toggle').forEach(btn => {
            btn.addEventListener('click', async function(e) {
                e.preventDefault();
                const snippetId = this.dataset.snippetId;
                
                try {
                    const formData = new FormData();
                    formData.append('snippet_id', snippetId);
                    
                    const response = await fetch('bookmark_action.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if(data.status === 'added') {
                        this.classList.add('bookmarked');
                        this.title = 'ลบบุ๊กมาร์ก';
                        showToast('✓ บุ๊กมาร์กแล้ว!');
                    } else if(data.status === 'removed') {
                        this.classList.remove('bookmarked');
                        this.title = 'เพิ่มบุ๊กมาร์ก';
                        showToast('✗ ลบบุ๊กมาร์กแล้ว');
                        
                        // หากกำลังเปิดตัวกรองดูเฉพาะบุ๊กมาร์ก ให้โหลดหน้าใหม่เพื่อตัดรายการออก
                        const urlParams = new URLSearchParams(window.location.search);
                        if(urlParams.get('bookmarks') === '1') {
                            setTimeout(() => window.location.reload(), 500);
                        }
                    } else if(data.status === 'error') {
                        showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                    }
                } catch(err) {
                    showToast('⚠ เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
                }
            });
        });
    </script>
</body>
</html>
