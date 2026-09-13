<?php
require_once __DIR__ . '/check_admin.php';

$successMsg = "";
$errorMsg = "";
$report = null;

// Handle POST Actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $snippetId = (int)($_POST['snippet_id'] ?? 0);
        $adminUsername = $_SESSION['username'] ?? 'Admin';
        
        if ($_POST['action'] === 'reject') {
            if ($reportId <= 0) {
                $errorMsg = "Invalid report ID.";
            } else {
                if ($control->rejectReportAdmin($reportId)) {
                    $successMsg = "Report dismissed.";
                } else {
                    $errorMsg = "Failed to dismiss report.";
                }
            }
        } elseif ($_POST['action'] === 'suspend_snippet') {
            if ($snippetId <= 0) {
                $errorMsg = "Invalid snippet ID.";
            } else {
                $reason = trim($_POST['reason'] ?? 'Reported violation');
                if ($reason === '') {
                    $reason = 'Reported violation';
                }
                if ($control->suspendSnippetAdmin($snippetId, $adminUsername, $reason)) {
                    $successMsg = "Snippet suspended successfully and related reports resolved.";
                } else {
                    $errorMsg = "Failed to suspend snippet.";
                }
            }
        } elseif ($_POST['action'] === 'unsuspend_snippet') {
            if ($snippetId <= 0) {
                $errorMsg = "Invalid snippet ID.";
            } else {
                if ($control->unsuspendSnippetAdmin($snippetId, $adminUsername)) {
                    $successMsg = "Snippet unsuspended successfully.";
                } else {
                    $errorMsg = "Failed to unsuspend snippet.";
                }
            }
        }
    }
}

$backUrl = 'manage_reports.php';

