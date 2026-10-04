<?php
/**
 * ==============================================================================
 * ไฟล์: viewcode.php
 * หน้าที่: หน้าแสดงรายละเอียดและเนื้อหาโค้ด Snippet แบบเต็ม (View Snippet Details)
 * ส่วนประกอบในไฟล์:
 *   1. โหลดระบบเซสชัน และจัดการเส้นทางปุ่มย้อนกลับ ($backUrl) ตามแหล่งที่มา
 *   2. ดึงข้อมูล Snippet ตาม ID ($control->getSnippetById())
 *   3. ตรวจสอบสิทธิ์การเข้าถึง (ระดับ Protected ต้องเข้าสู่ระบบก่อน)
 *   4. ตรวจสอบสถานะของผู้ใช้ต่อ Snippet นี้ (ถูกใจแล้วหรือไม่, บุ๊กมาร์กแล้วหรือไม่, รายงานแล้วหรือไม่)
 *   5. ตรวจสอบสถานะการอุทธรณ์กรณี Snippet ถูกระงับ ($control->getLatestAppealBySnippet())
 *   6. ส่วนแสดงผล HTML (ชื่อ Snippet, เจ้าของ, ปุ่มคัดลอกโค้ด, ปุ่มรายงาน, กล่องแสดงโค้ด, รูปภาพ, ข้อมูลอุทธรณ์)
 *   7. JavaScript: ปรับขนาดกล่องโค้ด, เลขบรรทัด, คัดลอกโค้ด, Modal รายงานปัญหา, Lightbox รูป, Modal อุทธรณ์
 * ==============================================================================
 */

require_once "layout/session.php";
require_once "db/connect.php";

$snippet = null;
$isBookmarked = false;
$isLiked = false;
$isReported = false;
$likeCount = 0;
$userId = getCurrentUserId();
$accessDenied = false;

// --- ส่วนที่ 1: กำหนด URL สำหรับปุ่มย้อนกลับตามหน้าที่ผู้ใช้คลิกเข้ามา ---
$backUrl = $userId ? 'snippet_page.php' : 'Home.php';
if(isset($_GET['from_search']) && $_GET['from_search'] === '1'){
    $backUrl = 'result.php';
    $backParams = [];
    if(isset($_GET['q'])) $backParams['q'] = $_GET['q'];
    if(isset($_GET['lang'])) $backParams['lang'] = $_GET['lang'];
    if(!empty($backParams)){
        $backUrl .= '?' . http_build_query($backParams);
    }
}elseif(isset($_GET['from_trash']) && $_GET['from_trash'] === '1'){
    $backUrl = 'remove.php';
}elseif(isset($_GET['from_explore']) && $_GET['from_explore'] === '1'){
    $backUrl = 'explore.php';
}

// --- ส่วนที่ 2: ดึงข้อมูล Snippet และตรวจสอบสิทธิ์/สถานะต่างๆ ---
if(isset($_GET['view']) && is_numeric($_GET['view'])){
    $id = (int)$_GET['view'];
    $snippet = $control->getSnippetById($id, $userId, true);
    
    // ตรวจสอบการเข้าถึงกรณีระดับการมองเห็นเป็น Protected (ต้องล็อกอินก่อนเข้าชม)
    if($snippet && strtolower((string)($snippet['visibility'] ?? '')) === 'protected' && !$userId){
        $accessDenied = true;
    }
    
    // ตรวจสอบสถานะการบุ๊กมาร์ก การกดไลก์ และการรายงานของผู้ใช้
    if($snippet && $userId){
        $isBookmarked = $control->isBookmarked($userId, $id);
        $isLiked = $control->isLiked($userId, $id);
        $isReported = $control->hasUserReported($userId, $id);
    }
    if($snippet){
        $likeCount = $control->getLikeCount($id);
    }
    
    // ตรวจสอบสถานะการยื่นอุทธรณ์สำหรับเจ้าของโค้ด (กรณีโค้ดถูกระงับ)
    $latestAppeal = null;
    $isAppealPending = false;
    $isAppealRejected = false;
    if($snippet && $userId && (int)$snippet['user_id'] === (int)$userId){
        $latestAppeal = $control->getLatestAppealBySnippet($id);
        if($latestAppeal){
            $isAppealPending = ($latestAppeal['status'] === 'pending');
            $isAppealRejected = ($latestAppeal['status'] === 'rejected');
        }
    }
}

