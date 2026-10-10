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

        <div class="form-group tag-form-group">
            <div class="tags-label-wrapper">
                <label for="tag_text_input">Tags</label>
                <span class="tags-count-info" id="tags-count-info"><span id="tags-count">0</span> / 5</span>
            </div>
            
            <input type="hidden" name="tags" id="tags" value="<?php echo htmlspecialchars($tags); ?>">

            <div class="tags-control-container">
                <div class="tags-input-box" id="tags-input-box">
                    <div class="tag-pills-list" id="tag-pills-list"></div>
                    <input type="text" id="tag_text_input" class="tag-text-input" placeholder="พิมพ์เพื่อค้นหาหรือเพิ่มแท็ก (เช่น JavaScript, regex...)" autocomplete="off">
                </div>

                <div class="tags-autocomplete-dropdown" id="tags-autocomplete-dropdown"></div>
            </div>

            <div id="tags-limit-msg" class="tags-limit-msg" style="display: none;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 6px; flex-shrink: 0;">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>เลือกครบ 5 แท็กแล้ว</span>
            </div>
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
         * หน้าที่: ปรับความสูงของ Textarea และ Editor Container ให้ยืดและหดตามปริมาณโค้ด
         */
        function autoResizeTextarea() {
            if (!textarea || !lineNumbers) return;
            // ป้องกันการค้างของตำแหน่ง scroll
            textarea.scrollTop = 0;
            lineNumbers.scrollTop = 0;

            const editorContainer = document.querySelector('.editor-container');
            if (editorContainer) {
                editorContainer.style.height = 'auto';
            }
            textarea.style.height = 'auto';

            // ตรวจสอบแถบเลื่อนแนวนอนเพื่อไม่ให้บังบรรทัดสุดท้าย
            const hasHorizontalScroll = textarea.scrollWidth > textarea.clientWidth;
            const scrollbarHeight = hasHorizontalScroll ? 16 : 0;
            const scrollHeight = textarea.scrollHeight + scrollbarHeight;

            // กำหนดความสูงของคอนเทนเนอร์ให้ตามความยาวโค้ด ขั้นต่ำ 100px และยืดหดตามจริง
            const minHeight = 100;
            const finalHeight = Math.max(minHeight, scrollHeight);

            textarea.style.height = finalHeight + 'px';
            if (editorContainer) {
                editorContainer.style.height = finalHeight + 'px';
            }
        }

        const descTextarea = document.getElementById('description');

        /**
         * ฟังก์ชัน: autoResizeDescription
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ปรับขนาดกล่องคำอธิบายอัตโนมัติ]
         * หน้าที่: ขยายและหดความสูงของกล่องคำอธิบายตามความยาวตัวอักษร
         */
        function autoResizeDescription() {
            if (!descTextarea) return;
            descTextarea.style.height = 'auto';
            const minDescHeight = 60;
            const borderOffset = descTextarea.offsetHeight - descTextarea.clientHeight;
            const scrollHeight = descTextarea.scrollHeight + borderOffset;
            descTextarea.style.height = Math.max(minDescHeight, scrollHeight) + 'px';
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

        // เมื่อมีการแก้ไขให้ใช้ updateditor
        textarea.addEventListener('input', updateEditor);

        // ให้ขนาดช่องคำอธิบายขยายตามความยาวข้อความ
        if (descTextarea) {
            descTextarea.addEventListener('input', autoResizeDescription);
        }

        // ตรวจจับการวางที่เกิน 5000ตัวอักษรแล้วจะตัดไป
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

        // ให้เลขตรงกับบรรทัดเวลาเลือนในช่องใส่โค้ด
        textarea.addEventListener('scroll', () => {
            lineNumbers.scrollTop = textarea.scrollTop;
        });

        // เป็นการเช็คปุ่มแท็กให้เลือนไป 4 ช่องว่าง ถ้าคลุมดำก็จะลบและเคอร์เซอร์ขยับไป 4 ช่องว่าง
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

        // กำหนดค่าเริ่มต้นสำหรับหมายเลขบรรทัดและความสูง
        updateEditor();
        autoResizeDescription();

        // ----------------------------------------------------
        // สคริปต์แสดงตัวอย่างรูปภาพ
        // ----------------------------------------------------
        const imageInput = document.getElementById('image_input');
        const imagePreview = document.getElementById('image-preview');
        const uploadBtnText = document.getElementById('upload-btn-text');
        const currentImagePath = <?php echo json_encode($current_image_path); ?>;
        //เป็นแสดงรูปภาพ
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
        // การกำหนดแท็กต่างๆ
        // ----------------------------------------------------
        const tagsHiddenInput = document.getElementById('tags');
        const tagTextInput = document.getElementById('tag_text_input');
        const tagsInputBox = document.getElementById('tags-input-box');
        const tagPillsList = document.getElementById('tag-pills-list');
        const autocompleteDropdown = document.getElementById('tags-autocomplete-dropdown');
        const tagsLimitMsg = document.getElementById('tags-limit-msg');
        const tagsCountEl = document.getElementById('tags-count');
        const tagsCountInfo = document.getElementById('tags-count-info');
        const allTags = <?php echo json_encode($all_tags); ?> || [];

        let selectedTags = [];
        let activeSuggestionIndex = -1;

        /**
         * ฟังก์ชัน: escapeHtml
         * หน้าที่: ป้องกัน XSS เมื่อแสดงผลชื่อแท็กใน Dropdown
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * ฟังก์ชัน: syncTagsState
         * หน้าที่: ซิงค์ข้อมูลแท็กที่เลือกลง Hidden Input สำหรับส่งฟอร์ม และจัดการสถานะ Disable/แจ้งเตือนเมื่อครบ 5 แท็ก
         */
        function syncTagsState() {
            if (tagsHiddenInput) {
                tagsHiddenInput.value = selectedTags.join(', ');
            }
            if (tagsCountEl) {
                tagsCountEl.textContent = selectedTags.length;
            }

            if (selectedTags.length >= 5) {
                if (tagTextInput) {
                    tagTextInput.disabled = true;
                    tagTextInput.placeholder = 'เลือกครบ 5 แท็กแล้ว';
                    tagTextInput.value = '';
                }
                if (tagsInputBox) tagsInputBox.classList.add('disabled');
                if (tagsLimitMsg) tagsLimitMsg.style.display = 'inline-flex';
                if (tagsCountInfo) tagsCountInfo.classList.add('limit-reached');
                hideDropdown();
            } else {
                if (tagTextInput) {
                    tagTextInput.disabled = false;
                    tagTextInput.placeholder = selectedTags.length === 0 ? 'พิมพ์เพื่อค้นหาหรือเพิ่มแท็ก (เช่น JavaScript, regex...)' : 'พิมพ์แท็กเพิ่ม...';
                }
                if (tagsInputBox) tagsInputBox.classList.remove('disabled');
                if (tagsLimitMsg) tagsLimitMsg.style.display = 'none';
                if (tagsCountInfo) tagsCountInfo.classList.remove('limit-reached');
            }
        }

        /**
         * ฟังก์ชัน: renderTagPills
         * หน้าที่: เรนเดอร์ป้ายแท็ก (Tag Pills) พร้อมปุ่มลบ (x) ในช่องกรอก
         */
        function renderTagPills() {
            if (!tagPillsList) return;
            tagPillsList.innerHTML = '';

            selectedTags.forEach((tagName, index) => {
                const pill = document.createElement('span');
                pill.className = 'tag-pill';

                const label = document.createElement('span');
                label.textContent = tagName;
                pill.appendChild(label);

                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'tag-pill-remove';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = 'ลบแท็ก ' + tagName;
                removeBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeTag(index);
                });

                pill.appendChild(removeBtn);
                tagPillsList.appendChild(pill);
            });

            syncTagsState();
        }

        /**
         * ฟังก์ชัน: addTag
         * หน้าที่: เพิ่มแท็กใหม่เข้าสู่รายการ (จำกัดไม่เกิน 5 แท็ก และไม่ซ้ำ)
         */
        function addTag(tagName) {
            tagName = (tagName || '').trim();
            if (!tagName) return;

            if (selectedTags.length >= 5) {
                syncTagsState();
                return;
            }

            // ตรวจสอบไม่ให้ใส่แท็กซ้ำ (Case-insensitive)
            const exists = selectedTags.some(t => t.toLowerCase() === tagName.toLowerCase());
            if (!exists) {
                selectedTags.push(tagName);
                renderTagPills();
            }

            if (tagTextInput) {
                tagTextInput.value = '';
            }
            hideDropdown();

            if (selectedTags.length < 5 && tagTextInput) {
                tagTextInput.focus();
            }
        }

        /**
         * ฟังก์ชัน: removeTag
         * หน้าที่: ลบแท็กออกจากรายการตาม Index
         */
        function removeTag(index) {
            selectedTags.splice(index, 1);
            renderTagPills();
            if (tagTextInput && !tagTextInput.disabled) {
                tagTextInput.focus();
            }
        }

        /**
         * ฟังก์ชัน: hideDropdown
         * หน้าที่: ซ่อน Dropdown แนะนำแท็ก
         */
        function hideDropdown() {
            if (autocompleteDropdown) {
                autocompleteDropdown.classList.remove('show');
                autocompleteDropdown.innerHTML = '';
            }
            activeSuggestionIndex = -1;
        }

        /**
         * ฟังก์ชัน: filterAndShowSuggestions
         * หน้าที่: ค้นหาแท็กในฐานข้อมูลตามที่ผู้ใช้เริ่มพิมพ์ และแสดงเป็น Dropdown Autocomplete
         */
        function filterAndShowSuggestions(query) {
            query = (query || '').trim().toLowerCase();
            activeSuggestionIndex = -1;

            // หากไม่มีข้อความพิมพ์ หรือเลือกครบ 5 แท็กแล้ว ให้ซ่อน Dropdown ทันที
            if (!query || selectedTags.length >= 5) {
                hideDropdown();
                return;
            }

            const currentLowerTags = selectedTags.map(t => t.toLowerCase());

            // กรองแท็กจากฐานข้อมูลที่มีตัวอักษรตรงกับคำค้นหา และยังไม่ได้ถูกเลือก
            const matches = allTags.filter(tag => {
                const name = (tag.tag_name || '').toLowerCase();
                return name.includes(query) && !currentLowerTags.includes(name);
            });

            const exactMatch = allTags.some(tag => (tag.tag_name || '').toLowerCase() === query);

            autocompleteDropdown.innerHTML = '';

            // แสดงรายการแท็กแนะนำ
            matches.forEach((tag, idx) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.dataset.index = idx;
                item.dataset.tagName = tag.tag_name;

                const tagName = tag.tag_name;
                const lowerName = tagName.toLowerCase();
                const matchPos = lowerName.indexOf(query);

                if (matchPos >= 0) {
                    const before = tagName.slice(0, matchPos);
                    const match = tagName.slice(matchPos, matchPos + query.length);
                    const after = tagName.slice(matchPos + query.length);
                    item.innerHTML = `<span>${escapeHtml(before)}<span class="match-highlight">${escapeHtml(match)}</span>${escapeHtml(after)}</span>`;
                } else {
                    item.textContent = tagName;
                }

                item.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    addTag(tag.tag_name);
                });

                autocompleteDropdown.appendChild(item);
            });

            // หากสิ่งที่พิมพ์ยังไม่มีในฐานข้อมูล ให้มีตัวเลือกเพิ่มเป็นแท็กใหม่ได้
            if (!exactMatch && query.length > 0 && !currentLowerTags.includes(query)) {
                const newItem = document.createElement('div');
                newItem.className = 'autocomplete-item autocomplete-item-new';
                newItem.dataset.tagName = query;
                newItem.innerHTML = `<span>+ เพิ่มแท็ก "<strong>${escapeHtml(query)}</strong>" (แท็กใหม่)</span>`;
                newItem.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    addTag(query);
                });
                autocompleteDropdown.appendChild(newItem);
            }

            if (autocompleteDropdown.children.length > 0) {
                autocompleteDropdown.classList.add('show');
            } else {
                hideDropdown();
            }
        }

        /**
         * ฟังก์ชัน: updateActiveSuggestion
         * หน้าที่: เลื่อนไฮไลต์ในรายการ Dropdown ด้วยปุ่มลูกศรขึ้น/ลง
         */
        function updateActiveSuggestion(items) {
            items.forEach((item, idx) => {
                if (idx === activeSuggestionIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // --- Event Listeners สำหรับ Tag Input ---
        if (tagTextInput) {
            // ดักจับการพิมพ์เพื่อค้นหา
            tagTextInput.addEventListener('input', () => {
                // หากผู้ใช้ Paste หรือพิมพ์จุลภาค (Comma) ให้แยกเป็นแท็กทันที
                if (tagTextInput.value.includes(',')) {
                    const parts = tagTextInput.value.split(',');
                    for (let i = 0; i < parts.length - 1; i++) {
                        if (parts[i].trim() && selectedTags.length < 5) {
                            addTag(parts[i].trim());
                        }
                    }
                    tagTextInput.value = parts[parts.length - 1].trim();
                }
                filterAndShowSuggestions(tagTextInput.value);
            });

            // ดักจับการกดปุ่มคีย์บอร์ด (Enter, ArrowUp, ArrowDown, Backspace, Escape)
            tagTextInput.addEventListener('keydown', (e) => {
                const items = autocompleteDropdown.querySelectorAll('.autocomplete-item');

                if (e.key === 'ArrowDown') {
                    if (items.length > 0) {
                        e.preventDefault();
                        activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                        updateActiveSuggestion(items);
                    }
                } else if (e.key === 'ArrowUp') {
                    if (items.length > 0) {
                        e.preventDefault();
                        activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                        updateActiveSuggestion(items);
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault(); // ป้องกันการ Submit Form ทันที
                    if (activeSuggestionIndex >= 0 && items[activeSuggestionIndex]) {
                        const chosen = items[activeSuggestionIndex].dataset.tagName;
                        if (chosen) addTag(chosen);
                    } else if (tagTextInput.value.trim().length > 0) {
                        addTag(tagTextInput.value.trim());
                    }
                } else if (e.key === ',' || e.key === 'Comma') {
                    e.preventDefault();
                    if (tagTextInput.value.trim().length > 0) {
                        addTag(tagTextInput.value.trim());
                    }
                } else if (e.key === 'Backspace') {
                    // หากช่องพิมพ์ว่างอยู่และกด Backspace ให้ลบแท็กล่าสุดออก
                    if (tagTextInput.value === '' && selectedTags.length > 0) {
                        removeTag(selectedTags.length - 1);
                    }
                } else if (e.key === 'Escape') {
                    hideDropdown();
                }
            });
        }

        // เมื่อคลิกที่กล่อง Input Wrapper ให้โฟกัสที่ช่อง Input
        if (tagsInputBox) {
            tagsInputBox.addEventListener('click', () => {
                if (tagTextInput && !tagTextInput.disabled) {
                    tagTextInput.focus();
                }
            });
        }

        // ปิด Dropdown เมื่อคลิกนอกขอบเขต
        document.addEventListener('click', (e) => {
            if (tagsInputBox && !tagsInputBox.contains(e.target) && autocompleteDropdown && !autocompleteDropdown.contains(e.target)) {
                hideDropdown();
            }
        });

        // ดักจับการส่งฟอร์มเพื่อตรวจสอบความถูกต้อง
        document.querySelector('.form-container').addEventListener('submit', function(e) {
            if (textarea.value.length > 5000) {
                e.preventDefault();
                alert('ใส่โค้ดได้สูงสุด 5,000 ตัวอักษรเท่านั้น');
                textarea.focus();
                return;
            }
            if (selectedTags.length > 5) {
                e.preventDefault();
                alert('คุณสามารถเพิ่มได้สูงสุด 5 tags เท่านั้น');
                if (tagTextInput && !tagTextInput.disabled) tagTextInput.focus();
                return;
            }
        });

        // กำหนดค่าเริ่มต้นของแท็กจากฐานข้อมูล (รองรับโหมดแก้ไข Snippet)
        if (tagsHiddenInput && tagsHiddenInput.value.trim()) {
            selectedTags = tagsHiddenInput.value
                .split(',')
                .map(t => t.trim())
                .filter(t => t.length > 0)
                .slice(0, 5);
        }
        renderTagPills();
    </script>
</body>
</html>