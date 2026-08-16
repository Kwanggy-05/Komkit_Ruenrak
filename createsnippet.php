<?php
require_once "db/connect.php";
require_once "layout/session.php";
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

if(!$userId){
    header('Location:login.php');
    exit;
}

if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->softDeleteSnippet((int)$_GET['delete'], $userId);
    header('Location:remove.php');
    exit;
}

if(isset($_GET['edit']) && is_numeric($_GET['edit'])){
    $snippet_id = (int)$_GET['edit'];
    $snippet = $control->getSnippetById($snippet_id, $userId);
    if($snippet){
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
    $language_id = $_POST["language_id"]; // เพิ่มส่วนการรับค่า Language
    $tags = trim($_POST["tags"] ?? '');
    $source_code = $_POST["source_code"];
    $description = $_POST["description"];
    $visibility = $_POST["visibility"] ?? "private";
    $current_image_path = $_POST['current_image_path'] ?? '';
    $image_path = $current_image_path;

    // 2. ตรวจสอบว่ามีการอัปโหลดไฟล์มาหรือไม่ และไม่มี error
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
    if(count($tags_arr) > 5){
        $error = "คุณสามารถใส่แท็กได้ไม่เกิน 5 แท็ก";
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
    
    <h1>สร้าง Snippet Box</h1>
    
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
            <label for="source_code">Code</label>
            <div class="editor-container">
                <div class="line-numbers" id="line-numbers">1</div>
                <textarea name="source_code" id="source_code" wrap="off"><?php echo htmlspecialchars($source_code); ?></textarea>
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

            <div class="right-actions">
                <select name="visibility" class="visibility-dropdown">
                    <option value="public" <?php echo ($visibility === 'public') ? 'selected' : ''; ?>>Public</option>
                    <option value="protected" <?php echo ($visibility === 'protected') ? 'selected' : ''; ?>>Protected</option>
                    <option value="private" <?php echo ($visibility === 'private') ? 'selected' : ''; ?>>Private</option>
                </select>
                
                <input type="submit" name="submit" value="<?php echo htmlspecialchars($formButton); ?>" >
            </div>

        </div>
    </form>

    <script>
        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');

        // ฟังก์ชันปรับความสูงของช่องใส่โค้ดโดยอัตโนมัติ
        function autoResizeTextarea() {
            // ป้องกันการค้างของตำแหน่ง Scroll
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            textarea.style.height = scrollHeight + 'px';

            // กำหนดความสูงให้กล่องครอบตัวนอก (Editor Container) ให้ยาวตามโค้ด
            // โดยมีความสูงขั้นต่ำ 280px เพื่อป้องกันปัญหา CSS Cache
            const finalHeight = Math.max(280, scrollHeight);
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        // ฟังก์ชันอัปเดตเลขบรรทัด
        function updateLineNumbers() {
            const lines = textarea.value.split('\n').length;
            lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
        }

        // ฟังก์ชันอัปเดตทั้งเลขบรรทัดและความสูงของช่องใส่โค้ด
        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
        }

        // ตรวจจับการพิมพ์หรือการวางโค้ด
        textarea.addEventListener('input', updateEditor);

        // ซิงค์การเลื่อน Scroll Bar ระหว่างโค้ดกับเลขบรรทัด
        textarea.addEventListener('scroll', () => {
            lineNumbers.scrollTop = textarea.scrollTop;
        });

        // ทำให้สามารถกด Tab ได้ในช่องเขียนโค้ด (ไม่กระโดดไปช่องอื่น)
        textarea.addEventListener('keydown', function(e) {
            if (e.key == 'Tab') {
                e.preventDefault();
                var start = this.selectionStart;
                var end = this.selectionEnd;
                this.value = this.value.substring(0, start) + "    " + this.value.substring(end);
                this.selectionStart = this.selectionEnd = start + 4;
                updateEditor();
            }
        });

        // กำหนดค่าเริ่มต้นสำหรับเลขบรรทัดและความสูง
        updateEditor();

        // ----------------------------------------------------
        // สคริปต์สำหรับแสดงภาพตัวอย่าง (Image Preview)
        // ----------------------------------------------------
        const imageInput = document.getElementById('image_input');
        const imagePreview = document.getElementById('image-preview');
        const uploadBtnText = document.getElementById('upload-btn-text');
        const currentImagePath = <?php echo json_encode($current_image_path); ?>;

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0]; // ดึงไฟล์แรกที่ผู้ใช้เลือก
            
            if (file) {
                // สร้าง URL ชั่วคราวจากไฟล์ที่อยู่ในเครื่อง แล้วยัดใส่ src ของ img
                imagePreview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block'; // เปลี่ยนจากซ่อนเป็นแสดงรูปภาพ
                uploadBtnText.textContent = 'Update Image';
            } else {
                // ถ้าผู้ใช้กดยกเลิกการเลือกไฟล์
                if (currentImagePath) {
                    imagePreview.src = currentImagePath;
                    imagePreview.style.display = 'block';
                    uploadBtnText.textContent = 'Update Image';
                } else {
                    imagePreview.src = '';
                    imagePreview.style.display = 'none'; // ซ่อนรูปภาพกลับไปเหมือนเดิม
                    uploadBtnText.textContent = 'Upload Image';
                }
            }
        });

        // ----------------------------------------------------
        // สคริปต์สำหรับแนะนำ Tags จากฐานข้อมูล
        // ----------------------------------------------------
        const tagsInput = document.getElementById('tags');
        const tagsSelector = document.getElementById('tags-selector');
        const allTags = <?php echo json_encode($all_tags); ?> || [];

        // ฟังก์ชันสร้างและเรนเดอร์ Tag Badges
        function renderTagBadges() {
            tagsSelector.innerHTML = '';
            allTags.forEach(tag => {
                const badge = document.createElement('span');
                badge.className = 'tag-badge';
                badge.textContent = tag.tag_name;
                badge.dataset.tagName = tag.tag_name;
                
                badge.addEventListener('mousedown', (e) => {
                    // ใช้ mousedown เพื่อชิงโฟกัสไม่ให้เกิด blur ก่อนคลิกทำงาน
                    e.preventDefault();
                    toggleTagSelection(tag.tag_name);
                });
                
                tagsSelector.appendChild(badge);
            });
            updateBadgeSelectionState();
        }

        // ดึง Tags ปัจจุบันจากช่อง Input ออกมาเป็น Array
        function getSelectedTagsFromInput() {
            return tagsInput.value
                .split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0);
        }

        // เลือกหรือสลับการเลือก Tag
        function toggleTagSelection(tagName) {
            let currentTags = getSelectedTagsFromInput();
            const index = currentTags.findIndex(t => t.toLowerCase() === tagName.toLowerCase());
            
            if (index > -1) {
                // ถ้ามีอยู่อยู่แล้ว ให้ลบออก
                currentTags.splice(index, 1);
                tagsInput.value = currentTags.join(', ');
                if (tagsInput.value.length > 0 && !tagsInput.value.endsWith(', ')) {
                    tagsInput.value += ', ';
                }
            } else {
                // ถ้ายังไม่มี ให้ใส่เพิ่ม
                if (currentTags.length >= 5) {
                    alert('คุณสามารถใส่แท็กได้ไม่เกิน 5 แท็ก');
                    return;
                }
                // เช็คคำค้นหาก่อนคอมม่าสุดท้าย ถ้าผู้ใช้กำลังพิมพ์อยู่ให้แทนที่คำนั้นเลย
                const parts = tagsInput.value.split(',');
                const lastPart = parts[parts.length - 1].trim();
                if (lastPart.length > 0 && tagName.toLowerCase().startsWith(lastPart.toLowerCase())) {
                    parts[parts.length - 1] = ' ' + tagName;
                    tagsInput.value = parts.join(',').trim();
                } else {
                    currentTags.push(tagName);
                    tagsInput.value = currentTags.join(', ');
                }
                
                // ใส่คอมม่าและเว้นวรรคเตรียมไว้สำหรับแท็กถัดไป
                if (!tagsInput.value.endsWith(', ') && tagsInput.value.length > 0) {
                    tagsInput.value += ', ';
                }
            }
            
            updateBadgeSelectionState();
            filterTagBadges();
            tagsInput.focus();
        }

        // อัปเดตสถานะ Active/Selected ของ Badges
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

        // กรองการแสดงผลของ Tag ตามคำที่พิมพ์
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

        // จัดการ Event Listeners
        tagsInput.addEventListener('focus', () => {
            tagsSelector.classList.add('active');
            updateBadgeSelectionState();
            filterTagBadges();
        });

        tagsInput.addEventListener('input', () => {
            updateBadgeSelectionState();
            filterTagBadges();
        });

        // ตรวจสอบตอนส่งฟอร์มเพื่อป้องกันการใส่เกิน 5 แท็ก
        document.querySelector('.form-container').addEventListener('submit', function(e) {
            const currentTags = getSelectedTagsFromInput();
            if (currentTags.length > 5) {
                e.preventDefault();
                alert('คุณสามารถใส่แท็กได้ไม่เกิน 5 แท็ก');
                tagsInput.focus();
            }
        });

        // ซ่อนแท็กเมื่อคลิกข้างนอก
        document.addEventListener('click', (e) => {
            if (!tagsInput.contains(e.target) && !tagsSelector.contains(e.target)) {
                tagsSelector.classList.remove('active');
            }
        });

        // เริ่มต้นการสร้าง Badges
        renderTagBadges();
    </script>
</body>
</html>