// ปฏิเสธการเข้าชมกรณี Protected แล้วยังไม่ล็อกอิน
if($accessDenied){
    ?>
    <script>
        alert("กรุณาเข้าสู่ระบบก่อนเข้าชม");
        window.location.href = "login.php";
    </script>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($snippet['title'] ?? 'ดู Snippet'); ?> - Snippet Box</title>
    <link rel="stylesheet" href="style/fonts.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/createsnippetcss.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style/viewcodecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" onclick="if(window.history.length > 1){ window.history.back(); return false; }" class="back-btn">ย้อนกลับ</a>

    <?php if(!$snippet): ?>
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 16px;">ไม่พบข้อมูล Snippet</div>
    <?php else: ?>
        <div class="form-container">
            <!-- Top Actions Row -->
            <div class="top-actions-row">
                <div class="author-credit" title="เจ้าของ Snippet">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="author-icon">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <span class="author-label">โดย</span>
                    <span class="author-username">@<?php echo htmlspecialchars($snippet['username'] ?? 'Unknown'); ?></span>
                </div>

                <?php if((int)($snippet['deleted'] ?? 0) !== 1 && (int)($snippet['is_suspended'] ?? 0) !== 1): ?>
                    <div class="right-actions">
                        <!-- Report Button -->
                        <button class="btn report-btn <?php echo $isReported ? 'reported' : ''; ?>" id="reportBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>" <?php echo $isReported ? 'disabled' : ''; ?>>
                            <svg width="14" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                                <line x1="4" y1="22" x2="4" y2="15"></line>
                            </svg>
                            <span><?php echo $isReported ? 'รายงานแล้ว' : 'รายงาน'; ?></span>
                        </button>

                        <!-- Bookmark Button -->
                        <button class="btn bookmark-btn <?php echo $isBookmarked ? 'bookmarked' : ''; ?>" id="bookmarkBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                            <svg width="14" height="18" viewBox="0 0 384 512" fill="<?php echo $isBookmarked ? '#fff' : 'none'; ?>" stroke="<?php echo $isBookmarked ? '#fff' : '#111'; ?>" stroke-width="30" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M0 512V48C0 21.49 21.49 0 48 0h288c26.51 0 48 21.49 48 48v464L192 400 0 512z"/>
                            </svg>
                            <span><?php echo $isBookmarked ? 'บุ๊กมาร์กแล้ว' : 'บุ๊กมาร์ก'; ?></span>
                        </button>

                        <!-- Like Button -->
                        <button class="btn like-btn <?php echo $isLiked ? 'liked' : ''; ?>" id="likeBtn" data-snippet-id="<?php echo (int)$snippet['snippet_id']; ?>">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="<?php echo $isLiked ? '#fff' : 'none'; ?>" stroke="<?php echo $isLiked ? '#fff' : '#111'; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px; vertical-align: middle;">
                                <path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3H14zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"/>
                            </svg>
                            <span class="like-btn-count"><?php echo $likeCount; ?></span>
                        </button>

                        <!-- Copy Button -->
                        <button class="btn copy" id="copyBtn">คัดลอก</button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if((int)($snippet['is_suspended'] ?? 0) === 1): ?>
                <!-- Suspension Notice for Owner -->
                <div style="background-color: #fef2f2; border: 1px solid #f87171; border-left: 6px solid #dc2626; border-radius: 8px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.08);">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <span style="font-size: 1.5rem; line-height: 1;">⚠️</span>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 6px 0; color: #991b1b; font-size: 1.05rem; font-weight: 700;">
                                โพสต์นี้ถูกระงับการแสดงผลโดยผู้ดูแลระบบ (Suspended by Admin)
                            </h4>
                            <div style="color: #7f1d1d; font-size: 0.92rem; line-height: 1.5;">
                                <p style="margin: 0 0 4px 0;"><strong>เหตุผลในการระงับ:</strong> <?php echo htmlspecialchars($snippet['suspension_reason'] ?: 'ไม่ระบุเหตุผล'); ?></p>
                                <?php if(!empty($snippet['suspension_time'])): ?>
                                    <p style="margin: 0 0 4px 0; font-size: 0.85rem; color: #991b1b;"><strong>ระงับเมื่อ:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($snippet['suspension_time']))); ?></p>
                                <?php endif; ?>
                                <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #b91c1c;">
                                    * โพสต์นี้ถูกซ่อนจากการแสดงผลต่อสาธารณะ ผู้ใช้อื่นจะไม่สามารถมองเห็นหรือค้นหาได้ และคุณไม่สามารถแก้ไขหรือกู้คืนได้จนกว่าผู้ดูแลระบบจะถอนการระงับ
                                </p>
                            </div>

                            <!-- Appeal Section for Owner -->
                            <?php if((int)($snippet['user_id'] ?? 0) === (int)$userId): ?>
                                <div style="margin-top: 14px; padding-top: 12px; border-top: 1px dashed #fca5a5;">
                                    <?php if($isAppealPending): ?>
                                        <div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px 14px; color: #92400e; font-size: 0.88rem;">
                                            <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                                <span>⏳</span> คำร้องขอถอนการระงับอยู่ระหว่างการพิจารณาโดยผู้ดูแลระบบ
                                            </div>
                                            <div><strong>เหตุผลที่คุณยื่น:</strong> <?php echo nl2br(htmlspecialchars($latestAppeal['reason'])); ?></div>
                                            <div style="font-size: 0.78rem; color: #b45309; margin-top: 4px;">
                                                ยื่นเมื่อ: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($latestAppeal['created_at']))); ?> (ระบบจะแจ้งผลให้ทราบหลังจาก Admin ตรวจสอบ)
                                            </div>
                                        </div>
                                    <?php elseif($isAppealRejected): ?>
                                        <div style="background-color: #fff5f5; border: 1px solid #fed7d7; border-radius: 6px; padding: 10px 14px; color: #9b2c2c; font-size: 0.88rem; margin-bottom: 10px;">
                                            <div style="font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                                <span>❌</span> คำร้องก่อนหน้านี้ของคุณถูกปฏิเสธ
                                            </div>
                                            <?php if(!empty($latestAppeal['admin_notes'])): ?>
                                                <div><strong>เหตุผลจากผู้ดูแลระบบ:</strong> <?php echo nl2br(htmlspecialchars($latestAppeal['admin_notes'])); ?></div>
                                            <?php endif; ?>
                                            <div style="font-size: 0.78rem; color: #e53e3e; margin-top: 4px;">
                                                พิจารณาเมื่อ: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($latestAppeal['reviewed_at'] ?? $latestAppeal['created_at']))); ?>
                                            </div>
                                        </div>
                                        <button type="button" class="btn" id="openAppealModalBtn" style="background-color: #b91c1c; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; transition: background-color 0.2s;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                            แจ้ง Admin ขอถอนการระงับใหม่อีกครั้ง
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn" id="openAppealModalBtn" style="background-color: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 0.9rem; transition: background-color 0.2s;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                            แจ้ง Admin ขอถอนการระงับโพสต์ (ยื่นคำร้อง)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if((int)($snippet['deleted'] ?? 0) === 1): ?>
                <div class="trash-notice">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                    <span>Snippet นี้อยู่ในถังขยะ คุณสามารถกู้คืนหรือลบถาวรได้ที่ <a href="remove.php" style="color: inherit; text-decoration: underline; font-weight: 700;">หน้าถังขยะ</a></span>
                </div>
            <?php endif; ?>

            <!-- Top Row: Title & Language -->
            <div class="top-row">
                <div class="form-group">
                    <label for="title">ชื่อเรื่อง</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($snippet['title']); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="language">ภาษา</label>
                    <select name="language_id" class="language-dropdown" disabled>
                        <option selected><?php echo htmlspecialchars($snippet['language_name'] ?? 'Language'); ?></option>
                    </select>
                </div>
            </div>

            <!-- Code Editor Container (Readonly) -->
            <div class="form-group">
                <label for="source_code">โค้ด</label>
                <div class="editor-container">
                    <div class="line-numbers" id="line-numbers">1</div>
                    <textarea name="source_code" id="source_code" readonly wrap="off" <?php if((int)($snippet['is_suspended'] ?? 0) === 1): ?>style="user-select: none; -webkit-user-select: none;" oncopy="return false;" oncut="return false;"<?php endif; ?>><?php echo htmlspecialchars($snippet['source_code']); ?></textarea>
                </div>
            </div>

            <!-- Description (Readonly) -->
            <div class="form-group">
                <label for="description">คำอธิบาย</label>
                <div class="description-view" id="description"><?php echo htmlspecialchars($snippet['description']); ?></div>
            </div>

            <!-- Tags & Visibility Row -->
            <div class="top-row">
                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($snippet['tags'] ?? ''); ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="visibility">การมองเห็น</label>
                    <select name="visibility" class="visibility-dropdown" disabled>
                        <option value="public" <?php echo (($snippet['visibility'] ?? '') === 'public') ? 'selected' : ''; ?>>สาธารณะ (Public)</option>
                        <option value="protected" <?php echo (($snippet['visibility'] ?? '') === 'protected') ? 'selected' : ''; ?>>เฉพาะสมาชิก (Protected)</option>
                        <option value="private" <?php echo (($snippet['visibility'] ?? '') === 'private') ? 'selected' : ''; ?>>ส่วนตัว (Private)</option>
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
                        <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">ไม่มีรูปภาพ</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Report Modal -->
    <div class="modal-overlay" id="reportModal">
        <div class="modal-card">
            <header class="modal-header">
                <span class="modal-title">รายงาน Snippet</span>
                <button class="modal-close" id="closeReportModalBtn">&times;</button>
            </header>
            <form id="reportForm">
                <div class="modal-body">
                    <p style="margin-bottom: 16px; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        กรุณาเลือกเหตุผลในการรายงาน Snippet เพื่อให้ผู้ดูแลระบบตรวจสอบ
                    </p>
                    
                    <?php if($snippet): ?>
                        <input type="hidden" name="snippet_id" value="<?php echo (int)$snippet['snippet_id']; ?>">
                    <?php endif; ?>
                    
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="spam" required>
                            <span>สแปม / ข้อมูลเท็จหรือหลอกลวง</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="inappropriate">
                            <span>เนื้อหาไม่เหมาะสม / รุนแรง / คุกคาม</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="malicious_code">
                            <span>โค้ดอันตราย / มัลแวร์</span>
                        </label>
                        <label class="report-option-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem;">
                            <input type="radio" name="reason" value="other">
                            <span>อื่นๆ</span>
                        </label>

                    <div class="report-desc-group" id="reportDescGroup" style="margin-top: 16px;">
                        <label for="reportDetails" style="display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 6px; color: var(--secondary);">
                            รายละเอียดเพิ่มเติม <span id="reportReqAsterisk" style="color: #dc3545; display: none;">*</span>
                        </label>
                        <textarea id="reportDetails" name="details" placeholder="รายละเอียดเพิ่มเติม (ไม่บังคับ)..." rows="3" style="width:100%; padding: 10px; border: 1px solid var(--input-border); border-radius: var(--radius-sm); font-size: 0.9rem; resize: vertical; outline: none; box-sizing: border-box;"></textarea>
                    </div>
                </div>
                <footer class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid var(--border-color); margin-top: 15px;">
                    <button type="button" class="btn" id="cancelReportBtn" style="border: 1px solid var(--border-color); background: transparent; padding: 8px 16px;">ยกเลิก</button>
                    <button type="submit" class="btn copy" style="background-color: #dc3545; border-color: #dc3545; color: #fff; padding: 8px 16px; font-weight: 600;">ส่งรายงาน</button>
                </footer>
            </form>
        </div>
    </div>

    <!-- Appeal Modal -->
    <div class="modal-overlay" id="appealModal">
        <div class="modal-card">
            <header class="modal-header">
                <span class="modal-title" style="display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    ยื่นคำร้องขอถอนการระงับโพสต์
                </span>
                <button class="modal-close" id="closeAppealModalBtn">&times;</button>
            </header>
            <form id="appealForm">
                <div class="modal-body">
                    <p style="margin-bottom: 12px; color: var(--text-muted); font-size: 0.9rem; line-height: 1.5;">
                        กรุณาระบุเหตุผลหรือคำชี้แจงเพื่อส่งให้ผู้ดูแลระบบพิจารณาถอนการระงับโพสต์นี้
                    </p>
                    
                    <?php if($snippet): ?>
                        <input type="hidden" name="snippet_id" value="<?php echo (int)$snippet['snippet_id']; ?>">
                    <?php endif; ?>

                    <div style="background-color: #fef2f2; border: 1px solid #fee2e2; border-radius: 6px; padding: 10px 12px; margin-bottom: 16px; font-size: 0.85rem; color: #991b1b;">
                        <strong>เหตุผลที่โพสต์ถูกระงับ:</strong> <?php echo htmlspecialchars($snippet['suspension_reason'] ?: 'ไม่ระบุเหตุผล'); ?>
                    </div>

                    <div class="form-group" style="margin-top: 10px;">
                        <label for="appealReason" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px; color: var(--secondary);">
                            เหตุผล / คำชี้แจงของคุณ <span style="color: #dc3545;">*</span>
                        </label>
                        <textarea id="appealReason" name="reason" placeholder="อธิบายข้อเท็จจริงหรือเหตุผลที่ควรได้รับการถอนการระงับ..." rows="4" required style="width:100%; padding: 10px; border: 1px solid var(--input-border); border-radius: var(--radius-sm); font-size: 0.9rem; resize: vertical; outline: none; box-sizing: border-box;"></textarea>
                    </div>
                </div>
                <footer class="modal-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding-top: 15px; border-top: 1px solid var(--border-color); margin-top: 15px;">
                    <button type="button" class="btn" id="cancelAppealBtn" style="border: 1px solid var(--border-color); background: transparent; padding: 8px 16px; border-radius: var(--radius-sm); cursor: pointer;">ยกเลิก</button>
                    <button type="submit" class="btn" id="submitAppealBtn" style="background-color: #dc2626; border: 1px solid #dc2626; color: #fff; padding: 8px 18px; font-weight: 600; border-radius: var(--radius-sm); cursor: pointer;">ส่งคำร้อง</button>
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
        // --- ส่วนที่ 7: สคริปต์ JavaScript จัดการการทำงานของหน้าดูรายละเอียด Snippet ---

        /**
         * ฟังก์ชัน: showToast
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: กล่องแจ้งเตือน]
         * หน้าที่: แสดงกล่องข้อความ Toast แจ้งเตือนชั่วคราว 2 วินาที
         * พารามิเตอร์: message
         */
        function showToast(message) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        }

        const textarea = document.getElementById('source_code');
        const lineNumbers = document.getElementById('line-numbers');

        /**
         * ฟังก์ชัน: autoResizeTextarea
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: ปรับขนาดกล่องโค้ด]
         * หน้าที่: ปรับความสูงของกล่องข้อความโค้ดและคอนเทนเนอร์ให้ตรงตามความยาวโค้ด
         */
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

        /**
         * ฟังก์ชัน: updateLineNumbers
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: เลขบรรทัดโค้ด]
         * หน้าที่: นับบรรทัดและสร้างตัวเลขบรรทัด (Line Numbers) ทางด้านซ้ายของกล่องโค้ด
         */
        function updateLineNumbers() {
            if (textarea) {
                const lines = textarea.value.split('\n').length;
                lineNumbers.innerHTML = Array(lines).fill(0).map((_, i) => i + 1).join('<br>');
            }
        }

        /**
         * ฟังก์ชัน: updateEditor
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: อัปเดตการแสดงผล Editor]
         * หน้าที่: เรียกทำงาน updateLineNumbers และ autoResizeTextarea พร้อมกัน
         */
        function updateEditor() {
            updateLineNumbers();
            autoResizeTextarea();
        }

        if (textarea) {
            // ซิงค์ตำแหน่ง Scroll ระหว่างกล่องโค้ดและตัวเลขบรรทัด
            textarea.addEventListener('scroll', () => {
                lineNumbers.scrollTop = textarea.scrollTop;
            });
            updateEditor();
        }

        // copy button
        const copyBtn = document.getElementById('copyBtn');
        const isSuspended = <?php echo ((int)($snippet['is_suspended'] ?? 0) === 1) ? 'true' : 'false'; ?>;
        
        if (isSuspended && textarea) {
            textarea.addEventListener('copy', (e) => {
                e.preventDefault();
                showToast('⚠️ โพสต์นี้ถูกระงับ ไม่สามารถคัดลอกโค้ดได้');
            });
            textarea.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'c') {
                    e.preventDefault();
                    showToast('⚠️ โพสต์นี้ถูกระงับ ไม่สามารถคัดลอกโค้ดได้');
                }
            });
        }

        copyBtn?.addEventListener('click', async ()=>{
            if (isSuspended) {
                showToast('⚠️ โพสต์นี้ถูกระงับ ไม่สามารถคัดลอกโค้ดได้');
                return;
            }
            const text = textarea.value;
            try{
                await navigator.clipboard.writeText(text);
                const old = copyBtn.innerText;
                copyBtn.innerText = 'คัดลอกแล้ว';
                setTimeout(()=> copyBtn.innerText = old, 1500);
            }catch(e){
                alert('การคัดลอกล้มเหลว');
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
                    showToast('👍 ถูกใจแล้ว!');
                } else if(data.status === 'removed') {
                    this.classList.remove('liked');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#111');
                    countEl.textContent = data.count;
                    showToast('👎 ยกเลิกถูกใจแล้ว');
                    showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                }
            } catch(err) {
                showToast('⚠ ข้อผิดพลาดในการเชื่อมต่อ');
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
                    if (span) span.textContent = 'บุ๊กมาร์กแล้ว';
                    showToast('✓ บุ๊กมาร์กแล้ว!');
                } else if(data.status === 'removed') {
                    this.classList.remove('bookmarked');
                    svg.setAttribute('fill', 'none');
                    svg.setAttribute('stroke', '#111');
                    if (span) span.textContent = 'บุ๊กมาร์ก';
                    showToast('✗ ลบบุ๊กมาร์กแล้ว');
                    showToast('⚠ ' + (data.message || 'เกิดข้อผิดพลาด'));
                }
            } catch(err) {
                showToast('⚠ ข้อผิดพลาดในการเชื่อมต่อ');
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
                showToast('⚠ กรุณาเข้าสู่ระบบก่อนรายงาน');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
                return;
            }
            reportModal.classList.add('open');
        });

        const reportReqAsterisk = document.getElementById('reportReqAsterisk');

        /**
         * ฟังก์ชัน: closeReportModal
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: จัดการหน้าต่างรายงานปัญหา]
         * หน้าที่: ปิด Modal รายงานปัญหา และรีเซ็ตค่าในฟอร์มกลับสู่ค่าเริ่มต้น
         */
        function closeReportModal() {
            reportModal.classList.remove('open');
            reportForm.reset();
            reportDetails.removeAttribute('required');
            reportDetails.placeholder = "รายละเอียดเพิ่มเติม (ไม่บังคับ)...";
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
                    reportDetails.placeholder = "กรุณาระบุรายละเอียดเพื่อให้ตรวจสอบ (จำเป็น)...";
                    if (reportReqAsterisk) reportReqAsterisk.style.display = 'inline';
                    reportDetails.focus();
                } else {
                    reportDetails.removeAttribute('required');
                    reportDetails.placeholder = "รายละเอียดเพิ่มเติม (ไม่บังคับ)...";
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
                    reportBtn.querySelector('span').textContent = 'รายงานแล้ว';
                } else {
                    showToast('⚠ ' + data.message);
                }
            } catch (err) {
                showToast('⚠ เกิดข้อผิดพลาดขณะส่งข้อมูล');
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

        /**
         * ฟังก์ชัน: closeImageModal
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: กล่องขยายรูปภาพ Lightbox]
         * หน้าที่: ปิดหน้าต่าง Modal แสดงรูปภาพขนาดใหญ่
         */
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

        // Appeal Modal functionality
        const openAppealModalBtn = document.getElementById('openAppealModalBtn');
        const appealModal = document.getElementById('appealModal');
        const closeAppealModalBtn = document.getElementById('closeAppealModalBtn');
        const cancelAppealBtn = document.getElementById('cancelAppealBtn');
        const appealForm = document.getElementById('appealForm');
        const submitAppealBtn = document.getElementById('submitAppealBtn');

        /**
         * ฟังก์ชัน: openAppealModal
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: จัดการหน้าต่างยื่นอุทธรณ์]
         * หน้าที่: เปิด Modal ให้เจ้าของ Snippet กรอกคำชี้แจงเพื่อขอยกเลิกการระงับ
         */
        function openAppealModal() {
            appealModal?.classList.add('open');
        }

        /**
         * ฟังก์ชัน: closeAppealModal
         * ส่วนที่: [ฟังก์ชันในหน้าเว็บ: จัดการหน้าต่างยื่นอุทธรณ์]
         * หน้าที่: ปิด Modal ยื่นอุทธรณ์และล้างข้อความในฟอร์ม
         */
        function closeAppealModal() {
            appealModal?.classList.remove('open');
            appealForm?.reset();
        }

        openAppealModalBtn?.addEventListener('click', openAppealModal);
        closeAppealModalBtn?.addEventListener('click', closeAppealModal);
        cancelAppealBtn?.addEventListener('click', closeAppealModal);

        window.addEventListener('click', function(e) {
            if (e.target === appealModal) {
                closeAppealModal();
            }
        });

        appealForm?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const reasonInput = document.getElementById('appealReason');
            const reason = reasonInput ? reasonInput.value.trim() : '';
            if (!reason) {
                showToast('⚠ กรุณากรอกเหตุผลในการยื่นคำร้อง');
                return;
            }

            if (submitAppealBtn) {
                submitAppealBtn.disabled = true;
                submitAppealBtn.textContent = 'กำลังส่ง...';
            }

            const formData = new FormData(this);

            try {
                const response = await fetch('appeal_action.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.status === 'success') {
                    showToast('✓ ' + data.message);
                    closeAppealModal();
                    // รีโหลดหน้าหลังจากยื่นคำร้องสำเร็จ เพื่ออัปเดตกล่องสถานะ
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    showToast('⚠ ' + data.message);
                    if (submitAppealBtn) {
                        submitAppealBtn.disabled = false;
                        submitAppealBtn.textContent = 'ส่งคำร้อง';
                    }
                }
            } catch (err) {
                showToast('⚠ เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่');
                if (submitAppealBtn) {
                    submitAppealBtn.disabled = false;
                    submitAppealBtn.textContent = 'ส่งคำร้อง';
                }
            }
        });
    </script>
</body>
</html>
