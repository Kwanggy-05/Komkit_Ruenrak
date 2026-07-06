<?php
require_once "layout/session.php";
require_once "db/connect.php";

$snippet = null;
$isBookmarked = false;
$isLiked = false;
$likeCount = 0;
$userId = getCurrentUserId();
$accessDenied = false;

if(isset($_GET['view']) && is_numeric($_GET['view'])){
    $id = (int)$_GET['view'];
    $snippet = $control->getSnippetById($id, $userId);
    if($snippet && strtolower((string)($snippet['visibility'] ?? '')) === 'protected' && !$userId){
        $accessDenied = true;
    }
    if($snippet && $userId){
        $isBookmarked = $control->isBookmarked($userId, $id);
        $isLiked = $control->isLiked($userId, $id);
    }
    if($snippet){
        $likeCount = $control->getLikeCount($id);
    }
}

if($accessDenied){
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>View Snippet</title>
        <style>
            body{font-family:Inter,Arial,sans-serif;background:#f4f5f7;color:#111;margin:20px}
            .back-btn{background:#fff;border:1px solid #888;padding:8px 20px;border-radius:8px;text-decoration:none;color:#111;display:inline-block;margin-bottom:18px}
            .empty{border:1px dashed #888;background:#fff;padding:24px;border-radius:12px;max-width:360px}
        </style>
    </head>
    <body>
        <a href="search.php<?php echo isset($_GET['q']) ? '?q=' . urlencode($_GET['q']) . (isset($_GET['lang']) && $_GET['lang'] !== '' ? '&lang=' . urlencode($_GET['lang']) : '') : ''; ?>" class="back-btn">Back</a>
        <div class="empty">โปรดเข้าสู่ระบบ</div>
    </body>
    </html>
    <?php
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($snippet['title'] ?? 'View Snippet'); ?></title>
    <style>
        body{font-family: Inter, Arial, sans-serif;background:#f4f5f7;color:#111;margin:20px}
        .back-btn{background:#fff;border:1px solid #888;padding:8px 20px;border-radius:8px;text-decoration:none;color:#111;display:inline-block;margin-bottom:18px}
        .top-row{display:flex;align-items:center;justify-content:space-between;gap:12px}
        .title-row{display:flex;align-items:center;gap:16px}
        .title-row h2{font-weight:400;margin:0;text-transform:none}
        .badge{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            padding:6px 12px;
            border-radius:16px;
            border:1px solid #3b82f6;
            font-size:14px;
            font-weight:500;
            line-height:1;
            background:#fff;
        }
        .badge.language-html{color:#E34F26;border-color:#E34F26}
        .badge.language-python{color:#3776AB;border-color:#3776AB}
        .badge.language-javascript{color:#F7DF1E;border-color:#F7DF1E;}
        .badge.language-php{color:#777BB4;border-color:#777BB4}
        .badge.language-sql{color:#00758F;border-color:#00758F}
        .badge.language-css{color:#1572B6;border-color:#1572B6}
        .visibility{padding:6px 12px;border-radius:16px;font-size:14px;border:1px solid #d0d0d0}
        .visibility.public{background:#e6f6ff;color:#0366a6;border-color:#bfe8ff}
        .visibility.protected{background:#fff4e6;color:#b45309;border-color:#ffd8a8}
        .visibility.private{background:#efefef;color:#666;border-color:#d0d0d0}
        .actions{display:flex;gap:12px;align-items:center}
        .btn{border:1px solid #111;padding:8px 16px;border-radius:8px;background:#fff;cursor:pointer;transition:all 0.2s ease}
        .btn.copy{background:#000;color:#fff;border:none}
        .btn.bookmark-btn{display:inline-flex;align-items:center;gap:6px}
        .btn.bookmark-btn.bookmarked{background:#f59e0b;border-color:#f59e0b;color:#fff}
        .btn.bookmark-btn:hover{transform:scale(1.05)}
        .toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(100px);background:#1a1a1a;color:#fff;padding:12px 28px;border-radius:12px;font-size:14px;z-index:9999;opacity:0;transition:all 0.3s ease;pointer-events:none}
        .toast.show{opacity:1;transform:translateX(-50%) translateY(0)}

        .btn.like-btn{display:inline-flex;align-items:center;gap:6px}
        .btn.like-btn.liked{background:#000;color:#fff;border-color:#000}
        .btn.like-btn:hover{transform:scale(1.05)}
        .like-btn-count{font-weight:600}

        .code-container{margin-top:26px;display:flex;gap:24px}
        .code-box{flex:1;border:1px solid #bdbdbd;border-radius:12px;overflow:hidden;background:#fff;display:flex}
        .line-numbers{width:56px;background:#efefef;color:#666;text-align:center;padding:12px 6px;font-family:monospace;font-size:13px;line-height:22px;user-select:none;flex-shrink:0}
        .line-number{height:22px;line-height:22px}
        .code-area{flex:1;padding:12px 18px;overflow:auto;font-family:monospace;font-size:14px}
        .code-content{margin:0}
        .code-line{height:22px;line-height:22px;white-space:pre}

        .meta{width:320px;display:flex;flex-direction:column;gap:18px}
        .image-box{height:140px;border:1px solid #bdbdbd;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;color:#aaa}
        .desc{border:1px solid #bdbdbd;border-radius:12px;padding:12px;background:#fff;min-height:140px}
        .tags{margin-top:6px}
        .tags strong{margin-right:8px}

        @media(max-width:900px){.code-container{flex-direction:column}.meta{width:100%}}
    </style>
</head>
<body>
    <a href="snippet_page.php" class="back-btn">Back</a>

    <?php if(!$snippet): ?>
        <div class="empty">ไม่พบ snippet นี้</div>
    <?php else: ?>
        <div class="top-row">
            <div class="title-row">
                <h2><?php echo htmlspecialchars($snippet['title']); ?></h2>
                <?php
                    $languageName = trim((string)($snippet['language_name'] ?? 'Language'));
                    $languageKey = strtolower($languageName);
                    $languageClass = 'badge';
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
                <div class="<?php echo htmlspecialchars($languageClass); ?>"><?php echo htmlspecialchars($languageName); ?></div>
                <div class="visibility <?php echo htmlspecialchars($snippet['visibility'] ?? 'private'); ?>"><?php echo ucfirst(htmlspecialchars($snippet['visibility'] ?? 'private')); ?></div>
            </div>

            <div class="actions">
                <button class="btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" id="likeBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $isLiked ? '#fff' : 'none'; ?>" stroke="<?php echo $isLiked ? '#fff' : '#111'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                    </svg>
                    <span class="like-btn-count"><?php echo $likeCount; ?></span>
                </button>
                <button class="btn bookmark-btn <?php echo $isBookmarked ? 'bookmarked' : ''; ?>" id="bookmarkBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                    <svg width="14" height="18" viewBox="0 0 384 512" fill="<?php echo $isBookmarked ? '#fff' : 'none'; ?>" stroke="<?php echo $isBookmarked ? '#fff' : '#111'; ?>" stroke-width="30">
                        <path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/>
                    </svg>
                    <?php echo $isBookmarked ? 'Bookmarked' : 'Bookmark'; ?>
                </button>
                <button class="btn copy" id="copyBtn">Copy</button>
            </div>
        </div>

        <div class="code-container">
            <div class="code-box">
                <div class="line-numbers" id="lineNumbers">1</div>
                <div class="code-area"><div class="code-content" id="codeContent"><?php echo htmlspecialchars($snippet['source_code']); ?></div></div>
            </div>

            <div class="meta">
                <div>
                    <label>Image</label>
                    <div class="image-box">
                        <?php if(!empty($snippet['image_path']) && file_exists($snippet['image_path'])): ?>
                            <img src="<?php echo htmlspecialchars($snippet['image_path']); ?>" alt="image" style="max-height:120px;max-width:100%;border-radius:8px;object-fit:cover">
                        <?php else: ?>
                            <span>No Image</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label>Description</label>
                    <div class="desc"><?php echo nl2br(htmlspecialchars($snippet['description'])); ?></div>
                </div>

                <div class="tags"><strong>Tags :</strong><?php echo htmlspecialchars($snippet['tags'] ?? ''); ?></div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toast notification -->
    <div class="toast" id="toast"></div>

    <script>
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        // render line numbers based on code content
        const codeEl = document.getElementById('codeContent');
        const linesEl = document.getElementById('lineNumbers');
        if(codeEl){
            const text = codeEl.textContent || '';
            const lines = text.split('\n');
            linesEl.innerHTML = lines.map((_, i) => `<div class="line-number">${i + 1}</div>`).join('');
            codeEl.innerHTML = lines.map(line => `<div class="code-line">${line === '' ? '&nbsp;' : line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</div>`).join('');
        }

        // copy button
        const copyBtn = document.getElementById('copyBtn');
        copyBtn?.addEventListener('click', async ()=>{
            const text = codeEl.innerText || codeEl.textContent;
            try{
                await navigator.clipboard.writeText(text);
                const old = copyBtn.innerText;
                copyBtn.innerText = 'Copied';
                setTimeout(()=> copyBtn.innerText = old, 1500);
            }catch(e){
                alert('Copy failed');
            }
        });

        // like button
        const likeBtn = document.getElementById('likeBtn');
        likeBtn?.addEventListener('click', async function() {
            const snippetId = this.dataset.snippetId;
            try {
                const formData = new FormData();
                formData.append('snippet_id', snippetId);

                const response = await fetch('like_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const svg = this.querySelector('svg');
                const countEl = this.querySelector('.like-btn-count');

                if(data.status === 'added') {
                    this.classList.add('liked');
                    svg.setAttribute('fill', '#fff');
                    svg.setAttribute('stroke', '#fff');
                    countEl.textContent = data.count;
                    showToast('👍 Liked!');
                } else if(data.status === 'removed') {
                    this.classList.remove('liked');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#111');
                    countEl.textContent = data.count;
                    showToast('👎 Like removed');
                } else if(data.status === 'error') {
                    showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                }
            } catch(err) {
                showToast('⚠ เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        });

        // bookmark button
        const bookmarkBtn = document.getElementById('bookmarkBtn');
        bookmarkBtn?.addEventListener('click', async function() {
            const snippetId = this.dataset.snippetId;
            try {
                const formData = new FormData();
                formData.append('snippet_id', snippetId);

                const response = await fetch('bookmark_action.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                const svg = this.querySelector('svg');

                if(data.status === 'added') {
                    this.classList.add('bookmarked');
                    svg.setAttribute('fill', '#fff');
                    svg.setAttribute('stroke', '#fff');
                    this.lastChild.textContent = ' Bookmarked';
                    showToast('✓ Bookmarked!');
                } else if(data.status === 'removed') {
                    this.classList.remove('bookmarked');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#111');
                    this.lastChild.textContent = ' Bookmark';
                    showToast('✗ Bookmark removed');
                } else if(data.status === 'error') {
                    showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                }
            } catch(err) {
                showToast('⚠ เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        });
    </script>
</body>
</html>
