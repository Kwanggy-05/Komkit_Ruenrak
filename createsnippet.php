<?php
/**
 * ==============================================================================
 * ไฟล์: createsnippet.php
 * หน้าที่: หน้าสร้างและแก้ไข Snippet โค้ด (Create / Edit Snippet)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดการเชื่อมต่อฐานข้อมูลและการตรวจสอบสิทธิ์เข้าใช้งาน
 *   2. ดึงข้อมูลภาษาและแท็กทั้งหมดสำหรับแสดงในตัวเลือก ($control->getLanguage(), getAllTags())
 *   3. จัดการการลบ Snippet ชั่วคราว (Soft Delete via $_GET['delete'])
 *   4. ดึงข้อมูลเดิมมาแสดงเมื่ออยู่ในโหมดแก้ไข (Edit Mode via $_GET['edit'])
 *   5. ประมวลผลการบันทึกข้อมูล (POST Submit): อัปโหลดรูปภาพ, ตรวจความยาวโค้ด <= 5,000 ตัว, แท็ก <= 5 แท็ก
 *   6. ส่วนแสดงผลฟอร์ม HTML (ชื่อเรื่อง, ภาษา, แท็ก, กล่องเขียนโค้ดพร้อมเลขบรรทัด, อัปโหลดรูป, การมองเห็น)
 *   7. สคริปต์ JavaScript สำหรับ Editor, นับตัวอักษร, ปรับขนาดอัตโนมัติ, ระบบแท็กแนะนำ, และ Preview รูป
 * ==============================================================================
 */

require_once "db/connect.php";
require_once "layout/session.php";
require_once "layout/check_user.php";

// ดึงรายการภาษาและแท็กทั้งหมดจากฐานข้อมูล
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
$formTitle = 'สร้าง Snippet';
$formButton = 'บันทึก';
$error = '';

$userId = getCurrentUserId();

// --- ส่วนที่ 2: จัดการการลบแบบ Soft Delete (ย้ายไปถังขยะ) หากมีพารามิเตอร์ ?delete ---
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->softDeleteSnippet((int)$_GET['delete'], $userId);
    header('Location:remove.php');
    exit;
}

