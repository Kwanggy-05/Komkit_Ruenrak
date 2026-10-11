<?php
/**
 * ==============================================================================
 * ไฟล์: db/user.php
 * คลาส: User
 * หน้าที่: จัดการข้อมูลผู้ใช้งาน สมาชิก สิทธิ์ การล็อกอิน การรีเซ็ตรหัสผ่าน และระบบแบน/ปลดแบน
 * 
 * สรุปฟังก์ชันในคลาส:
 *   - __construct()                     : สร้าง Object และรับค่า PDO Connection
 *   - insertUser()                      : บันทึกข้อมูลสมัครสมาชิกใหม่ พร้อมแฮชรหัสผ่าน
 *   - countUsers()                      : นับจำนวนผู้ใช้งานทั้งหมดในระบบ
 *   - getUserByUsername()               : ตรวจสอบและค้นหาผู้ใช้จาก Username
 *   - getUser()                         : ตรวจสอบการเข้าสู่ระบบ (Login) และตรวจสอบสถานะแบน
 *   - isUserBanned()                    : ตรวจสอบว่าผู้ใช้กำลังติดสถานะถูกแบนหรือไม่
 *   - resetPasswordWithSecurityAnswer() : รีเซ็ตรหัสผ่านด้วยคำถามความปลอดภัย (สำหรับหน้า Forget Password)
 *   - getUsersPaginated()               : ดึงรายชื่อผู้ใช้แบบแบ่งหน้า สำหรับระบบหลังบ้าน (Admin)
 *   - getTotalUsersCount()              : นับจำนวนผู้ใช้ตามตัวกรองค้นหา สำหรับแบ่งหน้า
 *   - getUserStats()                    : ดึงสถิติจำนวนผู้ใช้ (ทั้งหมด, สมัครวันนี้, ถูกแบน)
 *   - banUser()                         : ดำเนินการแบนผู้ใช้และบันทึกประวัติ
 *   - unbanUser()                       : ปลดแบนผู้ใช้และบันทึกข้อมูลผู้ปลดแบน
 *   - getUserBanHistory()               : ดึงประวัติการถูกแบนของสมาชิกรายบุคคล
 *   - getAllBanHistory()                : ดึงประวัติการแบนทั้งหมดในระบบ
 * ==============================================================================
 */
class User{
    private $db;

    /**
     * ฟังก์ชัน: __construct
     * ส่วนที่: กำหนดค่าเริ่มต้นคลาส
     * หน้าที่: รับ PDO Database Connection เพื่อใช้ติดต่อฐานข้อมูล
     */
    function __construct($con){
        $this->db=$con;
    }

