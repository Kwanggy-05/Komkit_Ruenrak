<?php
require_once "db/connect.php";
require_once "layout/session.php";
require_once "layout/check_user.php";
$result = $control->getLanguage();
$all_tags = $control->getAllTags();

$snippet_id = null;
$title = '';
$language_id = '';
$tags = '';
$source_code = '';
$description = '';
$visibility = 'private';
$image_path = '';
$current_image_path = '';
$formTitle = 'Create Snippet';
$formButton = 'Save';
$error = '';

$userId = getCurrentUserId();

if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->softDeleteSnippet((int)$_GET['delete'], $userId);
    header('Location:remove.php');
    exit;
}

if(isset($_GET['edit']) && is_numeric($_GET['edit'])){
    $snippet_id = (int)$_GET['edit'];
    $snippet = $control->getSnippetById($snippet_id, $userId);
    if($snippet){
        if((int)($snippet['is_suspended'] ?? 0) === 1){
            echo "<script>alert('This snippet is suspended by an administrator and cannot be edited.'); window.location.href='snippet_page.php';</script>";
            exit;
        }
        $title = $snippet['title'];
        $language_id = $snippet['language_id'];
        $tags = $snippet['tags'] ?? '';
        $source_code = $snippet['source_code'];
        $description = $snippet['description'];
        $visibility = $snippet['visibility'];
        $image_path = $snippet['image_path'];
        $current_image_path = $snippet['image_path'];
        $formTitle = 'Edit Snippet';
        $formButton = 'Update';
    }
}

