<?php
require_once __DIR__ . '/check_admin.php';

$snippet = null;

$backUrl = 'manage_snippets.php';

if(isset($_GET['view']) && is_numeric($_GET['view'])){
    $id = (int)$_GET['view'];
    $snippet = $control->getSnippetByIdAdmin($id);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($snippet['title'] ?? 'View Snippet - Admin'); ?></title>
    <link rel="stylesheet" href="../style/viewcodecss.css?v=<?php echo time(); ?>">
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
                    <!-- Copy Button -->
                    <button class="btn copy" id="copyBtn">Copy</button>
                </div>
            </div>

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
                    <?php if(!empty($snippet['image_path']) && file_exists('../' . $snippet['image_path'])): ?>
                        <img id="image-preview" src="<?php echo '../' . htmlspecialchars($snippet['image_path']); ?>" alt="Snippet Image" style="display: block;">
                    <?php else: ?>
                        <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">No Image</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

        // Auto-resize textarea height
        function autoResizeTextarea() {
            if (!textarea || !lineNumbers) return;
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

        // Update line numbers
        function updateLineNumbers() {
            if (textarea && lineNumbers) {
                const lines = textarea.value.split('\n').length;
                lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
            }
        }

        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
        }

        if (textarea) {
            // Sync scroll between code and line numbers
            textarea.addEventListener('scroll', () => {
                if (lineNumbers) lineNumbers.scrollTop = textarea.scrollTop;
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
