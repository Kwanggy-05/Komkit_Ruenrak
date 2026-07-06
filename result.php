<?php
require_once "layout/session.php";
require_once "db/connect.php";

$searchQuery = trim($_GET['q'] ?? '');
$selectedLanguage = $_GET['lang'] ?? '';
$snippets = [];

// Get all languages
$languageResult = $control->getLanguage();
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

if($searchQuery !== '' || $selectedLanguage !== ''){
    $result = $control->searchSnippets($searchQuery, $selectedLanguage);
    if($result){
        $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
    }
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Snippet Box</title>
    <link rel="stylesheet" href="style/Homecss.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f5f6;
            color: #000;
        }

        main.results-container {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            max-width: 1000px;
            width: 100%;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
            padding-bottom: 5vh;
        }

        .results-header {
            margin-bottom: 24px;
            font-size: 1rem;
            color: #666;
            font-weight: 500;
            text-align: center;
        }

        .snippet-grid {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Snippet Card styled exactly like user mockup */
        .snippet-card {
            width: 100%;
            border: 1px solid #000;
            border-radius: 16px;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f4f5f6; /* matches the mockup card background */
            box-sizing: border-box;
        }

        .card-left {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: flex-start;
            flex: 2;
            min-width: 0;
        }

        .card-title {
            font-size: 32px;
            font-weight: 400;
            color: #000;
            letter-spacing: -0.02em;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .btn-view-code {
            background-color: #fff;
            color: #000;
            border: 1px solid #000;
            border-radius: 6px;
            padding: 6px 14px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-view-code:hover {
            background-color: #000;
            color: #fff;
        }

        .card-middle {
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
        }

        /* Language Tag matching mockup colors */
        .language-tag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #3b82f6;
            border-radius: 24px;
            padding: 6px 20px;
            font-size: 15px;
            font-weight: 500;
            line-height: 1;
            background: #eff6ff;
            color: #1d4ed8;
        }

        .language-tag.language-html { color: #e34c26; border-color: #ff8a65; background: #fff5f2; }
        .language-tag.language-python { color: #306998; border-color: #8cc4fc; background: #f0f7ff; }
        .language-tag.language-javascript { color: #0284c7; border-color: #7dd3fc; background: #f0f9ff; }
        .language-tag.language-php { color: #777bb4; border-color: #b3b7e2; background: #f5f6fa; }
        .language-tag.language-sql { color: #00758f; border-color: #70d6ec; background: #eefbfe; }
        .language-tag.language-css { color: #1572b6; border-color: #7ec5f8; background: #f0f8ff; }

        /* Visibility Badges */
        .visibility {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1;
            border: 1px solid #d0d0d0;
        }
        .visibility.public {
            background: #e6f6ff;
            color: #0366a6;
            border-color: #bfe8ff;
        }
        .visibility.protected {
            background: #fff4e6;
            color: #b45309;
            border-color: #ffd8a8;
        }

        .card-right {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            flex-shrink: 0;
        }

        .like-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
            color: #000;
        }

        .like-toggle:hover {
            transform: scale(1.15);
        }

        .like-toggle svg {
            transition: fill 0.2s ease, stroke 0.2s ease;
        }

        .like-toggle:not(.liked) svg {
            fill: none;
            stroke: #000;
        }

        .like-toggle.liked svg {
            fill: #000;
            stroke: #000;
        }

        .like-count {
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #666;
            border: 1px dashed #000;
            border-radius: 16px;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #1a1a1a;
            color: #fff;
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        @media(max-width: 768px) {
            .snippet-card {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
                padding: 20px;
            }
            .card-middle {
                justify-content: flex-start;
                width: 100%;
            }
            .card-right {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
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
                    <input class="search-input" type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search Snippet" required>
                    <input type="hidden" name="lang" value="<?php echo htmlspecialchars($selectedLanguage); ?>">
                    <!-- <button type="submit" class="search-submit">Search</button> -->
                </form>
            </div>
            <div class="language-dropdown">
                <form method="GET" action="" style="display: inline;">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                    <select name="lang" id="language-filter" onchange="this.form.submit()">
                        <option value="">All Languages</option>
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
          <a href="register.php"><button class="btn btn-register">Register</button></a>
            
            <a href="login.php"><button class="btn btn-login">Login</button></a>
        </div>
    </header>

    <main class="results-container">
        <div class="results-header">
            <?php if ($searchQuery !== '' && $selectedLanguage !== ''): ?>
                
Search results for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>" in language <strong><?php echo htmlspecialchars($selectedLanguageName); ?></strong>
            <?php elseif ($searchQuery !== ''): ?>              
Search results for "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
            <?php elseif ($selectedLanguage !== ''): ?>
Search results for <strong><?php echo htmlspecialchars($selectedLanguageName); ?></strong>
            <?php else: ?>
                กรุณากรอกคำค้นหาหรือเลือกภาษาด้านบนเพื่อค้นหา Snippet
            <?php endif; ?>
        </div>

        <div class="snippet-grid">
            <?php if ($searchQuery === '' && $selectedLanguage === ''): ?>
                <div class="empty-state">
                    Please enter a search term or select a language above to start searching.
                </div>
            <?php elseif (empty($snippets)): ?>
                <div class="empty-state">   
                    No search results found.
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
                    ?>
                    <div class="snippet-card">
                        <div class="card-left">
                            <?php
                                $title = $row['title'] ?: 'Untitled';
                                if(mb_strlen($title, 'UTF-8') > 45){
                                    $title = mb_substr($title, 0, 45, 'UTF-8') . '...';
                                }
                            ?>
                            <div class="card-title"><?php echo htmlspecialchars($title); ?></div>
                            <a href="viewcode.php?view=<?php echo urlencode($row['snippet_id']); ?>" class="btn-view-code">View code</a>
                        </div>
                        
                        <div class="card-middle" style="gap: 12px;">
                            <div class="<?php echo htmlspecialchars($languageClass); ?>"><?php echo htmlspecialchars($languageName ?: 'Language'); ?></div>
                            <div class="visibility <?php echo htmlspecialchars($row['visibility']); ?>"><?php echo ucfirst(htmlspecialchars($row['visibility'])); ?></div>
                        </div>
                        
                        <div class="card-right">
                            <button class="like-toggle <?php echo $isLiked ? 'liked' : ''; ?>" data-snippet-id="<?php echo (int)$row['snippet_id']; ?>" title="<?php echo $isLiked ? 'Unlike' : 'Like'; ?>">
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
                        this.title = 'Unlike';
                        countEl.textContent = data.count;
                    } else if(data.status === 'removed') {
                        this.classList.remove('liked');
                        this.title = 'Like';
                        countEl.textContent = data.count;
                    } else if(data.status === 'error') {
                        showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                    }
                } catch(err) {
                    showToast('⚠ เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
            });
        });
    </script>
</body>
</html>
