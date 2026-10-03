<?php
require_once "layout/session.php";
require_once "db/connect.php";

$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = $_GET['lang'] ?? '';
$snippets = [];

// Get all languages with snippet counts
$languageResult = $control->getAllLanguagesWithSnippetCounts();
$languages = [];
if($languageResult){
    $languages = $languageResult->fetchAll(PDO::FETCH_ASSOC);
}

// Search with language filter
$selectedLanguageName = '';
if($selectedLanguage !== ''){
    foreach($languages as $lang){
        if($lang['language_id'] == $selectedLanguage){
            $selectedLanguageName = $lang['language_name'];
            break;
        }
    }
}

// Always fetch snippets (if q and lang are empty, returns all public & protected snippets)
$result = $control->searchSnippets($searchQuery, $selectedLanguage);
if($result){
    $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
}

$userId = getCurrentUserId();
$likedIds = [];
if($userId){
    $likedIds = $control->getLikedSnippetIds($userId);
}

$snippetIds = array_column($snippets, 'snippet_id');
$likeCounts = $control->getLikeCountsForSnippets($snippetIds);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการค้นหา - Snippet Box</title>
    <link rel="stylesheet" href="style/Homecss.css">
    <link rel="stylesheet" href="style/resultcss.css">
</head>
<body>

    <header>
        <div class="header-left">
            <div class="logo-icon">
                <a href="Home.php"><img src="logo/Logo_snippetBox.png" alt="Logo" width=32px height=32px></a>
            </div>
            <div class="logo-text">Snippet Box</div>
        </div>

        <div class="header-center">
            <div class="search-bar">
                <form method="GET" action="" class="search-form" id="searchForm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input class="search-input" type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="ค้นหา Snippet...">
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($selectedLanguage); ?>">
                    <!-- <button type="submit" class="search-submit">Search</button> -->
                </form>
            </div>
            <div class="language-dropdown">
                <form method="GET" action="" style="display: inline;">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="lang" id="language-filter" onchange="this.form.submit()">
                        <option value="">ทุกภาษา</option>
                        <?php foreach($languages as $lang): ?>
                            <option value="<?php echo htmlspecialchars($lang['language_id']); ?>" 
                                <?php echo ($selectedLanguage == $lang['language_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lang['language_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="header-right">
          <a href="register.php"><button class="btn btn-register">สมัครสมาชิก</button></a>
            
            <a href="login.php"><button class="btn btn-login">เข้าสู่ระบบ</button></a>
        </div>
    </header>

    <main class="results-container">
        <div class="results-header">
            <?php if ($searchQuery !== '' && $selectedLanguage !== ''): ?>
                ผลการค้นหาสำหรับ "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" ในภาษา <strong><?php echo htmlspecialchars($selectedLanguageName); ?></strong>
            <?php elseif ($searchQuery !== ''): ?>              
                ผลการค้นหาสำหรับ "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
            <?php elseif ($selectedLanguage !== ''): ?>
                ผลการค้นหาสำหรับภาษา <strong><?php echo htmlspecialchars($selectedLanguageName); ?></strong>
            <?php else: ?>
                Snippet สาธารณะและสำหรับสมาชิกทั้งหมด
            <?php endif; ?>
        </div>

        <div class="snippet-grid">
            <?php if (empty($snippets)): ?>
                <div class="empty-state">   
                    ไม่มีโพสต์
                </div>
            <?php else: ?>
                <?php foreach($snippets as $row): ?>
                    <?php 
                        $isLiked = in_array((int)$row['snippet_id'], $likedIds);
                        $currentLikeCount = $likeCounts[(int)$row['snippet_id']] ?? 0;
                        
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

                        $visLabel = 'สาธารณะ';
                        if ($row['visibility'] === 'protected') {
                            $visLabel = 'เฉพาะสมาชิก';
                        } elseif ($row['visibility'] === 'private') {
                            $visLabel = 'ส่วนตัว';
                        }
                    ?>
                    <div class="snippet-card">
                        <div class="card-left">
                            <?php
                                $title = $row['title'] ?: 'ไม่มีชื่อเรื่อง';
                                if(mb_strlen($title, 'UTF-8') > 45){
                                    $title = mb_substr($title, 0, 45, 'UTF-8') . '...';
                                }
                            ?>
                            <div class="card-title"><?php echo htmlspecialchars($title); ?></div>
                            <?php
                                $viewUrl = 'viewcode.php?view=' . urlencode($row['snippet_id']) . '&from_search=1';
                                if ($searchQuery !== '') {
                                    $viewUrl .= '&q=' . urlencode($searchQuery);
                                }
                                if ($selectedLanguage !== '') {
                                    $viewUrl .= '&lang=' . urlencode($selectedLanguage);
                                }
                            ?>
                            <a href="<?php echo htmlspecialchars($viewUrl); ?>" class="btn-view-code">ดูโค้ด</a>
                        </div>
                        
                        <div class="card-middle" style="gap: 12px;">
                            <div class="<?php echo htmlspecialchars($languageClass); ?>"><?php echo htmlspecialchars($languageName ?: 'Language'); ?></div>
                            <div class="visibility <?php echo htmlspecialchars($row['visibility']); ?>"><?php echo htmlspecialchars($visLabel); ?></div>
                        </div>
                        
                        <div class="card-right">
                            <button class="like-toggle <?php echo $isLiked ? 'liked' : ''; ?>" data-snippet-id="<?php echo (int)$row['snippet_id']; ?>" title="<?php echo $isLiked ? 'ยกเลิกถูกใจ' : 'ถูกใจ'; ?>">
                                <svg width="32" height="32" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                                </svg>
                                <span class="like-count"><?php echo $currentLikeCount; ?></span>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        // Handle like toggle on each card
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
                    } else if(data.status === 'error') {
                        showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                    }
                } catch(err) {
                    showToast('⚠ ข้อผิดพลาดในการเชื่อมต่อ');
                }
            });
        });
    </script>
</body>
</html>