if(isset($_POST["submit"])){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : null;
    $title = $_POST["title"];
    $language_id = $_POST["language_id"]; // Language ID input
    $tags = trim($_POST["tags"] ?? '');
    $source_code = str_replace(["\r\n", "\r"], "\n", $_POST["source_code"] ?? '');
    $description = $_POST["description"];
    $visibility = $_POST["visibility"] ?? "private";
    $current_image_path = $_POST['current_image_path'] ?? '';
    $image_path = $current_image_path;

    // 2. Check if file is uploaded and has no error
    if(isset($_FILES["image_path"]) && $_FILES["image_path"]["error"] == 0){
        
        $upload_dir = "uploads/"; 
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES["image_path"]["name"]);
        $new_file_name = time() . "_" . $file_name; 
        $target_file = $upload_dir . $new_file_name;

        if(move_uploaded_file($_FILES["image_path"]["tmp_name"], $target_file)){
            $image_path = $target_file; 
        }
    }

    $tags_arr = array_filter(array_map('trim', explode(',', $tags)));
    if(mb_strlen($source_code) > 5000){
        $error = "โค้ดต้องมีความยาวไม่เกิน 5,000 ตัวอักษรเท่านั้น (Code cannot exceed 5,000 characters)";
    } else if(count($tags_arr) > 5){
        $error = "You can only add up to 5 tags";
    } else {
        if($snippet_id){
            $status = $control->updateSnippet($snippet_id,$title,$source_code,$description,$visibility,$image_path,$language_id,$tags, $userId);
        } else {
            $status = $control->insert($title,$source_code,$description,$visibility,$image_path,$language_id,$tags, $userId);
        }

        header("Location:snippet_page.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Snippet</title>
    <link rel="stylesheet" href="style/createsnippetcss.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <a href="snippet_page.php" class="back-btn">Back</a>
    
    <h1>Create Snippet Box</h1>
    
    <form method="POST" action="createsnippet.php" class="form-container" enctype="multipart/form-data">
        <input type="hidden" name="snippet_id" value="<?php echo htmlspecialchars($snippet_id); ?>">
        <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($current_image_path); ?>">
        
        <?php if(!empty($error)): ?>
            <div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 16px; border-radius: 8px; font-size: 14px; text-align: center; width: 100%;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="top-row">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>">
            </div>

            <div class="form-group">
                <label for="language">Language</label>
                <select name="language_id" class="language-dropdown">
                    <?php while($row=$result->fetch(PDO::FETCH_ASSOC)){ ?>
                        <option value="<?php echo $row["language_id"]; ?>" <?php echo ($row["language_id"] == $language_id) ? 'selected' : ''; ?>><?php echo $row["language_name"]; ?></option>
                    <?php } ?>    
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="code-label-wrapper">
                <label for="source_code">Code <span class="code-limit-info">(ใส่ได้ไม่เกิน 5,000 ตัวอักษร)</span></label>
                <span class="char-counter" id="char-counter"><span id="char-count">0</span> / 5,000</span>
            </div>
            <div class="editor-container">
                <div class="line-numbers" id="line-numbers">1</div>
                <textarea name="source_code" id="source_code" wrap="off" maxlength="5000" placeholder="ใส่โค้ดที่นี่ (ไม่เกิน 5,000 ตัวอักษร)..."><?php echo htmlspecialchars($source_code); ?></textarea>
            </div>
            <div id="char-warning" class="code-char-warning" style="display: none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px; flex-shrink: 0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span id="char-warning-text">คุณใส่ข้อความครบ 5,000 ตัวอักษรแล้ว ไม่สามารถใส่เพิ่มได้</span>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea name="description" id="description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">
            <label for="tags">Tags</label>
            <input type="text" name="tags" id="tags" placeholder="e.g. JavaScript, ajax, DOM" autocomplete="off" value="<?php echo htmlspecialchars($tags); ?>">
            <div class="tags-selector" id="tags-selector"></div>
        </div>

        <div class="action-row">
            
            <div class="left-actions">
                <label class="upload-btn">
                    <div class="upload-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="19" x2="12" y2="5"></line>
                            <polyline points="5 12 12 5 19 12"></polyline>
                        </svg>
                    </div>
                    <span id="upload-btn-text"><?php echo !empty($current_image_path) ? 'Update Image' : 'Upload Image'; ?></span>
                    <input type="file" name="image_path" id="image_input" accept="image/*" style="display: none;">
                </label>
                
                <img id="image-preview" src="<?php echo htmlspecialchars($current_image_path); ?>" alt="Image Preview" style="<?php echo !empty($current_image_path) ? 'display: block;' : ''; ?>">
            </div>

            <div class="right-actions" style="align-items: flex-end;">
                <div class="form-group">
                    <label for="visibility">Visibility</label>
                    <select name="visibility" id="visibility" class="visibility-dropdown">
                        <option value="public" <?php echo ($visibility === 'public') ? 'selected' : ''; ?>>Public</option>
                        <option value="protected" <?php echo ($visibility === 'protected') ? 'selected' : ''; ?>>Protected</option>
                        <option value="private" <?php echo ($visibility === 'private') ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
                
                <input type="submit" name="submit" value="<?php echo htmlspecialchars($formButton); ?>" >
            </div>

        </div>
    </form>

    <script>
        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');
        const charCountEl = document.getElementById('char-count');
        const charCounterEl = document.getElementById('char-counter');
        const charWarningEl = document.getElementById('char-warning');
        const charWarningText = document.getElementById('char-warning-text');

        // Update character count and warning display
        function updateCharCount() {
            const count = textarea.value.length;
            if (charCountEl) {
                charCountEl.textContent = count.toLocaleString();
            }
            if (count >= 5000) {
                if (charCounterEl) charCounterEl.classList.add('limit-reached');
                if (charWarningEl) {
                    charWarningEl.style.display = 'flex';
                    if (charWarningText) {
                        charWarningText.textContent = 'คุณใส่ข้อความครบ 5,000 ตัวอักษรแล้ว ไม่สามารถใส่เพิ่มได้ (ใส่ได้สูงสุด 5,000 ตัวอักษรเท่านั้น)';
                    }
                }
            } else {
                if (charCounterEl) charCounterEl.classList.remove('limit-reached');
                if (charWarningEl) charWarningEl.style.display = 'none';
            }
        }

        // Auto-resize textarea height
        function autoResizeTextarea() {
            // Prevent scroll position freezing
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            textarea.style.height = scrollHeight + 'px';

            // Set editor container height to follow code length
            // Minimum height 280px to avoid CSS cache issues
            const finalHeight = Math.max(280, scrollHeight);
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        // Update line numbers
        function updateLineNumbers() {
            const lines = textarea.value.split('\n').length;
            lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
        }

        // Update line numbers, textarea height, and character count
        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
            updateCharCount();
        }

        // Detect typing or pasting code
        textarea.addEventListener('input', updateEditor);

        // Detect paste action exceeding 5000 characters
        textarea.addEventListener('paste', function(e) {
            const pasteData = (e.clipboardData || window.clipboardData)?.getData('text') || '';
            const currentLen = this.value.length;
            const selectedLen = this.selectionEnd - this.selectionStart;
            if (currentLen - selectedLen + pasteData.length > 5000) {
                if (charWarningEl) {
                    charWarningEl.style.display = 'flex';
                    if (charWarningText) {
                        charWarningText.textContent = 'ข้อความที่วางเกินขีดจำกัด 5,000 ตัวอักษร ระบบได้ตัดข้อความส่วนเกินออก (ใส่ได้สูงสุด 5,000 ตัวอักษรเท่านั้น)';
                    }
                }
            }
        });

        // Sync scroll between code and line numbers
        textarea.addEventListener('scroll', () => {
            lineNumbers.scrollTop = textarea.scrollTop;
        });

        // Enable Tab key support in code textarea with 5000 character check
        textarea.addEventListener('keydown', function(e) {
            if (e.key == 'Tab') {
                e.preventDefault();
                var start = this.selectionStart;
                var end = this.selectionEnd;
                var currentLen = this.value.length;
                var selectedLen = end - start;
                if (currentLen - selectedLen + 4 > 5000) {
                    if (charWarningEl) {
                        charWarningEl.style.display = 'flex';
                        if (charWarningText) {
                            charWarningText.textContent = 'ไม่สามารถแทรก Tab ได้ เนื่องจากจะเกินขีดจำกัด 5,000 ตัวอักษร (ใส่ได้สูงสุด 5,000 ตัวอักษรเท่านั้น)';
                        }
                    }
                    alert('ใส่โค้ดได้สูงสุด 5,000 ตัวอักษรเท่านั้น');
                    return;
                }
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
                updateEditor();
            }
        });

        // Initialize line numbers and height
        updateEditor();

        // ----------------------------------------------------
        // Image Preview Script
        // ----------------------------------------------------
        const imageInput = document.getElementById('image_input');
        const imagePreview = document.getElementById('image-preview');
        const uploadBtnText = document.getElementById('upload-btn-text');
        const currentImagePath = <?php echo json_encode($current_image_path); ?>;

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0]; // Get the first selected file
            
            if (file) {
                // Create temporary URL from file and assign to img src
                imagePreview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block'; // Show preview image
                uploadBtnText.textContent = 'Update Image';
            } else {
                // If user cancelled file selection
                if (currentImagePath) {
                    imagePreview.src = currentImagePath;
                    imagePreview.style.display = 'block';
                    uploadBtnText.textContent = 'Update Image';
                } else {
                    imagePreview.src = '';
                    imagePreview.style.display = 'none'; // Hide preview image
                    uploadBtnText.textContent = 'Upload Image';
                }
            }
        });

        // ----------------------------------------------------
        // Suggest Tags from database
        // ----------------------------------------------------
        const tagsInput = document.getElementById('tags');
        const tagsSelector = document.getElementById('tags-selector');
        const allTags = <?php echo json_encode($all_tags); ?> || [];

        // Function to create and render Tag Badges
        function renderTagBadges() {
            tagsSelector.innerHTML = '';
            allTags.forEach(tag => {
                const badge = document.createElement('span');
                badge.className = 'tag-badge';
                badge.textContent = tag.tag_name;
                badge.dataset.tagName = tag.tag_name;
                
                badge.addEventListener('mousedown', (e) => {
                    // Use mousedown to prevent blur before click activates
                    e.preventDefault();
                    toggleTagSelection(tag.tag_name);
                });
                
                tagsSelector.appendChild(badge);
            });
            updateBadgeSelectionState();
        }

        // Get current tags from input as array
        function getSelectedTagsFromInput() {
            return tagsInput.value
                .split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0);
        }

        // Select or toggle tag selection
        function toggleTagSelection(tagName) {
            let currentTags = getSelectedTagsFromInput();
            const index = currentTags.findIndex(t => t.toLowerCase() === tagName.toLowerCase());
            
            if (index > -1) {
                // If exists, remove it
                currentTags.splice(index, 1);
                tagsInput.value = currentTags.join(', ');
                if (tagsInput.value.length > 0 && !tagsInput.value.endsWith(', ')) {
                    tagsInput.value += ', ';
                }
            } else {
                // If not, add it
                if (currentTags.length >= 5) {
                    alert('You can only add up to 5 tags');
                    return;
                }
                // Check query before last comma to replace if user is typing
                const parts = tagsInput.value.split(',');
                const lastPart = parts[parts.length - 1].trim();
                if (lastPart.length > 0 && tagName.toLowerCase().startsWith(lastPart.toLowerCase())) {
                    parts[parts.length - 1] = ' ' + tagName;
                    tagsInput.value = parts.join(',').trim();
                } else {
                    currentTags.push(tagName);
                    tagsInput.value = currentTags.join(', ');
                }
                
                // Add comma and space for next tag
                if (!tagsInput.value.endsWith(', ') && tagsInput.value.length > 0) {
                    tagsInput.value += ', ';
                }
            }
            
            updateBadgeSelectionState();
            filterTagBadges();
            tagsInput.focus();
        }

        // Update Active/Selected status of Badges
        function updateBadgeSelectionState() {
            const currentTags = getSelectedTagsFromInput().map(t => t.toLowerCase());
            const badges = tagsSelector.querySelectorAll('.tag-badge');
            badges.forEach(badge => {
                const name = badge.dataset.tagName.toLowerCase();
                if (currentTags.includes(name)) {
                    badge.classList.add('selected');
                } else {
                    badge.classList.remove('selected');
                }
            });
        }

        // Filter tag badges based on typed text
        function filterTagBadges() {
            const parts = tagsInput.value.split(',');
            const currentQuery = parts[parts.length - 1].trim().toLowerCase();
            const badges = tagsSelector.querySelectorAll('.tag-badge');
            
            badges.forEach(badge => {
                const name = badge.dataset.tagName.toLowerCase();
                if (currentQuery.length === 0 || name.includes(currentQuery)) {
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            });
        }

        // Manage Event Listeners
        tagsInput.addEventListener('focus', () => {
            tagsSelector.classList.add('active');
            updateBadgeSelectionState();
            filterTagBadges();
        });

        tagsInput.addEventListener('input', () => {
            updateBadgeSelectionState();
            filterTagBadges();
        });

        // Check on form submission to prevent exceeding code limit and tags limit
        document.querySelector('.form-container').addEventListener('submit', function(e) {
            if (textarea.value.length > 5000) {
                e.preventDefault();
                alert('ใส่โค้ดได้สูงสุด 5,000 ตัวอักษรเท่านั้น');
                textarea.focus();
                return;
            }
            const currentTags = getSelectedTagsFromInput();
            if (currentTags.length > 5) {
                e.preventDefault();
                alert('You can only add up to 5 tags');
                tagsInput.focus();
            }
        });

        // Hide tags on outside click
        document.addEventListener('click', (e) => {
            if (!tagsInput.contains(e.target) && !tagsSelector.contains(e.target)) {
                tagsSelector.classList.remove('active');
            }
        });

        // Initialize badges
        renderTagBadges();
    </script>
</body>
</html>