    /**
     * ฟังก์ชัน: insertUser
     * ส่วนที่: ระบบสมัครสมาชิก (Registration)
     * หน้าที่: เพิ่มผู้ใช้งานใหม่ลงในฐานข้อมูล แฮชรหัสผ่านและคำตอบความปลอดภัยด้วย BCRYPT
     * พารามิเตอร์: $firstname, $lastname, $username, $password, $question, $answer
     * คืนค่า: bool (true เมื่อสำเร็จ, false เมื่อ Username ซ้ำหรือเกิดข้อผิดพลาด)
     */
    function insertUser($firstname,$lastname,$username,$password,$question,$answer){
        try{
            $result=$this->getUserByUsername($username);
            if($result["num"]>0){
                return false;
            }else{
                $new_password = password_hash($password, PASSWORD_DEFAULT);
                $new_answer = password_hash(trim(mb_strtolower($answer)), PASSWORD_DEFAULT);
                $sql="INSERT INTO user(firstname,lastname,username,password,question,answer)
                VALUES(:firstname,:lastname,:username,:password,:question,:answer)";
                $stmt=$this->db->prepare($sql);
                $stmt->bindParam(":firstname",$firstname);
                $stmt->bindParam(":lastname",$lastname);
                $stmt->bindParam(":username",$username);
                $stmt->bindParam(":password",$new_password);
                $stmt->bindParam(":question",$question);
                $stmt->bindParam(":answer",$new_answer);
                $stmt->execute();
                return true;
            }
            
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * ฟังก์ชัน: countUsers
     * ส่วนที่: ระบบสถิติ/นับจำนวน
     * หน้าที่: นับจำนวนบัญชีผู้ใช้ทั้งหมดที่มีอยู่ในฐานข้อมูล
     * คืนค่า: int จำนวนผู้ใช้
     */
    function countUsers(){
        try{
            $sql="SELECT COUNT(*) as num FROM user";
            $stmt=$this->db->prepare($sql);
            $stmt->execute();
            $result=$stmt->fetch();
            return (int)$result["num"];
        }catch(PDOException $e){
            echo $e->getMessage();
            return 0;
        }
    }

    /**
     * ฟังก์ชัน: getUserByUsername
     * ส่วนที่: การตรวจสอบบัญชีผู้ใช้
     * หน้าที่: ค้นหาว่ามีชื่อผู้ใช้นี้อยู่หรือไม่ โดยใช้ BINARY ให้ตรงตามตัวพิมพ์เล็ก-ใหญ่
     * คืนค่า: array ผลการนับแถว (คีย์ 'num')
     */
    function getUserByUsername($username){
        try{
            $sql="SELECT COUNT(*) as num FROM user WHERE BINARY username=:username";
            $stmt=$this->db->prepare($sql);
            $stmt->bindParam(":username",$username);
            $stmt->execute();
            $result = $stmt->fetch();
            return $result;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * ฟังก์ชัน: getUser
     * ส่วนที่: การยืนยันตัวตนและการเข้าสู่ระบบ (Login Authentication)
     * หน้าที่: ค้นหาผู้ใช้ ตรวจสอบความถูกต้องของรหัสผ่านด้วย password_verify
     *          พร้อมทั้งตรวจสอบสถานะการถูกระงับ/แบน (is_banned)
     * พารามิเตอร์: $username, $password
     * คืนค่า: array ข้อมูลผู้ใช้หากรหัสผ่านถูกต้อง, false หากไม่ถูกต้อง
     */
    function getUser($username,$password){
        try{
            $sql="SELECT * FROM user WHERE BINARY username=:username";
            $stmt=$this->db->prepare($sql);
            
            $stmt->bindParam(":username",$username);
            $stmt->execute();
            $result=$stmt->fetch();
            
            if($result && password_verify($password, $result["password"])){
                $result['is_banned'] = $this->isUserBanned((int)$result['user_id']);
                return $result;
            }
            return false;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * ฟังก์ชัน: isUserBanned
     * ส่วนที่: ตรวจสอบสถานะการถูกระงับสิทธิ์ (Ban Check)
     * หน้าที่: ตรวจสอบจากตาราง user_bans ว่าผู้ใช้คนนี้ยังมีสถานะถูกแบน (unbanned_at IS NULL) อยู่หรือไม่
     * พารามิเตอร์: $userId (รหัสผู้ใช้)
     * คืนค่า: bool (true หากถูกแบน, false หากไม่ถูกแบน)
     */
    function isUserBanned($userId){
        try {
            $stmt = $this->db->prepare("SELECT ban_id FROM user_bans WHERE user_id = :user_id AND unbanned_at IS NULL LIMIT 1");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * ฟังก์ชัน: resetPasswordWithSecurityAnswer
     * ส่วนที่: ระบบกู้คืน/รีเซ็ตรหัสผ่าน (Forgot Password)
     * หน้าที่: ตรวจสอบคำตอบจากคำถามความปลอดภัยของผู้ใช้ หากถูกต้องจะอัปเดตรหัสผ่านใหม่เป็น Bcrypt Hash
     * พารามิเตอร์: $username, $answer, $new_password
     * คืนค่า: array ['success' => bool, 'message' => string]
     */
    function resetPasswordWithSecurityAnswer($username, $answer, $new_password) {
        try {
            $sql = "SELECT * FROM user WHERE BINARY username = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                return ["success" => false, "message" => "ไม่พบชื่อผู้ใช้นี้ในระบบ"];
            }
            
            if (empty($user['answer'])) {
                return ["success" => false, "message" => "บัญชีนี้ยังไม่ได้ตั้งคำถามความปลอดภัย"];
            }
            
            $normalized_answer = trim(mb_strtolower($answer));
            $isAnswerCorrect = false;

            if (password_verify($normalized_answer, $user['answer'])) {
                $isAnswerCorrect = true;
            } elseif (password_verify(trim($answer), $user['answer'])) {
                $isAnswerCorrect = true;
            } elseif (trim(mb_strtolower($user['answer'])) === $normalized_answer) {
                // Fallback for legacy plain text answer
                $isAnswerCorrect = true;
                // Automatically upgrade to hashed answer
                $upgrade_answer = password_hash($normalized_answer, PASSWORD_DEFAULT);
                $up_stmt = $this->db->prepare("UPDATE user SET answer = :answer WHERE user_id = :user_id");
                $up_stmt->bindParam(":answer", $upgrade_answer);
                $up_stmt->bindParam(":user_id", $user['user_id']);
                $up_stmt->execute();
            }

            if (!$isAnswerCorrect) {
                return ["success" => false, "message" => "คำตอบความปลอดภัยไม่ถูกต้อง"];
            }
            
            if (mb_strlen($new_password) < 6) {
                return ["success" => false, "message" => "รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร"];
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE user SET password = :password WHERE user_id = :user_id";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bindParam(":password", $hashed_password);
            $update_stmt->bindParam(":user_id", $user['user_id']);
            $update_stmt->execute();
            
            return ["success" => true, "message" => "เปลี่ยนรหัสผ่านสำเร็จเรียบร้อย"];
        } catch (PDOException $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * ฟังก์ชัน: getUsersPaginated
     * ส่วนที่: ระบบผู้ดูแลระบบ (Admin - User Management)
     * หน้าที่: ดึงรายชื่อผู้ใช้แบบแบ่งหน้า (Pagination) พร้อมสถานะการถูกแบน ตัวกรองค้นหา สิทธิ์ และสถานะ
     * พารามิเตอร์: $page, $limit, $search, $role, $status
     * คืนค่า: array รายชื่อผู้ใช้
     */
    function getUsersPaginated($page = 1, $limit = 10, $search = '', $role = '', $status = ''){
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT u.*, 
                           CASE WHEN ub.ban_id IS NOT NULL THEN 1 ELSE 0 END AS is_banned,
                           (
                               SELECT COUNT(*) 
                               FROM snippet_suspensions ss 
                               JOIN snippet s ON ss.snippet_id = s.snippet_id 
                               WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL
                           ) AS suspension_count,
                           (
                               SELECT COUNT(*) 
                               FROM snippet_suspensions ss 
                               JOIN snippet s ON ss.snippet_id = s.snippet_id 
                               WHERE s.user_id = u.user_id
                           ) AS total_suspension_history
                    FROM user u
                    LEFT JOIN user_bans ub ON u.user_id = ub.user_id AND ub.unbanned_at IS NULL
                    WHERE 1=1";
            
            if ($search !== '') {
                $sql .= " AND (u.firstname LIKE :search OR u.lastname LIKE :search OR u.username LIKE :search)";
            }
            if ($role !== '') {
                $sql .= " AND u.role = :role";
            }
            if ($status === 'banned') {
                $sql .= " AND ub.ban_id IS NOT NULL";
            } elseif ($status === 'active') {
                $sql .= " AND ub.ban_id IS NULL";
            } elseif ($status === 'warning') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) BETWEEN 1 AND 5";
            } elseif ($status === 'over_limit') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) > 5";
            } elseif ($status === 'has_suspended_posts') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) > 0";
            }

            $sql .= " ORDER BY u.user_id DESC LIMIT :offset, :limit";
            
            $stmt = $this->db->prepare($sql);
            
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            }
            if ($role !== '') {
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $users;
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ฟังก์ชัน: getTotalUsersCount
     * ส่วนที่: ระบบผู้ดูแลระบบ (Admin - User Management)
     * หน้าที่: นับจำนวนผู้ใช้ทั้งหมดตามเงื่อนไขค้นหา/สิทธิ์/สถานะ สำหรับคำนวณจำนวนหน้า (Total Pages)
     * พารามิเตอร์: $search, $role, $status
     * คืนค่า: int จำนวนผู้ใช้ทั้งหมดตามเงื่อนไข
     */
    function getTotalUsersCount($search = '', $role = '', $status = ''){
        try {
            $sql = "SELECT COUNT(*) as total 
                    FROM user u
                    LEFT JOIN user_bans ub ON u.user_id = ub.user_id AND ub.unbanned_at IS NULL
                    WHERE 1=1";
            
            if ($search !== '') {
                $sql .= " AND (u.firstname LIKE :search OR u.lastname LIKE :search OR u.username LIKE :search)";
            }
            if ($role !== '') {
                $sql .= " AND u.role = :role";
            }
            if ($status === 'banned') {
                $sql .= " AND ub.ban_id IS NOT NULL";
            } elseif ($status === 'active') {
                $sql .= " AND ub.ban_id IS NULL";
            } elseif ($status === 'warning') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) BETWEEN 1 AND 5";
            } elseif ($status === 'over_limit') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) > 5";
            } elseif ($status === 'has_suspended_posts') {
                $sql .= " AND (SELECT COUNT(*) FROM snippet_suspensions ss JOIN snippet s ON ss.snippet_id = s.snippet_id WHERE s.user_id = u.user_id AND ss.unsuspended_at IS NULL) > 0";
            }

            $stmt = $this->db->prepare($sql);
            
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            }
            if ($role !== '') {
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }


    /**
     * ฟังก์ชัน: getUserStats
     * ส่วนที่: แดชบอร์ดและสถิติ (Admin Dashboard & Stats)
     * หน้าที่: ดึงสรุปสถิติจำนวนผู้ใช้ทั้งหมด, จำนวนผู้ใช้ที่สมัครในวันนี้, และจำนวนผู้ใช้ที่ถูกแบน
     * คืนค่า: array ['total' => int, 'today' => int, 'banned' => int]
     */
    function getUserStats(){
        try {
            // Total users
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM user");
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Registered today
            $stmt = $this->db->query("SELECT COUNT(*) as total_today FROM user WHERE DATE(created_at) = CURDATE()");
            $today = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_today'];

            // Banned users (from user_bans with active ban)
            $stmt = $this->db->query("SELECT COUNT(DISTINCT user_id) as total_banned FROM user_bans WHERE unbanned_at IS NULL");
            $banned = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_banned'];

            // Users with at least 1 currently suspended snippet
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT s.user_id) as total_suspended 
                 FROM snippet_suspensions ss 
                 JOIN snippet s ON ss.snippet_id = s.snippet_id
                 WHERE ss.unsuspended_at IS NULL"
            );
            $suspendedUsers = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_suspended'];

            // Users whose active suspended snippets > 5
            $stmt = $this->db->query(
                "SELECT COUNT(*) as total_over_limit FROM (
                    SELECT s.user_id, COUNT(*) as cnt 
                    FROM snippet_suspensions ss 
                    JOIN snippet s ON ss.snippet_id = s.snippet_id 
                    WHERE ss.unsuspended_at IS NULL
                    GROUP BY s.user_id 
                    HAVING cnt > 5
                ) as over_tbl"
            );
            $overLimit = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_over_limit'];

            return [
                'total' => $total,
                'today' => $today,
                'banned' => $banned,
                'suspended_users' => $suspendedUsers,
                'violated' => $suspendedUsers,
                'over_limit' => $overLimit
            ];
        } catch (PDOException $e) {
            return [
                'total' => 0,
                'today' => 0,
                'banned' => 0,
                'suspended_users' => 0,
                'violated' => 0,
                'over_limit' => 0
            ];
        }
    }

    /**
     * ฟังก์ชัน: banUser
     * ส่วนที่: ระบบการจัดการผู้ใช้โดยผู้ดูแลระบบ (Admin - Ban System)
     * หน้าที่: สั่งแบนผู้ใช้งาน โดยตรวจสอบสิทธิ์ผู้ดำเนินการ ป้องกันการแบนแอดมิน และบันทึกประวัติการแบนลงตาราง user_bans
     * พารามิเตอร์: 
     *   - $userId   : รหัสผู้ใช้ที่ต้องการแบน
     *   - $adminId  : รหัสแอดมินผู้ดำเนินการ หรือชื่อระบบ
     *   - $reason   : เหตุผลในการสั่งแบน
     * คืนค่า: array ['success' => bool, 'message' => string]
     */
    function banUser($userId, $adminId, $reason){
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Please specify a reason for banning.'];
        }

        try {
            // 1. ตรวจสอบว่าผู้ดำเนินการเป็นแอดมินจริง หรือเป็นระบบอัตโนมัติ
            $adminBanned = 'System';
            if (is_numeric($adminId)) {
                $adminStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :admin_id");
                $adminStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
                $adminStmt->execute();
                $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

                if (!$admin || $admin['role'] !== 'admin') {
                    return ['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้ (สำหรับผู้ดูแลระบบเท่านั้น)'];
                }

                $adminBanned = $admin['username'];
            } elseif (is_string($adminId) && $adminId !== '') {
                $adminBanned = $adminId;
            }

            // 2. ตรวจสอบว่าพบบัญชีผู้ใช้เป้าหมายหรือไม่
            $targetStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :user_id");
            $targetStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $targetStmt->execute();
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                return ['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้เป้าหมาย'];
            }

            // 3. ป้องกันไม่ให้แบนบัญชีแอดมินด้วยกัน
            if ($target['role'] === 'admin') {
                return ['success' => false, 'message' => 'ไม่สามารถแบนบัญชีผู้ดูแลระบบได้'];
            }

            // 4. ตรวจสอบว่าถูกแบนอยู่ก่อนแล้วหรือไม่
            if ($this->isUserBanned($userId)) {
                return ['success' => false, 'message' => 'บัญชีนี้ถูกแบนอยู่แล้ว'];
            }

            // 5. บันทึกข้อมูลการแบนลงตาราง user_bans
            $insertStmt = $this->db->prepare(
                "INSERT INTO user_bans (user_id, admin_banned, reason, banned_at)
                 VALUES (:user_id, :admin_banned, :reason, NOW())"
            );
            $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindParam(':admin_banned', $adminBanned, PDO::PARAM_STR);
            $insertStmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $insertStmt->execute();

            return ['success' => true, 'message' => 'แบนบัญชีผู้ใช้เรียบร้อยแล้ว'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการแบนบัญชี: ' . $e->getMessage()];
        }
    }

    /**
     * ฟังก์ชัน: unbanUser
     * ส่วนที่: ระบบการจัดการผู้ใช้โดยผู้ดูแลระบบ (Admin - Unban System)
     * หน้าที่: สั่งปลดแบนผู้ใช้งาน โดยตรวจสอบสิทธิ์แอดมิน และบันทึกวันเวลาและชื่อผู้ปลดแบนลงใน user_bans
     * พารามิเตอร์: 
     *   - $userId   : รหัสผู้ใช้ที่ต้องการปลดแบน
     *   - $adminId  : รหัสแอดมินผู้ดำเนินการ
     * คืนค่า: array ['success' => bool, 'message' => string]
     */
    function unbanUser($userId, $adminId){
        try {
            // 1. ตรวจสอบสิทธิ์ผู้ดำเนินการว่าต้องเป็นแอดมิน
            $adminStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :admin_id");
            $adminStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
            $adminStmt->execute();
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || $admin['role'] !== 'admin') {
                return ['success' => false, 'message' => 'คุณไม่มีสิทธิ์ในการดำเนินการนี้ (สำหรับผู้ดูแลระบบเท่านั้น)'];
            }

            $adminUnbanned = $admin['username'];

            // 2. ตรวจสอบข้อมูลผู้ใช้เป้าหมาย
            $targetStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :user_id");
            $targetStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $targetStmt->execute();
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                return ['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้เป้าหมาย'];
            }

            // 3. ตรวจสอบว่าผู้ใช้ติดสถานะถูกแบนอยู่หรือไม่
            if (!$this->isUserBanned($userId)) {
                return ['success' => false, 'message' => 'บัญชีนี้ไม่ได้ถูกแบน'];
            }

            // 4. บันทึกการปลดแบน (อัปเดต unbanned_at และ admin_unbanned)
            $banUpdateStmt = $this->db->prepare(
                "UPDATE user_bans 
                 SET unbanned_at = NOW(), admin_unbanned = :admin_unbanned 
                 WHERE user_id = :user_id AND unbanned_at IS NULL"
            );
            $banUpdateStmt->bindParam(':admin_unbanned', $adminUnbanned, PDO::PARAM_STR);
            $banUpdateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $banUpdateStmt->execute();

            return ['success' => true, 'message' => 'ยกเลิกการแบนผู้ใช้ ' . $target['username'] . ' เรียบร้อยแล้ว'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'เกิดข้อผิดพลาดในการยกเลิกการแบนบัญชี: ' . $e->getMessage()];
        }
    }

    /**
     * ฟังก์ชัน: getUserBanHistory
     * ส่วนที่: ระบบประวัติการลงโทษ (Ban History)
     * หน้าที่: ดึงประวัติการแบนและปลดแบนทั้งหมดของสมาชิกรายบุคคล
     * พารามิเตอร์: $userId (รหัสผู้ใช้)
     * คืนค่า: array รายการประวัติการแบน
     */
    function getUserBanHistory($userId){
        try {
            $sql = "SELECT ub.ban_id, ub.user_id, ub.admin_banned, ub.reason, ub.banned_at, ub.unbanned_at, ub.admin_unbanned,
                           u.username AS banned_username, u.firstname AS banned_firstname, u.lastname AS banned_lastname,
                           ub.admin_banned AS admin_username,
                           ub.admin_unbanned AS unbanned_by_username
                    FROM user_bans ub
                    JOIN user u ON ub.user_id = u.user_id
                    WHERE ub.user_id = :user_id
                    ORDER BY ub.banned_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ฟังก์ชัน: getAllBanHistory
     * ส่วนที่: ระบบประวัติการลงโทษโดยรวม (System-wide Ban History)
     * หน้าที่: ดึงประวัติการแบนและปลดแบนทั้งหมดในระบบเพื่อแสดงในหน้า Admin
     * คืนค่า: array รายการประวัติการแบนทั้งหมด
     */
    function getAllBanHistory(){
        try {
            $sql = "SELECT ub.ban_id, ub.user_id, ub.admin_banned, ub.reason, ub.banned_at, ub.unbanned_at, ub.admin_unbanned,
                           u.username AS banned_username,
                           ub.admin_banned AS admin_username,
                           ub.admin_banned AS banned_by_username,
                           ub.admin_unbanned AS unbanned_by_username
                    FROM user_bans ub
                    JOIN user u ON ub.user_id = u.user_id
                    ORDER BY ub.banned_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * ฟังก์ชัน: getUserSuspensionCount
     * ส่วนที่: ระบบนับจำนวนครั้งที่โพสต์ถูกระงับของผู้ใช้ (User Post Suspension System)
     * หน้าที่: ดึงจำนวนครั้งที่โพสต์ (Snippet) ของผู้ใช้กำลังถูกระงับอยู่ในปัจจุบัน (ไม่นับรายการที่ยกเลิกการระงับแล้ว)
     * พารามิเตอร์: $userId
     * คืนค่า: int
     */
    function getUserSuspensionCount($userId){
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as total 
                 FROM snippet_suspensions ss
                 JOIN snippet s ON ss.snippet_id = s.snippet_id
                 WHERE s.user_id = :user_id AND ss.unsuspended_at IS NULL"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? (int)$row['total'] : 0;
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * ฟังก์ชัน: getUserSuspensions
     * ส่วนที่: ระบบประวัติโพสต์ที่ถูกระงับของผู้ใช้ (User Suspended Snippets History)
     * หน้าที่: ดึงรายการประวัติการถูกระงับโพสต์ทั้งหมดของผู้ใช้
     * พารามิเตอร์: $userId
     * คืนค่า: array
     */
    function getUserSuspensions($userId){
        try {
            $stmt = $this->db->prepare(
                "SELECT ss.suspension_id, ss.snippet_id, ss.admin_suspended, ss.reason, 
                        ss.suspended_at, ss.admin_unsuspended, ss.unsuspended_at,
                        s.title, s.title as snippet_title, s.visibility, s.is_suspended, u.username
                 FROM snippet_suspensions ss
                 JOIN snippet s ON ss.snippet_id = s.snippet_id
                 JOIN user u ON s.user_id = u.user_id
                 WHERE s.user_id = :user_id
                 ORDER BY ss.suspended_at DESC, ss.suspension_id DESC"
            );
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>