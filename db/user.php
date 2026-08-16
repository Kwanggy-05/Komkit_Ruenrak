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
                $sql="INSERT INTO user(firstname,lastname,username,password,question,answer)
                VALUES(:firstname,:lastname,:username,:password,:question,:answer)";
                $stmt=$this->db->prepare($sql);
                $stmt->bindParam(":firstname",$firstname);
                $stmt->bindParam(":lastname",$lastname);
                $stmt->bindParam(":username",$username);
                $stmt->bindParam(":password",$new_password);
                $stmt->bindParam(":question",$question);
                $stmt->bindParam(":answer",$answer);
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
            
            if (trim(mb_strtolower($user['answer'])) !== trim(mb_strtolower($answer))) {
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

    function getUsersPaginated($page = 1, $limit = 10, $search = '', $role = ''){
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

            $sql .= " ORDER BY user_id DESC LIMIT :offset, :limit";
            
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
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    function getTotalUsersCount($search = '', $role = ''){
        try {
            $sql = "SELECT COUNT(*) as total FROM user WHERE 1=1";
            
            if ($search !== '') {
                $sql .= " AND (firstname LIKE :search OR lastname LIKE :search OR username LIKE :search)";
            }
            if ($role !== '') {
                $sql .= " AND role = :role";
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

            return [
                'total' => $total,
                'today' => $today
            ];
        } catch (PDOException $e) {
            return [
                'total' => 0,
                'today' => 0
            ];
        }
    }
}
?>