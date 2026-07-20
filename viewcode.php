<?php
require_once "layout/session.php";
require_once "db/connect.php";

$snippet = null;
$isBookmarked = false;
$isLiked = false;
$likeCount = 0;
$userId = getCurrentUserId();
$accessDenied = false;

$backUrl = 'snippet_page.php';
if(isset($_GET['from_search']) && $_GET['from_search'] === '1'){
    $backUrl = 'search.php';
    $backParams = [];
    if(isset($_GET['q'])) $backParams['q'] = $_GET['q'];
    if(isset($_GET['lang'])) $backParams['lang'] = $_GET['lang'];
    if(!empty($backParams)){
        $backUrl .= '?' . http_build_query($backParams);
    }
}

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
    <script>
        alert("Please log in.");
        window.location.href = "login.php";
    </script>
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
    <link rel="stylesheet" href="style/viewcodecss.css">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">Back</a>

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
