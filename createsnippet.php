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

$userId = $_SESSION['user_id'] ?? ($_SESSION['userid'] ?? 0);

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
    <style>
        body {
            background-color: #f4f5f7;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 30px;
            color: #111;
        }

        h1 { display: none; }

        .back-btn {
            background-color: #fff;
            border: 1px solid #888;
            padding: 8px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            color: #111;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .form-container {
            background-color: #f4f5f7;
            border: 1px solid #b3b3b3;
            border-radius: 12px;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            font-size: 14px;
            color: #111;
        }

        input[type="text"], textarea {
            border: 1px solid #b3b3b3;
            border-radius: 8px;
            padding: 10px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            background-color: #fff;
            outline: none;
        }

        input[name="title"],
        input[name="tags"] {
            width: 350px;
            height: 35px;
        }

        /* --- เพิ่ม CSS สำหรับจัดการแถวบน และ Dropdown ของ Language --- */
        .top-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .language-dropdown {
            padding: 10px 35px 10px 15px;
            border: 1px solid #b3b3b3;
            border-radius: 8px;
            background-color: #fff;
            font-size: 14px;
            font-family: Arial, sans-serif;
            color: #111;
            cursor: pointer;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            min-width: 180px;
            height: 47px; /* ปรับความสูงให้เท่ากับ Input ของ Title */
            box-sizing: border-box;
        }

        .language-dropdown:focus { border-color: #888; }
        /* -------------------------------------------------------- */

        textarea[name="description"] {
            width: 100%;
            height: 100px;
            resize: vertical;
        }

        .editor-container {
            display: flex;
            border: 1px solid #b3b3b3;
            border-radius: 8px;
            overflow: hidden;
            background-color: #fff;
            height: 280px;
        }

        .line-numbers {
            width: 40px;
            background-color: #d1d1d1;
            color: #333;
            text-align: center;
            padding: 10px 0;
            font-family: monospace;
            font-size: 14px;
            line-height: 21px;
            overflow: hidden;
            user-select: none;
            border-right: 1px solid #b3b3b3;
        }

        textarea[name="source_code"] {
            flex-grow: 1;
            border: none;
            border-radius: 0;
            padding: 10px;
            margin: 0;
            font-family: monospace;
            font-size: 14px;
            line-height: 21px;
            resize: none;
            white-space: pre;
            overflow-wrap: normal;
            overflow-x: auto;
        }

        textarea[name="source_code"]:focus { outline: none; }

        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        /* จัดกลุ่มปุ่ม Upload และพรีวิวรูปภาพให้อยู่ติดกัน */
        .left-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px 8px 8px;
            background-color: #fff;
            border: 1px solid #b3b3b3;
            border-radius: 8px;
            font-size: 13px;
            color: #111;
            cursor: pointer;
            user-select: none;
        }

        .upload-btn:hover { background-color: #f9f9f9; }

        .upload-icon {
            background-color: #111;
            color: #fff;
            width: 22px;
            height: 22px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-icon svg { width: 14px; height: 14px; }

        /* สไตล์สำหรับรูปภาพพรีวิว */
        #image-preview {
            display: none; /* ซ่อนไว้ก่อนจนกว่าจะมีการเลือกไฟล์ */
            max-height: 40px; 
            border-radius: 6px;
            border: 1px solid #b3b3b3;
            object-fit: cover;
        }

        .right-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .visibility-dropdown {
            padding: 8px 35px 8px 15px;
            border: 1px solid #b3b3b3;
            border-radius: 8px;
            background-color: #fff;
            font-size: 14px;
            color: #111;
            cursor: pointer;
            outline: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 16px;
            min-width: 120px;
        }

        .visibility-dropdown:focus { border-color: #888; }

        input[type="submit"] {
            background-color: #1f1f1f;
            color: #fff;
            border: none;
            padding: 10px 40px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
        }

        input[type="submit"]:hover { background-color: #000; }
    </style>
</head>
<body>
    
    <a href="Home.html" class="back-btn">Back</a>
    
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