// --- ส่วนที่ 3: โหมดแก้ไข Snippet (Edit Mode) เมื่อมีพารามิเตอร์ ?edit ---
if(isset($_GET['edit']) && is_numeric($_GET['edit'])){
    $snippet_id = (int)$_GET['edit'];
    $snippet = $control->getSnippetById($snippet_id, $userId);
    if($snippet){
        // หากโพสต์ถูกระงับโดยแอดมิน จะไม่อนุญาตให้แก้ไข
        if((int)($snippet['is_suspended'] ?? 0) === 1){
            echo "<script>alert('โพสต์นี้ถูกระงับโดยผู้ดูแลระบบ จึงไม่สามารถแก้ไขได้'); window.location.href='snippet_page.php';</script>";
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
        $formTitle = 'แก้ไข Snippet';
        $formButton = 'บันทึกการแก้ไข';
    }
}

// --- ส่วนที่ 4: ประมวลผลการส่งฟอร์มบันทึกข้อมูล (POST Submit) ---
if(isset($_POST["submit"])){
    $snippet_id = isset($_POST['snippet_id']) ? (int)$_POST['snippet_id'] : null;
    $title = $_POST["title"];
    $language_id = $_POST["language_id"];
    $tags = trim($_POST["tags"] ?? '');
    $source_code = str_replace(["\r\n", "\r"], "\n", $_POST["source_code"] ?? '');
    $description = $_POST["description"];
    $visibility = $_POST["visibility"] ?? "private";
    $current_image_path = $_POST['current_image_path'] ?? '';
    $image_path = $current_image_path;

    // จัดการการอัปโหลดไฟล์รูปภาพประกอบ
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

    // ตรวจสอบความถูกต้อง: ความยาวโค้ดไม่เกิน 5,000 ตัวอักษร และแท็กไม่เกิน 5 แท็ก
    $tags_arr = array_filter(array_map('trim', explode(',', $tags)));
    if(mb_strlen($source_code) > 5000){
        $error = "โค้ดต้องมีความยาวไม่เกิน 5,000 ตัวอักษรเท่านั้น (Code cannot exceed 5,000 characters)";
    } else if(count($tags_arr) > 5){
        $error = "คุณสามารถเพิ่มได้สูงสุด 5 tags เท่านั้น";
    } else {
        // หากมีรหัส $snippet_id จะเป็นการอัปเดต ถ้าไม่มีจะเป็นการเพิ่มใหม่
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
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($formTitle); ?> - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/createsnippetcss.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <a href="snippet_page.php" class="back-btn">ย้อนกลับ</a>
    
    <h1><?php echo htmlspecialchars($formTitle); ?></h1>
    
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
                <label for="title">ชื่อเรื่อง</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>">
            </div>

            <div class="form-group">
                <label for="language">ภาษา</label>
                <select name="language_id" class="language-dropdown">
                    <?php while($row=$result->fetch(PDO::FETCH_ASSOC)){ ?>
                        <option value="<?php echo $row["language_id"]; ?>" <?php echo ($row["language_id"] == $language_id) ? 'selected' : ''; ?>><?php echo $row["language_name"]; ?></option>
                    <?php } ?>    
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="code-label-wrapper">
                <label for="source_code">โค้ด</label>
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
            <label for="description">คำอธิบาย</label>
            <textarea name="description" id="description"><?php echo htmlspecialchars($description); ?></textarea>
        </div>

        <div class="form-group">
            <label for="tags">Tags</label>
            <input type="text" name="tags" id="tags" placeholder="เช่น JavaScript, ajax, DOM" autocomplete="off" value="<?php echo htmlspecialchars($tags); ?>">
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
                    <span id="upload-btn-text"><?php echo !empty($current_image_path) ? 'เปลี่ยนรูปภาพ' : 'อัปโหลดรูปภาพ'; ?></span>
                    <input type="file" name="image_path" id="image_input" accept="image/*" style="display: none;">
                </label>
                
                <img id="image-preview" src="<?php echo htmlspecialchars($current_image_path); ?>" alt="Image Preview" style="<?php echo !empty($current_image_path) ? 'display: block;' : ''; ?>">
            </div>

            <div class="right-actions" style="align-items: flex-end;">
                <div class="form-group">
                    <label for="visibility">การมองเห็น</label>
                    <select name="visibility" id="visibility" class="visibility-dropdown">
                        <option value="public" <?php echo ($visibility === 'public') ? 'selected' : ''; ?>>สาธารณะ (Public)</option>
                        <option value="protected" <?php echo ($visibility === 'protected') ? 'selected' : ''; ?>>เฉพาะสมาชิก (Member)</option>
                        <option value="private" <?php echo ($visibility === 'private') ? 'selected' : ''; ?>>ส่วนตัว (Private)</option>
                    </select>
                </div>
                
                <input type="submit" name="submit" value="<?php echo htmlspecialchars($formButton); ?>" >
            </div>

        </div>
    </form>

    <script>
        // --- ส่วนที่ 7: สคริปต์ JavaScript ควบคุม Editor และการทำงานในฟอร์ม ---
        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');
        const charCountEl = document.getElementById('char-count');
        const charCounterEl = document.getElementById('char-counter');
        const charWarningEl = document.getElementById('char-warning');
        const charWarningText = document.getElementById('char-warning-text');

        /**
         * ฟังก์ชัน: updateCharCount
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: จัดการตัวนับจำนวนตัวอักษร]
         * หน้าที่: นับจำนวนตัวอักษรของโค้ดใน Textarea และแสดงผล พร้อมแจ้งเตือนเมื่อเกินหรือถึง 5,000 ตัว
         */
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

        /**
         * ฟังก์ชัน: autoResizeTextarea
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ปรับขนาดกล่องโค้ดอัตโนมัติ]
         * หน้าที่: ขยายความสูงของ Textarea และ Editor Container ให้พอดีกับปริมาณโค้ดโดยไม่ต้องเลื่อนสกอร์บาร์
         */
        function autoResizeTextarea() {
            // ป้องกันการค้างของตำแหน่ง scroll
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            textarea.style.height = scrollHeight + 'px';

            // กำหนดความสูงของคอนเทนเนอร์ให้ตามความยาวโค้ด ขั้นต่ำ 280px
            const finalHeight = Math.max(280, scrollHeight);
            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        /**
         * ฟังก์ชัน: updateLineNumbers
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: เลขบรรทัดโค้ด (Line Numbers)]
         * หน้าที่: คำนวณจำนวนบรรทัดของโค้ด และเรนเดอร์ตัวเลข 1, 2, 3... ในฝั่งซ้ายของ Editor
         */
        function updateLineNumbers() {
            const lines = textarea.value.split('\n').length;
            lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
        }

        /**
         * ฟังก์ชัน: updateEditor
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: อัปเดตสถานะ Editor โดยรวม]
         * หน้าที่: เรียกทำงานฟังก์ชัน updateLineNumbers, autoResizeTextarea และ updateCharCount พร้อมกัน
         */
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
                uploadBtnText.textContent = 'เปลี่ยนรูปภาพ';
            } else {
                // If user cancelled file selection
                if (currentImagePath) {
                    imagePreview.src = currentImagePath;
                    imagePreview.style.display = 'block';
                    uploadBtnText.textContent = 'เปลี่ยนรูปภาพ';
                } else {
                    imagePreview.src = '';
                    imagePreview.style.display = 'none'; // Hide preview image
                    uploadBtnText.textContent = 'อัปโหลดรูปภาพ';
                }
            }
        });

        // ----------------------------------------------------
        // Suggest Tags from database
        // ----------------------------------------------------
        const tagsInput = document.getElementById('tags');
        const tagsSelector = document.getElementById('tags-selector');
        const allTags = <?php echo json_encode($all_tags); ?> || [];

        /**
         * ฟังก์ชัน: renderTagBadges
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ระบบแนะนำแท็ก (Tag Badges)]
         * หน้าที่: สร้าง element ป้ายแท็ก (<span class="tag-badge">) ตามรายการแท็กที่มีในฐานข้อมูล
         */
        function renderTagBadges() {
            tagsSelector.innerHTML = '';
            allTags.forEach(tag => {
                const badge = document.createElement('span');
                badge.className = 'tag-badge';
                badge.textContent = tag.tag_name;
                badge.dataset.tagName = tag.tag_name;
                
                badge.addEventListener('mousedown', (e) => {
                    // ใช้ mousedown เพื่อป้องกันการ blur หลุดก่อนคลิกทำงาน
                    e.preventDefault();
                    toggleTagSelection(tag.tag_name);
                });
                
                tagsSelector.appendChild(badge);
            });
            updateBadgeSelectionState();
        }

        /**
         * ฟังก์ชัน: getSelectedTagsFromInput
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ระบบแนะนำแท็ก (Tag Badges)]
         * หน้าที่: อ่านค่าจากช่องกรอก tags แล้วแยกเป็น Array ของชื่อแท็ก
         * คืนค่า: Array ของชื่อแท็กที่เลือกอยู่
         */
        function getSelectedTagsFromInput() {
            return tagsInput.value
                .split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0);
        }

        /**
         * ฟังก์ชัน: toggleTagSelection
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ระบบแนะนำแท็ก (Tag Badges)]
         * หน้าที่: สลับการเลือกแท็ก (หากเลือกอยู่แล้วให้เอาออก หากยังไม่เลือกให้ใส่เพิ่ม พร้อมเช็คจำกัดไม่เกิน 5 แท็ก)
         * พารามิเตอร์: tagName (ชื่อแท็ก)
         */
        function toggleTagSelection(tagName) {
            let currentTags = getSelectedTagsFromInput();
            const index = currentTags.findIndex(t => t.toLowerCase() === tagName.toLowerCase());
            
            if (index > -1) {
                // หากมีอยู่แล้ว ให้ลบออก
                currentTags.splice(index, 1);
                tagsInput.value = currentTags.join(', ');
                if (tagsInput.value.length > 0 && !tagsInput.value.endsWith(', ')) {
                    tagsInput.value += ', ';
                }
            } else {
                // หากยังไม่มี ให้เพิ่มเข้าไป (จำกัดไม่เกิน 5 tags)
                if (currentTags.length >= 5) {
                    alert('คุณสามารถเพิ่มได้สูงสุด 5 tags เท่านั้น');
                    return;
                }
                const parts = tagsInput.value.split(',');
                const lastPart = parts[parts.length - 1].trim();
                if (lastPart.length > 0 && tagName.toLowerCase().startsWith(lastPart.toLowerCase())) {
                    parts[parts.length - 1] = ' ' + tagName;
                    tagsInput.value = parts.join(',').trim();
                } else {
                    currentTags.push(tagName);
                    tagsInput.value = currentTags.join(', ');
                }
                
                if (!tagsInput.value.endsWith(', ') && tagsInput.value.length > 0) {
                    tagsInput.value += ', ';
                }
            }
            
            updateBadgeSelectionState();
            filterTagBadges();
            tagsInput.focus();
        }

        /**
         * ฟังก์ชัน: updateBadgeSelectionState
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ระบบแนะนำแท็ก (Tag Badges)]
         * หน้าที่: ตรวจสอบและเพิ่ม/ลบ class 'selected' บนป้ายแท็ก เพื่อแสดงสีไฮไลต์ตามแท็กที่พิมพ์ในช่อง
         */
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

        /**
         * ฟังก์ชัน: filterTagBadges
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ระบบแนะนำแท็ก (Tag Badges)]
         * หน้าที่: กรองป้ายแท็กให้แสดงเฉพาะแท็กที่ตรงกับคำที่ผู้ใช้กำลังพิมพ์อยู่หลังเครื่องหมายจุลภาค (Comma)
         */
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
                alert('คุณสามารถเพิ่มได้สูงสุด 5 tags เท่านั้น');
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