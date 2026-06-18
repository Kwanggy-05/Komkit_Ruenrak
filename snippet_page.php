<?php
require_once "layout/session.php";
require_once "db/connect.php";
// require_once "layout/check_user.php";

$searchQuery = trim($_GET['q'] ?? '');
$showBookmarks = isset($_GET['bookmarks']) && $_GET['bookmarks'] === '1';
$userId = $_SESSION['userid'] ?? 0;

if($showBookmarks && $userId){
    $result = $control->getBookmarkedSnippets($userId);
}elseif($searchQuery !== ''){
    $result = $control->searchSnippets($searchQuery);
}else{
    $result = $control->getSnippet();
}

$snippets = [];
if($result){
    $snippets = $result->fetchAll(PDO::FETCH_ASSOC);
}

$totalSnippets = count($snippets);

// ดึง list ของ snippet_id ที่ถูก bookmark ไว้
$bookmarkedIds = [];
if($userId){
    $bookmarkedIds = $control->getBookmarkedSnippetIds($userId);
}

// ดึง list ของ snippet_id ที่ถูก like ไว้ และจำนวน like ของแต่ละ snippet
$likedIds = [];
$likeCounts = [];
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
    <title>Snippet Page</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap');
        
        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f6f6f6; 
            color: #000; 
        }
        
        a { 
            color: inherit; 
            text-decoration: none; 
        }
        
        /* Header styling */
        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 20px 40px; 
        }
        
        .nav-left, .nav-right { 
            display: flex; 
            align-items: center; 
            gap: 16px; 
        }
        
        .btn-outline { 
            border: 1px solid #000; 
            border-radius: 24px; 
            padding: 8px 24px; 
            font-size: 16px; 
            background: transparent; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        .search-box { 
            display: flex; 
            align-items: center; 
            border: 1px solid #000; 
            border-radius: 24px; 
            padding: 8px 16px; 
            background: transparent; 
            width: 280px; 
        }
        
        .search-box input { 
            border: none; 
            outline: none; 
            background: transparent; 
            width: 100%; 
            margin-left: 8px; 
            font-size: 15px; 
            font-family: 'Inter', sans-serif;
        }
        
        .search-box input::placeholder { 
            color: #888; 
        }
        
        .dropdown { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            border: 1px solid #000; 
            border-radius: 24px; 
            padding: 8px 16px; 
            font-size: 15px; 
            cursor: pointer; 
        }
        
        .bell-icon { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            margin-right: 12px; 
        }
        
        .user-profile { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            font-size: 22px; 
            font-weight: 500; 
            margin-right: 12px; 
        }
        
        .avatar { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        /* Main Layout */
        .main-wrapper { 
            display: flex; 
            padding: 20px 40px; 
            gap: 30px; 
        }
        
        .sidebar { 
            width: 130px; 
            flex-shrink: 0; 
        }
        
        .btn-create { 
            background: #1a1a1a; 
            color: #fff; 
            border-radius: 12px; 
            padding: 12px 16px; 
            font-size: 22px; 
            font-weight: 400; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            width: 100%; 
        }
        
        .content { 
            flex-grow: 1; 
            max-width: 900px; 
        }
        
        .toolbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 8px; 
        }
        
        .toolbar .search-box { 
            width: 350px; 
        }
        
        .btn-bookmark { 
            display: flex; 
            align-items: center; 
            gap: 8px; 
            border: 1px solid #000; 
            border-radius: 24px; 
            padding: 8px 20px; 
            font-size: 16px; 
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-bookmark:hover {
            background: #f0f0f0;
        }

        .btn-bookmark.active {
            background: #1a1a1a;
            color: #fff;
            border-color: #1a1a1a;
        }

        .btn-bookmark.active svg {
            fill: #fff;
            stroke: #fff;
        }
        
        .total-snippets { 
            color: #777; 
            font-size: 15px; 
            margin-bottom: 24px; 
            margin-left: 12px; 
        }
        
        /* Snippet Cards */
        .snippet-grid { 
            display: flex; 
            flex-direction: column; 
            gap: 16px; 
        }
        
        .snippet-card { 
            border: 1px solid #000; 
            border-radius: 16px; 
            padding: 24px 32px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: transparent; 
        }
        
        .card-left { 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            align-items: flex-start; 
        }

        .card-title { 
            font-size: 26px; 
            font-weight: 400; 
            color: #000; 
        }

        .tag-badge {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
            font-size: 13px;
            color: #333;
        }

        .tag-badge span {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            padding: 4px 10px;
            border: 1px solid #000; 
            border-radius: 8px; 
            padding: 4px 12px; 
            font-size: 13px; 
        }
        
        .card-middle { 
            flex-grow: 1; 
            display: flex; 
            justify-content: center; 
        }
        
        .language-tag { 
            color: #3b82f6; 
            border: 1px solid #3b82f6; 
            border-radius: 24px; 
            padding: 4px 20px; 
            font-size: 15px; 
        }
        
        .card-right { 
            display: flex; 
            align-items: center; 
            gap: 24px; 
        }
        
        .icon-action { 
            color: #000; 
            cursor: pointer; 
        }

        /* Bookmark icon on each card */
        .bookmark-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .bookmark-toggle:hover {
            transform: scale(1.2);
        }

        .bookmark-toggle svg {
            transition: fill 0.2s ease;
        }

        .bookmark-toggle.bookmarked svg {
            fill: #f59e0b;
            stroke: #f59e0b;
        }

        .bookmark-toggle:not(.bookmarked) svg {
            fill: none;
            stroke: #000;
        }

        /* Like button */
        .like-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            transition: transform 0.2s ease;
        }

        .like-toggle:hover {
            transform: scale(1.2);
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
            font-size: 13px;
            color: #555;
            font-weight: 500;
            line-height: 1;
        }

        .like-toggle.liked .like-count {
            color: #000;
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
        
        .empty-state { 
            padding: 40px; 
            text-align: center; 
            color: #666; 
            border: 1px dashed #000; 
            border-radius: 16px; 
        }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="nav-left">
            <!-- <a href="testweb.html" class="btn-outline">Back</a> -->
            <div class="search-box">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" placeholder="Search snippet..." disabled>
            </div>
            <div class="dropdown">
                All Languages
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </div>
        </div>
        <div class="nav-right">
            <div class="bell-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/></svg>
            </div>
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
            <a href="login.php" class="btn-outline">Log out</a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="sidebar">
            <a href="createsnippet.php" class="btn-create">+ Create</a>
        </div>
        
        <div class="content">
            <div class="toolbar">
                <form method="GET" class="search-box" style="width:350px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search your snippets...">
                </form>
                <a href="<?php echo $showBookmarks ? 'snippet_page.php' : 'snippet_page.php?bookmarks=1'; ?>" class="btn-bookmark <?php echo $showBookmarks ? 'active' : ''; ?>">
                    <svg width="14" height="18" viewBox="0 0 384 512" fill="<?php echo $showBookmarks ? '#fff' : 'currentColor'; ?>"><path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/></svg>
                    Bookmark
                </a>
            </div>
            
            <div class="total-snippets">
                <?php if($showBookmarks): ?>
                    Bookmarked Snippets: <?php echo $totalSnippets; ?>
                <?php else: ?>
                    Total Snippets: <?php echo $totalSnippets; ?><?php if($searchQuery !== ''): ?> (search results for "<?php echo htmlspecialchars($searchQuery); ?>" — only public/protected shown)<?php endif; ?>
                <?php endif; ?>
            </div>
            
            <div class="snippet-grid">
                <?php if($totalSnippets === 0): ?>
                    <div class="empty-state">
                        <?php if($showBookmarks): ?>
                            ยังไม่มี Snippet ที่ถูก Bookmark ไว้
                        <?php else: ?>
                            ยังไม่มี Snippet ในระบบ
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach($snippets as $row): ?>
                        <?php $isBookmarked = in_array((int)$row['snippet_id'], $bookmarkedIds); ?>
                        <div class="snippet-card">
                            <div class="card-left">
                                <div class="card-title"><?php echo htmlspecialchars($row['title'] ?: 'Title'); ?></div>
                                <?php if(!empty($row['tags'])): ?>
                                    <div class="tag-badge">
                                        <?php foreach(explode(', ', $row['tags']) as $tagName): ?>
                                            <span><?php echo htmlspecialchars($tagName); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <a href="viewcode.php?view=<?php echo urlencode($row['snippet_id']); ?>" class="btn-view-code">View code</a>
                            </div>
                            
                            <div class="card-middle">
                                <div class="language-tag"><?php echo htmlspecialchars($row['language_name'] ?: 'Language'); ?></div>
                            </div>
                            
                            <div class="card-right">
                                <?php 
                                    $snippetIdInt = (int)$row['snippet_id'];
                                    $isLiked = in_array($snippetIdInt, $likedIds);
                                    $currentLikeCount = $likeCounts[$snippetIdInt] ?? 0;
                                ?>
                                <button class="like-toggle <?php echo $isLiked ? 'liked' : ''; ?>" data-snippet-id="<?php echo $snippetIdInt; ?>" title="<?php echo $isLiked ? 'Unlike' : 'Like'; ?>">
                                    <svg width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                                    </svg>
                                    <span class="like-count"><?php echo $currentLikeCount; ?></span>
                                </button>
                                <button class="bookmark-toggle <?php echo $isBookmarked ? 'bookmarked' : ''; ?>" data-snippet-id="<?php echo (int)$row['snippet_id']; ?>" title="<?php echo $isBookmarked ? 'Remove Bookmark' : 'Add Bookmark'; ?>">
                                    <svg width="20" height="24" viewBox="0 0 384 512" stroke-width="30">
                                        <path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/>
                                    </svg>
                                </button>
                                <a href="createsnippet.php?edit=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="Edit">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </a>
                                <a href="createsnippet.php?delete=<?php echo urlencode($row['snippet_id']); ?>" class="icon-action" title="Delete" onclick="return confirm('คุณแน่ใจว่าต้องการลบรายการนี้หรือไม่?');">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
                        showToast('👍 Liked!');
                    } else if(data.status === 'removed') {
                        this.classList.remove('liked');
                        this.title = 'Like';
                        countEl.textContent = data.count;
                        showToast('👎 Like removed');
                    } else if(data.status === 'error') {
                        showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                    }
                } catch(err) {
                    showToast('⚠ เกิดข้อผิดพลาดในการเชื่อมต่อ');
                }
            });
        });

        // Handle bookmark toggle on each card
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
                        this.title = 'Remove Bookmark';
                        showToast('✓ Bookmarked!');
                    } else if(data.status === 'removed') {
                        this.classList.remove('bookmarked');
                        this.title = 'Add Bookmark';
                        showToast('✗ Bookmark removed');
                        
                        // ถ้าอยู่ในหน้า bookmarks filter ให้ลบ card ออก
                        const urlParams = new URLSearchParams(window.location.search);
                        if(urlParams.get('bookmarks') === '1') {
                            const card = this.closest('.snippet-card');
                            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                            card.style.opacity = '0';
                            card.style.transform = 'translateX(-20px)';
                            setTimeout(() => {
                                card.remove();
                                // Update count
                                const remaining = document.querySelectorAll('.snippet-card').length;
                                document.querySelector('.total-snippets').textContent = 'Bookmarked Snippets: ' + remaining;
                                if(remaining === 0) {
                                    document.querySelector('.snippet-grid').innerHTML = '<div class="empty-state">ยังไม่มี Snippet ที่ถูก Bookmark ไว้</div>';
                                }
                            }, 300);
                        }
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