$suspensionHistory = [];
if(isset($_GET['id']) && is_numeric($_GET['id'])){
    $id = (int)$_GET['id'];
    $report = $control->getReportByIdAdmin($id);
    if ($report && !empty($report['snippet_id'])) {
        $suspensionHistory = $control->getSnippetSuspensionHistory((int)$report['snippet_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Report Details - Admin</title>
    <link rel="stylesheet" href="../style/viewcodecss.css?v=<?php echo time(); ?>">
</head>
<body>
    <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-btn">Back</a>

    <?php if(!$report): ?>
        <div class="empty" style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 16px;">Report not found</div>
    <?php else: ?>
        <div class="form-container" style="max-width: 900px; margin: 0 auto; gap: 20px;">
            <!-- Status Alerts -->
            <?php if ($successMsg !== ''): ?>
                <div style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 12px 16px; border-radius: 8px; font-size: 14px; text-align: center; width: 100%;">
                    <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>
            <?php if ($errorMsg !== ''): ?>
                <div style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 12px 16px; border-radius: 8px; font-size: 14px; text-align: center; width: 100%;">
                    <?php echo htmlspecialchars($errorMsg); ?>
                </div>
            <?php endif; ?>

            <!-- Report Information Card -->
            <div class="report-info-card" style="background-color: #fff; border: 1px solid var(--border-color); border-left: 4px solid #dc3545; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; width: 100%; box-sizing: border-box;">
                <h3 style="margin-top: 0; margin-bottom: 12px; color: #dc3545; font-size: 1.1rem; display: flex; align-items: center; gap: 8px; font-family: 'Lato', sans-serif;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    Report Details #<?php echo htmlspecialchars($report['report_id']); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; font-size: 0.95rem; font-family: 'Lato', sans-serif;">
                    <div>
                        <strong>Reported Reason:</strong> 
                        <span style="color: #dc3545; font-weight: bold; text-transform: capitalize;"><?php echo htmlspecialchars($report['reason']); ?></span>
                    </div>
                    <div>
                        <strong>Reporter:</strong> 
                        <span><?php echo htmlspecialchars($report['reporter_firstname'] . ' ' . $report['reporter_lastname']); ?> (@<?php echo htmlspecialchars($report['reporter_username']); ?>)</span>
                    </div>
                    <div>
                        <strong>Snippet Owner:</strong> 
                        <span><?php echo htmlspecialchars($report['owner_firstname'] . ' ' . $report['owner_lastname']); ?> (@<?php echo htmlspecialchars($report['owner_username']); ?>)</span>
                    </div>
                    <div>
                        <strong>Report Date:</strong> 
                        <span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($report['created_at']))); ?></span>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-color); font-family: 'Lato', sans-serif;">
                    <strong>Report Description:</strong>
                    <div style="margin-top: 6px; padding: 12px; background-color: var(--neutral-bg); border-radius: var(--radius-sm); border: 1px solid var(--border-color); white-space: pre-wrap; font-size: 0.9rem; line-height: 1.4; color: #333;">
                        <?php echo htmlspecialchars($report['details'] ?: 'No description'); ?>
                    </div>
                </div>
                
                <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div>
                        <span style="font-weight: 600; font-family: 'Lato', sans-serif; font-size: 0.95rem;">Snippet Status: </span>
                        <?php if ((int)($report['snippet_suspended'] ?? 0) === 1): ?>
                            <span style="font-weight: bold; color: #dc2626; padding: 3px 8px; background: #fee2e2; border-radius: 4px; font-size: 0.85rem;">SUSPENDED (ถูกระงับ)</span>
                        <?php elseif ((int)($report['snippet_deleted'] ?? 0) === 1): ?>
                            <span style="font-weight: bold; color: #6b7280; padding: 3px 8px; background: #f3f4f6; border-radius: 4px; font-size: 0.85rem;">DELETED (ผู้ใช้ลบ)</span>
                        <?php else: ?>
                            <span style="font-weight: bold; color: #15803d; padding: 3px 8px; background: #dcfce7; border-radius: 4px; font-size: 0.85rem;">ACTIVE (ปกติ)</span>
                        <?php endif; ?>
                    </div>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <?php if ($report['status'] === 'pending'): ?>
                            <!-- Reject / Dismiss Report Form -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="report_id" value="<?php echo $report['report_id']; ?>">
                                <button type="submit" class="btn" style="border-color: var(--border-color); color: white; background: #15803D;" onclick="return confirm('Dismiss this report?');">Dismiss Report</button>
                            </form>
                        <?php endif; ?>

                        <?php if ((int)($report['snippet_suspended'] ?? 0) === 1): ?>
                            <!-- Unsuspend Snippet Form -->
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="unsuspend_snippet">
                                <input type="hidden" name="snippet_id" value="<?php echo $report['snippet_id']; ?>">
                                <button type="submit" class="btn" style="background-color: #2563eb; color: white;" onclick="return confirm('Are you sure you want to unsuspend this snippet?');">Unsuspend Snippet</button>
                            </form>
                        <?php else: ?>
                            <!-- Suspend Snippet Form -->
                            <form method="POST" style="margin: 0;" onsubmit="return promptSuspend(this, '<?php echo htmlspecialchars(addslashes($report['reason'])); ?>');">
                                <input type="hidden" name="action" value="suspend_snippet">
                                <input type="hidden" name="snippet_id" value="<?php echo $report['snippet_id']; ?>">
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn copy" style="background-color: #d97706; border-color: #d97706; color: white;">Suspend Snippet</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Suspension History Card -->
            <div class="report-info-card" style="background-color: #fff; border: 1px solid var(--border-color); border-left: 4px solid #2563eb; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; width: 100%; box-sizing: border-box; font-family: 'Lato', sans-serif;">
                <h3 style="margin-top: 0; margin-bottom: 12px; color: #2563eb; font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    Snippet Suspension History (ประวัติการระงับ)
                </h3>
                <?php if (empty($suspensionHistory)): ?>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem; font-style: italic;">No suspension history recorded for this snippet.</p>
                <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.88rem;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--border-color); text-align: left; color: var(--text-muted);">
                                    <th style="padding: 8px 12px;">#</th>
                                    <th style="padding: 8px 12px;">Suspended By</th>
                                    <th style="padding: 8px 12px;">Reason</th>
                                    <th style="padding: 8px 12px;">Suspended At</th>
                                    <th style="padding: 8px 12px;">Unsuspended By</th>
                                    <th style="padding: 8px 12px;">Unsuspended At</th>
                                    <th style="padding: 8px 12px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($suspensionHistory as $idx => $sh): ?>
                                    <tr style="border-bottom: 1px solid var(--border-color);">
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['suspension_id']); ?></td>
                                        <td style="padding: 10px 12px; font-weight: 600;"><?php echo htmlspecialchars($sh['admin_suspended']); ?></td>
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['reason']); ?></td>
                                        <td style="padding: 10px 12px; color: var(--text-muted);"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sh['suspended_at']))); ?></td>
                                        <td style="padding: 10px 12px;"><?php echo htmlspecialchars($sh['admin_unsuspended'] ?: '-'); ?></td>
                                        <td style="padding: 10px 12px; color: var(--text-muted);"><?php echo $sh['unsuspended_at'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($sh['unsuspended_at']))) : '-'; ?></td>
                                        <td style="padding: 10px 12px;">
                                            <?php if (empty($sh['unsuspended_at'])): ?>
                                                <span style="color: #dc2626; font-weight: 600;">Active Suspension</span>
                                            <?php else: ?>
                                                <span style="color: #15803d; font-weight: 600;">Restored</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Code Details Card (Viewcode Layout) -->
            <div style="width: 100%; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 32px; background: #fff; box-shadow: 0 2px 12px rgba(0,0,0,0.04); box-sizing: border-box; display: flex; flex-direction: column; gap: 24px;">
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
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($report['snippet_title']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="language">Language</label>
                        <select name="language_id" class="language-dropdown" disabled>
                            <option selected><?php echo htmlspecialchars($report['language_name'] ?? 'Language'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Code Editor Container (Readonly) -->
                <div class="form-group">
                    <label for="source_code">Code</label>
                    <div class="editor-container">
                        <div class="line-numbers" id="line-numbers">1</div>
                        <textarea name="source_code" id="source_code" readonly wrap="off"><?php echo htmlspecialchars($report['source_code']); ?></textarea>
                    </div>
                </div>

                <!-- Description (Readonly) -->
                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="description-view" id="description"><?php echo htmlspecialchars($report['snippet_desc']); ?></div>
                </div>

                <!-- Tags & Visibility Row -->
                <div class="top-row">
                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" name="tags" id="tags" value="<?php echo htmlspecialchars($report['tags'] ?? ''); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="visibility">Visibility</label>
                        <select name="visibility" class="visibility-dropdown" disabled>
                            <option value="public" <?php echo (($report['visibility'] ?? '') === 'public') ? 'selected' : ''; ?>>Public</option>
                            <option value="protected" <?php echo (($report['visibility'] ?? '') === 'protected') ? 'selected' : ''; ?>>Protected</option>
                            <option value="private" <?php echo (($report['visibility'] ?? '') === 'private') ? 'selected' : ''; ?>>Private</option>
                        </select>
                    </div>
                </div>

                <!-- Action Row -->
                <div class="action-row" style="margin-top: 0;">
                    <!-- Left: Show Image directly -->
                    <div class="left-actions">
                        <?php if(!empty($report['image_path']) && file_exists('../' . $report['image_path'])): ?>
                            <img id="image-preview" src="<?php echo '../' . htmlspecialchars($report['image_path']); ?>" alt="Snippet Image" style="display: block;">
                        <?php else: ?>
                            <span style="font-size: 14px; color: var(--text-muted); font-style: italic;">No Image</span>
                        <?php endif; ?>
                    </div>
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
            if (toast) {
                toast.textContent = message;
                toast.classList.add('show');
                setTimeout(() => toast.classList.remove('show'), 2000);
            }
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

        function promptSuspend(form, defaultReason) {
            const reason = prompt('Please enter the reason for suspending this snippet:', defaultReason || 'Policy violation');
            if (reason === null) {
                return false;
            }
            form.querySelector('input[name="reason"]').value = reason.trim() || defaultReason || 'Policy violation';
            return true;
        }
    </script>
</body>
</html>
