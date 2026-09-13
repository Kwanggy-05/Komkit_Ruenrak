<?php
class User{
    private $db;
    function __construct($con){
        $this->db=$con;
    }
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
    function getUser($username,$password){
        try{
            $sql="SELECT * FROM user WHERE BINARY username=:username";
            $stmt=$this->db->prepare($sql);
            
            $stmt->bindParam(":username",$username);
            $stmt->execute();
            $result=$stmt->fetch();
            
            if($result){
                $isPasswordCorrect = false;
                $isLegacyMd5 = false;
                
                if(password_verify($password, $result["password"])){
                    $isPasswordCorrect = true;
                } else {
                    // Fallback for legacy MD5 users to migrate seamlessly
                    $old_md5 = md5($password.$username);
                    if($result["password"] === $old_md5){
                        $isPasswordCorrect = true;
                        $isLegacyMd5 = true;
                    }
                }
                
                if($isPasswordCorrect){
                    if($isLegacyMd5){
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE user SET password = :password WHERE user_id = :user_id";
                        $update_stmt = $this->db->prepare($update_sql);
                        $update_stmt->bindParam(":password", $new_hash);
                        $update_stmt->bindParam(":user_id", $result["user_id"]);
                        $update_stmt->execute();
                    }
                    return $result;
                }
            }
            return false;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function resetPasswordWithSecurityAnswer($username, $answer, $new_password) {
        try {
            $sql = "SELECT * FROM user WHERE BINARY username = :username";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            $user = $stmt->fetch();
            
            if (!$user) {
                return ["success" => false, "message" => "Username not found"];
            }
            
            if (empty($user['answer'])) {
                return ["success" => false, "message" => "This account does not have a security question set"];
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
                return ["success" => false, "message" => "Incorrect security question answer"];
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE user SET password = :password WHERE user_id = :user_id";
            $update_stmt = $this->db->prepare($update_sql);
            $update_stmt->bindParam(":password", $hashed_password);
            $update_stmt->bindParam(":user_id", $user['user_id']);
            $update_stmt->execute();
            
            return ["success" => true, "message" => "Password changed successfully."];
        } catch (PDOException $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    function getUsersPaginated($page = 1, $limit = 10, $search = '', $role = '', $status = ''){
        try {
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT * FROM user WHERE 1=1";
            
            if ($search !== '') {
                $sql .= " AND (firstname LIKE :search OR lastname LIKE :search OR username LIKE :search)";
            }
            if ($role !== '') {
                $sql .= " AND role = :role";
            }
            if ($status !== '') {
                $sql .= " AND account_status = :status";
            }

            $sql .= " ORDER BY user_id DESC LIMIT :offset, :limit";
            
            $stmt = $this->db->prepare($sql);
            
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            }
            if ($role !== '') {
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            }
            if ($status !== '') {
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            }
            
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    function getTotalUsersCount($search = '', $role = '', $status = ''){
        try {
            $sql = "SELECT COUNT(*) as total FROM user WHERE 1=1";
            
            if ($search !== '') {
                $sql .= " AND (firstname LIKE :search OR lastname LIKE :search OR username LIKE :search)";
            }
            if ($role !== '') {
                $sql .= " AND role = :role";
            }
            if ($status !== '') {
                $sql .= " AND account_status = :status";
            }

            $stmt = $this->db->prepare($sql);
            
            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $stmt->bindParam(':search', $searchTerm, PDO::PARAM_STR);
            }
            if ($role !== '') {
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
            }
            if ($status !== '') {
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            }
            
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        } catch (PDOException $e) {
            return 0;
        }
    }

    function adminResetPassword($userId, $newPassword){
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE user SET password = :password WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            return false;
        }
    }

    function getUserStats(){
        try {
            // Total users
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM user");
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Registered today
            $stmt = $this->db->query("SELECT COUNT(*) as total_today FROM user WHERE DATE(created_at) = CURDATE()");
            $today = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_today'];

            // Banned users
            $stmt = $this->db->query("SELECT COUNT(*) as total_banned FROM user WHERE account_status = 'banned'");
            $banned = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total_banned'];

            return [
                'total' => $total,
                'today' => $today,
                'banned' => $banned
            ];
        } catch (PDOException $e) {
            return [
                'total' => 0,
                'today' => 0,
                'banned' => 0
            ];
        }
    }

    /**
     * Ban a user permanently
     *
     * @param int $userId Target user ID to ban
     * @param int $adminId Admin ID performing the ban
     * @param string $reason Reason for ban
     * @return array ['success' => bool, 'message' => string]
     */
    function banUser($userId, $adminId, $reason){
        $reason = trim($reason);
        if ($reason === '') {
            return ['success' => false, 'message' => 'Please specify a reason for banning.'];
        }

        try {
            // 1. Verify operator is admin
            $adminStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :admin_id");
            $adminStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
            $adminStmt->execute();
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || $admin['role'] !== 'admin') {
                return ['success' => false, 'message' => 'You do not have permission to perform this action (Admin only).'];
            }

            $adminBanned = $admin['username'];

            // 2. Verify target user
            $targetStmt = $this->db->prepare("SELECT user_id, username, role, account_status FROM user WHERE user_id = :user_id");
            $targetStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $targetStmt->execute();
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                return ['success' => false, 'message' => 'Target user account not found.'];
            }

            // 3. Cannot ban admin
            if ($target['role'] === 'admin') {
                return ['success' => false, 'message' => 'Cannot ban an Admin account.'];
            }

            // 4. Cannot ban already banned user
            if ($target['account_status'] === 'banned') {
                return ['success' => false, 'message' => 'This account is already banned.'];
            }

            // 5. Execute transaction (insert user_bans and update user.account_status)
            $this->db->beginTransaction();

            $insertStmt = $this->db->prepare(
                "INSERT INTO user_bans (user_id, admin_banned, reason, banned_at)
                 VALUES (:user_id, :admin_banned, :reason, NOW())"
            );
            $insertStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $insertStmt->bindParam(':admin_banned', $adminBanned, PDO::PARAM_STR);
            $insertStmt->bindParam(':reason', $reason, PDO::PARAM_STR);
            $insertStmt->execute();

            $updateStmt = $this->db->prepare(
                "UPDATE user SET account_status = 'banned' WHERE user_id = :user_id"
            );
            $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $updateStmt->execute();

            $this->db->commit();
            return ['success' => true, 'message' => 'Account banned successfully.'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error banning account: ' . $e->getMessage()];
        }
    }

    /**
     * Unban a user
     *
     * @param int $userId Target user ID to unban
     * @param int $adminId Admin ID performing the unban
     * @return array ['success' => bool, 'message' => string]
     */
    function unbanUser($userId, $adminId){
        try {
            // 1. Verify operator is admin
            $adminStmt = $this->db->prepare("SELECT user_id, username, role FROM user WHERE user_id = :admin_id");
            $adminStmt->bindParam(':admin_id', $adminId, PDO::PARAM_INT);
            $adminStmt->execute();
            $admin = $adminStmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || $admin['role'] !== 'admin') {
                return ['success' => false, 'message' => 'You do not have permission to perform this action (Admin only).'];
            }

            $adminUnbanned = $admin['username'];

            // 2. Verify target user
            $targetStmt = $this->db->prepare("SELECT user_id, username, role, account_status FROM user WHERE user_id = :user_id");
            $targetStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $targetStmt->execute();
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {
                return ['success' => false, 'message' => 'Target user account not found.'];
            }

            // 3. Check if target user is currently banned
            if ($target['account_status'] !== 'banned') {
                return ['success' => false, 'message' => 'This account is not banned.'];
            }

            $this->db->beginTransaction();

            // 4. Update account_status to active
            $updateStmt = $this->db->prepare(
                "UPDATE user SET account_status = 'active' WHERE user_id = :user_id"
            );
            $updateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $updateStmt->execute();

            // 5. Update user_bans to record unbanned_at and admin_unbanned
            $banUpdateStmt = $this->db->prepare(
                "UPDATE user_bans 
                 SET unbanned_at = NOW(), admin_unbanned = :admin_unbanned 
                 WHERE user_id = :user_id AND unbanned_at IS NULL 
                 ORDER BY ban_id DESC LIMIT 1"
            );
            $banUpdateStmt->bindParam(':admin_unbanned', $adminUnbanned, PDO::PARAM_STR);
            $banUpdateStmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $banUpdateStmt->execute();

            $this->db->commit();

            return ['success' => true, 'message' => 'User ' . $target['username'] . ' has been unbanned successfully.'];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Error unbanning account: ' . $e->getMessage()];
        }
    }

    /**
     * Get ban history for a specific user
     *
     * @param int $userId
     * @return array
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
     * Get all ban history in the system
     *
     * @return array
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
}
?>