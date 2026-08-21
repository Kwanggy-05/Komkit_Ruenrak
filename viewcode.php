<?php
require_once "layout/session.php";
require_once "db/connect.php";

$snippet = null;
$isBookmarked = false;
$isLiked = false;
$isReported = false;
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
}elseif(isset($_GET['from_trash']) && $_GET['from_trash'] === '1'){
    $backUrl = 'remove.php';
}

if(isset($_GET['view']) && is_numeric($_GET['view'])){
    $id = (int)$_GET['view'];
    $snippet = $control->getSnippetById($id, $userId, true);
    if($snippet && strtolower((string)($snippet['visibility'] ?? '')) === 'protected' && !$userId){
        $accessDenied = true;
    }
    if($snippet && $userId){
        $isBookmarked = $control->isBookmarked($userId, $id);
        $isLiked = $control->isLiked($userId, $id);
        $isReported = $control->hasUserReported($userId, $id);
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
    <link rel="stylesheet" href="style/viewcodecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">Back</a>

    <?php if(!$snippet): ?>
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 16px;">Snippet not found</div>
    <?php else: ?>
        <div class="form-container">
            <!-- Top Actions Row -->
            <div class="top-actions-row">
                <div class="right-actions">
                    <?php if((int)($snippet['deleted'] ?? 0) !== 1): ?>
                        <!-- Report Button -->
                        <button class="btn report-btn <?php echo $isReported ? 'reported' : ''; ?>" id="reportBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>" <?php echo $isReported ? 'disabled' : ''; ?>>
                            <svg width="14" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                <line x1="4" y1="22" x2="4" y2="15"></line>
                            </svg>
                            <span><?php echo $isReported ? 'Reported' : 'Report'; ?></span>
                        </button>

                        <!-- Bookmark Button -->
                        <button class="btn bookmark-btn <?php echo $isBookmarked ? 'bookmarked' : ''; ?>" id="bookmarkBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                            <svg width="14" height="18" viewBox="0 0 384 512" fill="<?php echo $isBookmarked ? '#fff' : 'none'; ?>" stroke="<?php echo $isBookmarked ? '#fff' : '#111'; ?>" stroke-width="30" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/>
                            </svg>
                            <span><?php echo $isBookmarked ? 'Bookmarked' : 'Bookmark'; ?></span>
                        </button>

                        <!-- Like Button -->
                        <button class="btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" id="likeBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $isLiked ? '#fff' : 'none'; ?>" stroke="<?php echo $isLiked ? '#fff' : '#111'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                            </svg>
                            <span class="like-btn-count"><?php echo $likeCount; ?></span>
                        </button>
                    <?php endif; ?>

                    <!-- Copy Button -->
                    <button class="btn copy" id="copyBtn">Copy</button>
                </div>
            </div>

            <?php if((int)($snippet['deleted'] ?? 0) === 1): ?>
                <div class="trash-notice">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <span>This snippet is in the Trash. You can restore it or delete it permanently from the <a href="remove.php" style="color: inherit; text-decoration: underline; font-weight: 700;">Trash page</a>.</span>
                </div>
            <?php endif; ?>

            <!-- Top Row: Title & Language -->
            <div class="top-row">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($snippet['title']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="language">Language</label>
                    <select name="language_id" class="language-dropdown" disabled>
                        <option selected><?php echo htmlspecialchars($snippet['language_name'] ?? 'Language'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Code Editor Container (Readonly) -->
            <div class="form-group">
                <label for="source_code">Code</label>
                <div class="editor-container">
                    <div class="line-numbers" id="line-numbers">1</div>
                    <textarea name="source_code" id="source_code" readonly wrap="off"><?php echo htmlspecialchars($snippet['source_code']); ?></textarea>
                </div>
            </div>

            <!-- Description (Readonly) -->
            <div class="form-group">
                <label for="description">Description</label>
                <div class="description-view" id="description"><?php echo htmlspecialchars($snippet['description']); ?></div>
            </div>

            <!-- Tags & Visibility Row -->
            <div class="top-row">
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($snippet['tags'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="visibility">Visibility</label>
                    <select name="visibility" class="visibility-dropdown" disabled>
                        <option value="public" <?php echo (($snippet['visibility'] ?? '') === 'public') ? 'selected' : ''; ?>>Public</option>
                        <option value="protected" <?php echo (($snippet['visibility'] ?? '') === 'protected') ? 'selected' : ''; ?>>Protected</option>
                        <option value="private" <?php echo (($snippet['visibility'] ?? '') === 'private') ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
            </div>

            <!-- Action Row -->
            <div class="action-row" style="margin-top: 0;">
                <!-- Left: Show Image directly -->
                <div class="left-actions">
                    <?php if(!empty($snippet['image_path']) && file_exists($snippet['image_path'])): ?>
                        <img id="image-preview" src="<?php echo htmlspecialchars($snippet['image_path']); ?>" alt="Snippet Image" style="display: block;">
                    <?php else: ?>
                        <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">No Image</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Modal -->
    <div class="modal-overlay" id="reportModal">
        <div class="modal-card">
            <header class="modal-header">
                <span class="modal-title">Report Snippet</span>
                <button class="modal-close" id="closeReportModalBtn">&times;</button>
            </header>
            <form id="reportForm">
                <div class="modal-body">
                    <p style="margin-bottom: 16px; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        Please select a reason for reporting this snippet for admin review.
                    </p>
                    
                    <?php if($snippet): ?>
                        <input type="hidden" name="snippet_id" value="<?php echo (int)$snippet['snippet_id']; ?>">
                    <?php endif; ?>
                    
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="spam" required>
                            <span>Spam / Misleading</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="inappropriate">
                            <span>Inappropriate Content / Violent / Harassment</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="malicious_code">
                            <span>Malicious Code / Malware</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="other">
                            <span>Other</span>
                        </label>

                    <div class="report-desc-group" id="reportDescGroup" style="margin-top: 16px;">
                        <label for="reportDetails" style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; color: var(--secondary);">
                            Details <span id="reportReqAsterisk" style="color: #dc3545; display: none;">*</span>
                        </label>
                        <textarea id="reportDetails" name="details" placeholder="Additional details (optional)..." rows="3" style="width:100%; padding: 10px; border: 1px solid var(--input-border); border-radius: var(--radius-sm); font-size: 0.9rem; resize: vertical; outline: none; box-sizing: border-box;"></textarea>
                    </div>
                </div>
                <footer class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid var(--border-color); margin-top: 15px;">
                    <button type="button" class="btn" id="cancelReportBtn" style="border: 1px solid var(--border-color); background: transparent; padding: 8px 16px;">Cancel</button>
                    <button type="submit" class="btn copy" style="background-color: #dc3545; border-color: #dc3545; color: #fff; padding: 8px 16px; font-weight: 600;">Submit</button>
                </footer>
            </form>
        </div>
    </div>

    <!-- Image Viewer Modal (Lightbox) -->
    <div class="modal-overlay" id="imageModal">
        <div class="modal-card">
            <button class="modal-close" id="closeImageModalBtn">&times;</button>
            <img id="modal-image-display" src="" alt="Snippet Image Large">
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

        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');

        // ฟังก์ชันปรับความสูงของช่องใส่โค้ดโดยอัตโนมัติ
        function autoResizeTextarea() {
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            textarea.style.height = scrollHeight + 'px';

            const finalHeight = Math.max(280, scrollHeight);
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        // ฟังก์ชันอัปเดตเลขบรรทัด
        function updateLineNumbers() {
            if (textarea) {
                const lines = textarea.value.split('\n').length;
                lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
            }
        }

        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
        }

        if (textarea) {
            // ซิงค์การเลื่อน Scroll Bar ระหว่างโค้ดกับเลขบรรทัด
            textarea.addEventListener('scroll', () => {
                lineNumbers.scrollTop = textarea.scrollTop;
            });
            updateEditor();
        }

        // copy button
        const copyBtn = document.getElementById('copyBtn');
        copyBtn?.addEventListener('click', async ()=>{
            const text = textarea.value;
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
                    showToast('⚠ ' + (data.message || 'An error occurred'));
                }
            } catch(err) {
                showToast('⚠ Connection error');
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
                const span = this.querySelector('span');

                if(data.status === 'added') {
                    this.classList.add('bookmarked');
                    svg.setAttribute('fill', '#fff');
                    svg.setAttribute('stroke', '#fff');
                    if (span) span.textContent = 'Bookmarked';
                    showToast('✓ Bookmarked!');
                } else if(data.status === 'removed') {
                    this.classList.remove('bookmarked');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#111');
                    if (span) span.textContent = 'Bookmark';
                    showToast('✗ Bookmark removed');
                    showToast('⚠ ' + (data.message || 'An error occurred'));
                }
            } catch(err) {
                showToast('⚠ Connection error');
            }
        });

        // Report Modal functionality
        const reportBtn = document.getElementById('reportBtn');
        const reportModal = document.getElementById('reportModal');
        const closeReportModalBtn = document.getElementById('closeReportModalBtn');
        const cancelReportBtn = document.getElementById('cancelReportBtn');
        const reportForm = document.getElementById('reportForm');
        const reportDetails = document.getElementById('reportDetails');
        const reportDescGroup = document.getElementById('reportDescGroup');
        const reasonRadios = document.querySelectorAll('input[name="reason"]');

        const currentUserId = <?php echo $userId ? (int)$userId : 0; ?>;

        reportBtn?.addEventListener('click', function() {
            if (currentUserId === 0) {
                showToast('⚠ Please log in first to report.');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
                return;
            }
            reportModal.classList.add('open');
        });

        const reportReqAsterisk = document.getElementById('reportReqAsterisk');

        function closeReportModal() {
            reportModal.classList.remove('open');
            reportForm.reset();
            reportDetails.removeAttribute('required');
            reportDetails.placeholder = "Additional details (optional)...";
            if (reportReqAsterisk) reportReqAsterisk.style.display = 'none';
        }

        closeReportModalBtn?.addEventListener('click', closeReportModal);
        cancelReportBtn?.addEventListener('click', closeReportModal);

        // Close when clicking outside the card
        window.addEventListener('click', function(e) {
            if (e.target === reportModal) {
                closeReportModal();
            }
        });

        // Toggle required state when selecting 'other'
        reasonRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'other') {
                    reportDetails.setAttribute('required', 'required');
                    reportDetails.placeholder = "Please specify details for review (required)...";
                    if (reportReqAsterisk) reportReqAsterisk.style.display = 'inline';
                    reportDetails.focus();
                } else {
                    reportDetails.removeAttribute('required');
                    reportDetails.placeholder = "Additional details (optional)...";
                    if (reportReqAsterisk) reportReqAsterisk.style.display = 'none';
                }
            });
        });

        // Handle Report Submission
        reportForm?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            try {
                const response = await fetch('report_action.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showToast('✓ ' + data.message);
                    closeReportModal();
                    // Disable button
                    reportBtn.classList.add('reported');
                    reportBtn.setAttribute('disabled', 'disabled');
                    reportBtn.querySelector('span').textContent = 'Reported';
                } else {
                    showToast('⚠ ' + data.message);
                }
            } catch (err) {
                showToast('⚠ An error occurred while sending data');
            }
        });

        // Image Lightbox Modal functionality
        const imagePreviewEl = document.getElementById('image-preview');
        const imageModal = document.getElementById('imageModal');
        const closeImageModalBtn = document.getElementById('closeImageModalBtn');
        const modalImageDisplay = document.getElementById('modal-image-display');

        imagePreviewEl?.addEventListener('click', function() {
            if (modalImageDisplay) {
                modalImageDisplay.src = this.src;
            }
            imageModal?.classList.add('open');
        });

        function closeImageModal() {
            imageModal?.classList.remove('open');
        }

        closeImageModalBtn?.addEventListener('click', closeImageModal);

        // Close image modal when clicking outside the image
        imageModal?.addEventListener('click', function(e) {
            if (e.target === imageModal) {
                closeImageModal();
            }
        });
    </script>
</body>
</html>
