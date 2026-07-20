<?php
require_once "db/connect.php";
require_once "layout/session.php";
$result = $control->getLanguage();

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

$userId = getCurrentUserId();

if(!$userId){
    header('Location:login.php');
    exit;
}

if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $control->deleteSnippet((int)$_GET['delete'], $userId);
    header('Location:snippet_page.php');
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

    if($snippet_id){
        $status = $control->updateSnippet($snippet_id,$title,$source_code,$description,$visibility,$image_path,$language_id,$tags, $userId);
    } else {
        $status = $control->insert($title,$source_code,$description,$visibility,$image_path,$language_id,$tags, $userId);
    }

    header("Location:snippet_page.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Snippet</title>
    <link rel="stylesheet" href="style/createsnippetcss.css">
</head>
<body>
    
    <a href="snippet_page.php" class="back-btn">Back</a>
    
    <h1>สร้าง Snippet Box</h1>
    
    <form method="POST" action="createsnippet.php" class="form-container" enctype="multipart/form-data">
        <input type="hidden" name="snippet_id" value="<?php echo htmlspecialchars($snippet_id); ?>">
        <input type="hidden" name="current_image_path" value="<?php echo htmlspecialchars($current_image_path); ?>">
        
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
            <input type="text" name="tags" id="tags" placeholder="e.g. JavaScript, ajax, DOM" value="<?php echo htmlspecialchars($tags); ?>">
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
                    Upload Image
                    <input type="file" name="image_path" id="image_input" accept="image/*" style="display: none;">
                </label>
                
                <img id="image-preview" src="" alt="Image Preview">
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

        // ฟังก์ชันอัปเดตเลขบรรทัด
        function updateLineNumbers() {
            const lines = textarea.value.split('\n').length;
            lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
        }

        // ตรวจจับการพิมพ์หรือการวางโค้ด
        textarea.addEventListener('input', updateLineNumbers);

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
                updateLineNumbers();
            }
        });

        // กำหนดค่าเริ่มต้นเลขบรรทัด
        updateLineNumbers();

        // ----------------------------------------------------
        // สคริปต์สำหรับแสดงภาพตัวอย่าง (Image Preview)
        // ----------------------------------------------------
        const imageInput = document.getElementById('image_input');
        const imagePreview = document.getElementById('image-preview');

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0]; // ดึงไฟล์แรกที่ผู้ใช้เลือก
            
            if (file) {
                // สร้าง URL ชั่วคราวจากไฟล์ที่อยู่ในเครื่อง แล้วยัดใส่ src ของ img
                imagePreview.src = URL.createObjectURL(file);
                imagePreview.style.display = 'block'; // เปลี่ยนจากซ่อนเป็นแสดงรูปภาพ
            } else {
                // ถ้าผู้ใช้กดยกเลิกการเลือกไฟล์
                imagePreview.src = '';
                imagePreview.style.display = 'none'; // ซ่อนรูปภาพกลับไปเหมือนเดิม
            }
        });
    </script>
</body>
</html>