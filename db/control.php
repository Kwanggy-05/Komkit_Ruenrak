<?php
class Control{
    private $db;
    function __construct($con){
        $this->db=$con;
    }

    function getLanguage(){
        try{
            $sql = "SELECT * FROM language";
            $result=$this->db->query($sql);
            return $result;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getPublicLanguages(){
        try{
            $sql = "SELECT DISTINCT b.language_id, b.language_name
                    FROM language b
                    INNER JOIN snippet a ON b.language_id = a.language_id
                    WHERE a.visibility IN ('public','protected')
                    AND (a.deleted = 0 OR a.deleted IS NULL)
                    AND (a.is_suspended = 0 OR a.is_suspended IS NULL)
                    ORDER BY b.language_name ASC";
            $result = $this->db->query($sql);
            return $result;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getAllLanguagesWithSnippetCounts(){
        try{
            $sql = "SELECT l.language_id, l.language_name,
                           COUNT(s.snippet_id) AS snippet_count
                    FROM language l
                    LEFT JOIN snippet s ON l.language_id = s.language_id
                         AND s.visibility IN ('public','protected')
                         AND (s.deleted = 0 OR s.deleted IS NULL)
                         AND (s.is_suspended = 0 OR s.is_suspended IS NULL)
                    GROUP BY l.language_id, l.language_name
                    ORDER BY l.language_name ASC";
            $result = $this->db->query($sql);
            return $result;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getSnippet($current_user_id = 0){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE (a.visibility IN ('public','protected') OR a.user_id = :user_id)
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)
                GROUP BY a.snippet_id
                ORDER BY a.snippet_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getUserSnippets($user_id, $keyword = ''){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE a.user_id = :user_id 
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";

            if($keyword !== ''){
                $sql .= " AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            }

            $sql .= " GROUP BY a.snippet_id
                ORDER BY a.snippet_id DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if($keyword !== ''){
                $searchTerm = '%' . $keyword . '%';
                $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function searchSnippets($keyword, $language_id = null){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
                COUNT(DISTINCT sl.user_id) AS like_count
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                LEFT JOIN snippet_like sl ON a.snippet_id = sl.snippet_id
                WHERE a.visibility IN ('public','protected')
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)
                AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            
            if($language_id && $language_id !== ''){
                $sql .= " AND a.language_id = :language_id";
            }
            
            $sql .= " GROUP BY a.snippet_id
                ORDER BY like_count DESC, a.snippet_id DESC";
            $stmt = $this->db->prepare($sql);
            $searchTerm = '%' . $keyword . '%';
            $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            if($language_id && $language_id !== ''){
                $stmt->bindParam(':language_id', $language_id, PDO::PARAM_INT);
            }
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getSnippetsByLanguage($language_id){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE a.visibility IN ('public','protected')
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)
                AND a.language_id = :language_id
                GROUP BY a.snippet_id
                ORDER BY a.snippet_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':language_id', $language_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function insert($title,$source_code,$description,$visibility,$image_path,$language_id,$tags, $user_id){
        try{
            $this->db->beginTransaction();

            $sql="INSERT INTO snippet(user_id, title,source_code,description,visibility,image_path,language_id)
            VALUES (:user_id, :title,:source_code,:description,:visibility,:image_path,:language_id)";
            $stmt=$this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":title",$title);
            $stmt->bindParam(":source_code",$source_code);
            $stmt->bindParam(":description",$description);
            $stmt->bindParam(":visibility",$visibility);
            $stmt->bindParam(":image_path",$image_path);
            $stmt->bindParam(":language_id",$language_id);
            $stmt->execute();

            $snippet_id = $this->db->lastInsertId();

            if(!empty($tags)){
                $tagNames = array_filter(array_map('trim', explode(',', $tags)));
                $tagNames = array_unique($tagNames);

                foreach($tagNames as $tagName){
                    if($tagName === ''){
                        continue;
                    }

                    $tag_id = $this->getTagIdByName($tagName);
                    if(!$tag_id){
                        $tag_id = $this->createTag($tagName);
                    }

                    if($tag_id){
                        $this->attachTagToSnippet($snippet_id, $tag_id);
                    }
                }
            }

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            $this->db->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    function getSnippetById($snippet_id, $current_user_id = 0, $include_deleted = false){
        try{
            $deleted_condition = $include_deleted 
                ? "(s.deleted = 0 OR s.deleted IS NULL OR (s.deleted = 1 AND s.user_id = :user_id))"
                : "(s.deleted = 0 OR s.deleted IS NULL)";
            
            $sql = "SELECT s.*, l.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
                MAX(susp.reason) as suspension_reason,
                MAX(susp.suspended_at) as suspension_time,
                MAX(susp.admin_suspended) as admin_suspended
                FROM snippet s
                INNER JOIN language l ON s.language_id = l.language_id
                LEFT JOIN snippet_tag st ON s.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                LEFT JOIN snippet_suspensions susp ON (s.snippet_id = susp.snippet_id AND susp.unsuspended_at IS NULL)
                WHERE s.snippet_id = :snippet_id
                AND (s.visibility IN ('public','protected') OR s.user_id = :user_id)
                AND (s.is_suspended = 0 OR s.is_suspended IS NULL OR s.user_id = :user_id)
                AND $deleted_condition
                GROUP BY s.snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $current_user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function updateSnippet($snippet_id, $title, $source_code, $description, $visibility, $image_path, $language_id, $tags, $user_id){
        try{
            $this->db->beginTransaction();

            $sql = "UPDATE snippet SET title = :title, source_code = :source_code, description = :description, visibility = :visibility, image_path = :image_path, language_id = :language_id WHERE snippet_id = :snippet_id AND user_id = :user_id AND (is_suspended = 0 OR is_suspended IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":source_code", $source_code);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":visibility", $visibility);
            $stmt->bindParam(":image_path", $image_path);
            $stmt->bindParam(":language_id", $language_id);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $sql = "DELETE FROM snippet_tag WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();

            if(!empty($tags)){
                $tagNames = array_filter(array_map('trim', explode(',', $tags)));
                $tagNames = array_unique($tagNames);

                foreach($tagNames as $tagName){
                    if($tagName === ''){
                        continue;
                    }

                    $tag_id = $this->getTagIdByName($tagName);
                    if(!$tag_id){
                        $tag_id = $this->createTag($tagName);
                    }

                    if($tag_id){
                        $this->attachTagToSnippet($snippet_id, $tag_id);
                    }
                }
            }

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            $this->db->rollBack();
            echo $e->getMessage();
            return false;
        }
    }

    function deleteSnippet($snippet_id, $user_id){
        try{
            $this->db->beginTransaction();

            // Verify that snippet exists and belongs to user (and is not suspended by admin)
            $checkSql = "SELECT snippet_id FROM snippet WHERE snippet_id = :snippet_id AND user_id = :user_id AND (is_suspended = 0 OR is_suspended IS NULL)";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $checkStmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $checkStmt->execute();

            if($checkStmt->rowCount() === 0){
                $this->db->rollBack();
                return false;
            }

            // Remove child records from dependent tables first
            $sqlTag = "DELETE FROM snippet_tag WHERE snippet_id = :snippet_id";
            $stmtTag = $this->db->prepare($sqlTag);
            $stmtTag->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtTag->execute();

            $sqlBk = "DELETE FROM bookmarks WHERE snippet_id = :snippet_id";
            $stmtBk = $this->db->prepare($sqlBk);
            $stmtBk->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtBk->execute();

            $sqlLike = "DELETE FROM snippet_like WHERE snippet_id = :snippet_id";
            $stmtLike = $this->db->prepare($sqlLike);
            $stmtLike->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtLike->execute();

            // Finally delete snippet row permanently from database
            $sql = "DELETE FROM snippet WHERE snippet_id = :snippet_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            if($this->db->inTransaction()){
                $this->db->rollBack();
            }
            return false;
        }
    }

    function softDeleteSnippet($snippet_id, $user_id){
        try{
            $sql = "UPDATE snippet SET deleted = 1 WHERE snippet_id = :snippet_id AND user_id = :user_id AND (is_suspended = 0 OR is_suspended IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function restoreSnippet($snippet_id, $user_id){
        try{
            $sql = "UPDATE snippet SET deleted = 0 WHERE snippet_id = :snippet_id AND user_id = :user_id AND (is_suspended = 0 OR is_suspended IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    // ===== Pagination Methods =====

    function getTotalUserSnippetCount($user_id, $keyword = '', $only_own = false, $language_id = ''){
        try{
            $sql = "SELECT COUNT(DISTINCT a.snippet_id) as total FROM snippet a
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE (a.deleted = 0 OR a.deleted IS NULL)";

            if ($only_own) {
                $sql .= " AND a.user_id = :user_id";
            } else {
                $sql .= " AND a.visibility IN ('public', 'protected') AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";
            }

            if($keyword !== ''){
                $sql .= " AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            }

            if($language_id !== ''){
                $sql .= " AND a.language_id = :language_id";
            }

            $stmt = $this->db->prepare($sql);
            if ($only_own) {
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            }

            if($keyword !== ''){
                $searchTerm = '%' . $keyword . '%';
                $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            }

            if($language_id !== ''){
                $stmt->bindParam(':language_id', $language_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        }catch(PDOException $e){
            return 0;
        }
    }

    function getTotalBookmarkedSnippetCount($user_id){
        try{
            $sql = "SELECT COUNT(*) as total FROM bookmarks bk
                INNER JOIN snippet a ON bk.snippet_id = a.snippet_id
                WHERE bk.user_id = :user_id 
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        }catch(PDOException $e){
            return 0;
        }
    }

    function getUserSnippetsPaginated($user_id, $page = 1, $limit = 10, $keyword = '', $only_own = false, $language_id = ''){
        try{
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
                COUNT(DISTINCT sl.user_id) AS like_count,
                MAX(susp.reason) as suspension_reason,
                MAX(susp.suspended_at) as suspension_time
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                LEFT JOIN snippet_like sl ON a.snippet_id = sl.snippet_id
                LEFT JOIN snippet_suspensions susp ON (a.snippet_id = susp.snippet_id AND susp.unsuspended_at IS NULL)
                WHERE (a.deleted = 0 OR a.deleted IS NULL)";

            if ($only_own) {
                $sql .= " AND a.user_id = :user_id";
            } else {
                $sql .= " AND a.visibility IN ('public', 'protected') AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";
            }

            if($keyword !== ''){
                $sql .= " AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            }

            if($language_id !== ''){
                $sql .= " AND a.language_id = :language_id";
            }

            $sql .= " GROUP BY a.snippet_id";
            
            if (!$only_own) {
                $sql .= " ORDER BY like_count DESC, a.snippet_id DESC";
            } else {
                $sql .= " ORDER BY a.snippet_id DESC";
            }
            
            $sql .= " LIMIT :offset, :limit";

            $stmt = $this->db->prepare($sql);
            if ($only_own) {
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            }
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

            if($keyword !== ''){
                $searchTerm = '%' . $keyword . '%';
                $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            }

            if($language_id !== ''){
                $stmt->bindParam(':language_id', $language_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getBookmarkedSnippetsPaginated($user_id, $page = 1, $limit = 10){
        try{
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM bookmarks bk
                INNER JOIN snippet a ON bk.snippet_id = a.snippet_id
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE bk.user_id = :user_id 
                AND (a.deleted = 0 OR a.deleted IS NULL)
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)
                GROUP BY a.snippet_id
                ORDER BY bk.bookmarked_at DESC
                LIMIT :offset, :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getTotalDeletedUserSnippetCount($user_id, $keyword = ''){
        try{
            $sql = "SELECT COUNT(DISTINCT a.snippet_id) as total FROM snippet a
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE a.user_id = :user_id 
                AND a.deleted = 1 
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";

            if($keyword !== ''){
                $sql .= " AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);

            if($keyword !== ''){
                $searchTerm = '%' . $keyword . '%';
                $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            }

            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['total'];
        }catch(PDOException $e){
            return 0;
        }
    }

    function getDeletedUserSnippetsPaginated($user_id, $page = 1, $limit = 10, $keyword = ''){
        try{
            $page = max(1, (int)$page);
            $limit = max(1, (int)$limit);
            $offset = ($page - 1) * $limit;

            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE a.user_id = :user_id 
                AND a.deleted = 1 
                AND (a.is_suspended = 0 OR a.is_suspended IS NULL)";

            if($keyword !== ''){
                $sql .= " AND (
                    a.title LIKE :keyword
                    OR a.description LIKE :keyword
                    OR a.source_code LIKE :keyword
                    OR t.tag_name LIKE :keyword
                )";
            }

            $sql .= " GROUP BY a.snippet_id
                ORDER BY a.snippet_id DESC
                LIMIT :offset, :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);

            if($keyword !== ''){
                $searchTerm = '%' . $keyword . '%';
                $stmt->bindParam(':keyword', $searchTerm, PDO::PARAM_STR);
            }

            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    private function getTagIdByName($tag_name){
        $sql = "SELECT tag_id FROM tag WHERE tag_name = :tag_name LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":tag_name", $tag_name);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['tag_id'] : false;
    }

    private function createTag($tag_name){
        $sql = "INSERT INTO tag(tag_name) VALUES (:tag_name)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":tag_name", $tag_name);
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    private function attachTagToSnippet($snippet_id, $tag_id){
        $sql = "INSERT IGNORE INTO snippet_tag(snippet_id, tag_id) VALUES (:snippet_id, :tag_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(":snippet_id", $snippet_id);
        $stmt->bindParam(":tag_id", $tag_id);
        $stmt->execute();
    }

    function Tag(){
        try{
            $sql="SELECT 
            s.snippet_id,
            s.title, 
            t.tag_id,
            t.tag_name      
            FROM snippet s
            INNER JOIN snippet_tag st ON s.snippet_id = st.snippet_id
            INNER JOIN tag t ON st.tag_id = t.tag_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;

        }
        
    
    }

    function getAllTags(){
        try{
            $sql = "SELECT * FROM tag ORDER BY tag_name ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            echo $e->getMessage();
            return [];
        }
    }

    // ===== Like Methods =====

    function toggleLike($user_id, $snippet_id){
        try{
            if($this->isLiked($user_id, $snippet_id)){
                $sql = "DELETE FROM snippet_like WHERE user_id = :user_id AND snippet_id = :snippet_id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
                $stmt->execute();
                $newCount = $this->getLikeCount($snippet_id);
                return ['status' => 'removed', 'count' => $newCount];
            }else{
                $sql = "INSERT INTO snippet_like(user_id, snippet_id) VALUES(:user_id, :snippet_id)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
                $stmt->execute();
                $newCount = $this->getLikeCount($snippet_id);
                return ['status' => 'added', 'count' => $newCount];
            }
        }catch(PDOException $e){
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    function isLiked($user_id, $snippet_id){
        try{
            $sql = "SELECT COUNT(*) as cnt FROM snippet_like WHERE user_id = :user_id AND snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['cnt'] > 0;
        }catch(PDOException $e){
            return false;
        }
    }

    function getLikeCount($snippet_id){
        try{
            $sql = "SELECT COUNT(*) as cnt FROM snippet_like WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$row['cnt'];
        }catch(PDOException $e){
            return 0;
        }
    }

    function getLikedSnippetIds($user_id){
        try{
            $sql = "SELECT snippet_id FROM snippet_like WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $ids = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $ids[] = (int)$row['snippet_id'];
            }
            return $ids;
        }catch(PDOException $e){
            return [];
        }
    }

    function getLikeCountsForSnippets($snippetIds){
        $counts = [];
        if(empty($snippetIds)) return $counts;
        try{
            $placeholders = implode(',', array_fill(0, count($snippetIds), '?'));
            $sql = "SELECT snippet_id, COUNT(*) as cnt FROM snippet_like WHERE snippet_id IN ($placeholders) GROUP BY snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($snippetIds));
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $counts[(int)$row['snippet_id']] = (int)$row['cnt'];
            }
            return $counts;
        }catch(PDOException $e){
            return $counts;
        }
    }

    // ===== Bookmark Methods =====

    function toggleBookmark($user_id, $snippet_id){
        try{
            if($this->isBookmarked($user_id, $snippet_id)){
                $sql = "DELETE FROM bookmarks WHERE user_id = :user_id AND snippet_id = :snippet_id";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
                $stmt->execute();
                return ['status' => 'removed'];
            }else{
                $sql = "INSERT INTO bookmarks(user_id, snippet_id) VALUES(:user_id, :snippet_id)";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
                $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
                $stmt->execute();
                return ['status' => 'added'];
            }
        }catch(PDOException $e){
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    function isBookmarked($user_id, $snippet_id){
        try{
            $sql = "SELECT COUNT(*) as cnt FROM bookmarks WHERE user_id = :user_id AND snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['cnt'] > 0;
        }catch(PDOException $e){
            return false;
        }
    }

    function getBookmarkedSnippets($user_id){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM bookmarks bk
                INNER JOIN snippet a ON bk.snippet_id = a.snippet_id
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE bk.user_id = :user_id
                GROUP BY a.snippet_id
                ORDER BY bk.bookmarked_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }

    function getBookmarkedSnippetIds($user_id){
        try{
            $sql = "SELECT snippet_id FROM bookmarks WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $ids = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $ids[] = (int)$row['snippet_id'];
            }
            return $ids;
        }catch(PDOException $e){
            return [];
        }
    }

    function addLanguage($language_name){
        try{
            $sql = "INSERT INTO language(language_name) VALUES (:language_name)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":language_name", $language_name, PDO::PARAM_STR);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function updateLanguage($language_id, $language_name){
        try{
            $sql = "UPDATE language SET language_name = :language_name WHERE language_id = :language_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":language_name", $language_name, PDO::PARAM_STR);
            $stmt->bindParam(":language_id", $language_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function deleteLanguage($language_id){
        try{
            $sql = "DELETE FROM language WHERE language_id = :language_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":language_id", $language_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function isLanguageInUse($language_id){
        try{
            $sql = "SELECT COUNT(*) as count FROM snippet WHERE language_id = :language_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":language_id", $language_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)$result['count']) > 0;
        }catch(PDOException $e){
            return false;
        }
    }

    function getAllSnippetsAdmin($keyword = ''){
        try{
            $sql = "SELECT s.*, l.language_name, u.username, u.firstname, u.lastname
                    FROM snippet s
                    INNER JOIN language l ON s.language_id = l.language_id
                    INNER JOIN user u ON s.user_id = u.user_id";
            
            if($keyword !== ''){
                $sql .= " WHERE s.title LIKE :keyword OR u.username LIKE :keyword OR u.firstname LIKE :keyword OR u.lastname LIKE :keyword";
            }
            
            $sql .= " ORDER BY s.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if($keyword !== ''){
                $search = "%$keyword%";
                $stmt->bindParam(":keyword", $search, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            return false;
        }
    }

    function getSnippetByIdAdmin($snippet_id){
        try{
            $sql = "SELECT s.*, l.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet s
                INNER JOIN language l ON s.language_id = l.language_id
                LEFT JOIN snippet_tag st ON s.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE s.snippet_id = :snippet_id
                GROUP BY s.snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function restoreSnippetAdmin($snippet_id){
        try{
            $sql = "UPDATE snippet SET deleted = 0 WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function softDeleteSnippetAdmin($snippet_id){
        try{
            $sql = "UPDATE snippet SET deleted = 1 WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function suspendSnippetAdmin($snippet_id, $admin_username, $reason){
        try{
            $this->db->beginTransaction();

            $sqlSnippet = "UPDATE snippet SET is_suspended = 1 WHERE snippet_id = :snippet_id";
            $stmtSnippet = $this->db->prepare($sqlSnippet);
            $stmtSnippet->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSnippet->execute();

            $sqlSuspension = "INSERT INTO snippet_suspensions (snippet_id, admin_suspended, reason, suspended_at) 
                              VALUES (:snippet_id, :admin_suspended, :reason, NOW())";
            $stmtSuspension = $this->db->prepare($sqlSuspension);
            $stmtSuspension->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSuspension->bindParam(":admin_suspended", $admin_username, PDO::PARAM_STR);
            $stmtSuspension->bindParam(":reason", $reason, PDO::PARAM_STR);
            $stmtSuspension->execute();

            $sqlReports = "UPDATE reports SET status = 'removed' WHERE snippet_id = :snippet_id AND status = 'pending'";
            $stmtReports = $this->db->prepare($sqlReports);
            $stmtReports->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtReports->execute();

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            if($this->db->inTransaction()){
                $this->db->rollBack();
            }
            return false;
        }
    }

    function unsuspendSnippetAdmin($snippet_id, $admin_username){
        try{
            $this->db->beginTransaction();

            $sqlSnippet = "UPDATE snippet SET is_suspended = 0 WHERE snippet_id = :snippet_id";
            $stmtSnippet = $this->db->prepare($sqlSnippet);
            $stmtSnippet->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSnippet->execute();

            $sqlSuspension = "UPDATE snippet_suspensions 
                              SET admin_unsuspended = :admin_unsuspended, unsuspended_at = NOW() 
                              WHERE snippet_id = :snippet_id AND unsuspended_at IS NULL
                              ORDER BY suspension_id DESC LIMIT 1";
            $stmtSuspension = $this->db->prepare($sqlSuspension);
            $stmtSuspension->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSuspension->bindParam(":admin_unsuspended", $admin_username, PDO::PARAM_STR);
            $stmtSuspension->execute();

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            if($this->db->inTransaction()){
                $this->db->rollBack();
            }
            return false;
        }
    }

    function getSnippetSuspensionHistory($snippet_id){
        try{
            $sql = "SELECT * FROM snippet_suspensions 
                    WHERE snippet_id = :snippet_id 
                    ORDER BY suspended_at DESC, suspension_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return [];
        }
    }

    function getLatestSnippetSuspension($snippet_id){
        try{
            $sql = "SELECT * FROM snippet_suspensions 
                    WHERE snippet_id = :snippet_id 
                    ORDER BY suspended_at DESC, suspension_id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function deleteSnippetAdmin($snippet_id){
        try{
            $this->db->beginTransaction();
            
            $sqlTag = "DELETE FROM snippet_tag WHERE snippet_id = :snippet_id";
            $stmtTag = $this->db->prepare($sqlTag);
            $stmtTag->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtTag->execute();
            
            $sqlBk = "DELETE FROM bookmarks WHERE snippet_id = :snippet_id";
            $stmtBk = $this->db->prepare($sqlBk);
            $stmtBk->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtBk->execute();
            
            $sqlLike = "DELETE FROM snippet_like WHERE snippet_id = :snippet_id";
            $stmtLike = $this->db->prepare($sqlLike);
            $stmtLike->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtLike->execute();
            
            $sql = "DELETE FROM snippet WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->db->commit();
            return true;
        }catch(PDOException $e){
            $this->db->rollBack();
            return false;
        }
    }

    function createReport($snippet_id, $user_id, $reason, $details){
        try{
            $sql = "INSERT INTO reports (snippet_id, user_id, reason, details, status) 
                    VALUES (:snippet_id, :user_id, :reason, :details, 'pending')";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":reason", $reason, PDO::PARAM_STR);
            $stmt->bindParam(":details", $details, PDO::PARAM_STR);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function hasUserReported($user_id, $snippet_id){
        try{
            $sql = "SELECT COUNT(*) as count FROM reports WHERE user_id = :user_id AND snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)$result['count']) > 0;
        }catch(PDOException $e){
            return false;
        }
    }

    function getAllReportsAdmin($statusFilter = null){
        try{
            $sql = "SELECT r.*, s.title as snippet_title, s.deleted as snippet_deleted, s.is_suspended as snippet_suspended, s.source_code,
                            u.username as reporter_username, u.firstname as reporter_firstname, u.lastname as reporter_lastname,
                            owner.username as owner_username, owner.firstname as owner_firstname, owner.lastname as owner_lastname
                    FROM reports r
                    INNER JOIN snippet s ON r.snippet_id = s.snippet_id
                    INNER JOIN user u ON r.user_id = u.user_id
                    INNER JOIN user owner ON s.user_id = owner.user_id";
            
            if($statusFilter !== null){
                $sql .= " WHERE r.status = :status";
            }
            
            $sql .= " ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            if($statusFilter !== null){
                $stmt->bindParam(":status", $statusFilter, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            return false;
        }
    }

    function getReportByIdAdmin($report_id){
        try{
            $sql = "SELECT r.*, s.title as snippet_title, s.deleted as snippet_deleted, s.is_suspended as snippet_suspended, s.source_code, s.description as snippet_desc, s.visibility, s.image_path, l.language_name,
                            GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
                            u.username as reporter_username, u.firstname as reporter_firstname, u.lastname as reporter_lastname,
                            owner.username as owner_username, owner.firstname as owner_firstname, owner.lastname as owner_lastname
                    FROM reports r
                    INNER JOIN snippet s ON r.snippet_id = s.snippet_id
                    INNER JOIN language l ON s.language_id = l.language_id
                    LEFT JOIN snippet_tag st ON s.snippet_id = st.snippet_id
                    LEFT JOIN tag t ON st.tag_id = t.tag_id
                    INNER JOIN user u ON r.user_id = u.user_id
                    INNER JOIN user owner ON s.user_id = owner.user_id
                    WHERE r.report_id = :report_id
                    GROUP BY r.report_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":report_id", $report_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function rejectReportAdmin($report_id){
        try{
            $sql = "UPDATE reports SET status = 'rejected' WHERE report_id = :report_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":report_id", $report_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function resolveReportsForSnippet($snippet_id){
        try{
            $sql = "UPDATE reports SET status = 'removed' WHERE snippet_id = :snippet_id AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function getPendingReportsCountAdmin(){
        try{
            $sql = "SELECT COUNT(*) as count FROM reports WHERE status = 'pending'";
            $stmt = $this->db->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0);
        }catch(PDOException $e){
            return 0;
        }
    }


    // ==========================================
    // SNIPPET APPEALS (ระบบยื่นคำร้องถอนการระงับโพสต์)
    // ==========================================

    function createSnippetAppeal($snippet_id, $user_id, $reason){
        try{
            // ป้องกันการส่งซ้ำหากมีคำร้องที่ค้างรอพิจารณาอยู่แล้ว
            $existing = $this->getPendingAppealBySnippet($snippet_id);
            if ($existing) {
                return false;
            }

            $sql = "INSERT INTO snippet_appeals (snippet_id, user_id, reason, status, created_at)
                    VALUES (:snippet_id, :user_id, :reason, 'pending', NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->bindParam(":reason", $reason, PDO::PARAM_STR);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function getPendingAppealBySnippet($snippet_id){
        try{
            $sql = "SELECT * FROM snippet_appeals 
                    WHERE snippet_id = :snippet_id AND status = 'pending' 
                    ORDER BY appeal_id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function getLatestAppealBySnippet($snippet_id){
        try{
            $sql = "SELECT * FROM snippet_appeals 
                    WHERE snippet_id = :snippet_id 
                    ORDER BY appeal_id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function getSnippetAppealsHistory($snippet_id){
        try{
            $sql = "SELECT a.*, u.username as user_username, u.firstname as user_firstname, u.lastname as user_lastname 
                    FROM snippet_appeals a
                    JOIN user u ON a.user_id = u.user_id
                    WHERE a.snippet_id = :snippet_id 
                    ORDER BY a.created_at DESC, a.appeal_id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return [];
        }
    }

    function getAllAppealsAdmin($statusFilter = null){
        try{
            $sql = "SELECT a.*, 
                           s.title as snippet_title, s.deleted as snippet_deleted, s.is_suspended as snippet_suspended, s.source_code,
                           u.username as appellant_username, u.firstname as appellant_firstname, u.lastname as appellant_lastname,
                           susp.reason as suspension_reason, susp.suspended_at, susp.admin_suspended
                    FROM snippet_appeals a
                    INNER JOIN snippet s ON a.snippet_id = s.snippet_id
                    INNER JOIN user u ON a.user_id = u.user_id
                    LEFT JOIN snippet_suspensions susp ON (s.snippet_id = susp.snippet_id AND susp.unsuspended_at IS NULL)";
            
            if($statusFilter !== null && $statusFilter !== 'ALL'){
                $sql .= " WHERE a.status = :status";
            }
            
            $sql .= " ORDER BY a.created_at DESC, a.appeal_id DESC";
            
            $stmt = $this->db->prepare($sql);
            if($statusFilter !== null && $statusFilter !== 'ALL'){
                $stmt->bindParam(":status", $statusFilter, PDO::PARAM_STR);
            }
            $stmt->execute();
            return $stmt;
        }catch(PDOException $e){
            return false;
        }
    }

    function getAppealByIdAdmin($appeal_id){
        try{
            $sql = "SELECT a.*, 
                           s.title as snippet_title, s.deleted as snippet_deleted, s.is_suspended as snippet_suspended, s.source_code, s.description as snippet_desc, s.visibility, s.image_path, l.language_name,
                           GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags,
                           u.username as appellant_username, u.firstname as appellant_firstname, u.lastname as appellant_lastname,
                           susp.reason as suspension_reason, susp.suspended_at, susp.admin_suspended
                    FROM snippet_appeals a
                    INNER JOIN snippet s ON a.snippet_id = s.snippet_id
                    INNER JOIN language l ON s.language_id = l.language_id
                    LEFT JOIN snippet_tag st ON s.snippet_id = st.snippet_id
                    LEFT JOIN tag t ON st.tag_id = t.tag_id
                    INNER JOIN user u ON a.user_id = u.user_id
                    LEFT JOIN snippet_suspensions susp ON (s.snippet_id = susp.snippet_id AND susp.unsuspended_at IS NULL)
                    WHERE a.appeal_id = :appeal_id
                    GROUP BY a.appeal_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":appeal_id", $appeal_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }catch(PDOException $e){
            return false;
        }
    }

    function approveAppealAdmin($appeal_id, $admin_username, $admin_notes = null){
        try{
            $this->db->beginTransaction();

            // ดึง snippet_id จาก appeal
            $sqlCheck = "SELECT snippet_id FROM snippet_appeals WHERE appeal_id = :appeal_id AND status = 'pending'";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindParam(":appeal_id", $appeal_id, PDO::PARAM_INT);
            $stmtCheck->execute();
            $appeal = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if(!$appeal){
                $this->db->rollBack();
                return false;
            }

            $snippet_id = (int)$appeal['snippet_id'];

            // 1. อัปเดตสถานะ Appeal เป็น approved
            $sqlAppeal = "UPDATE snippet_appeals 
                          SET status = 'approved', reviewed_by = :reviewed_by, admin_notes = :admin_notes, reviewed_at = NOW() 
                          WHERE appeal_id = :appeal_id";
            $stmtAppeal = $this->db->prepare($sqlAppeal);
            $stmtAppeal->bindParam(":reviewed_by", $admin_username, PDO::PARAM_STR);
            $stmtAppeal->bindParam(":admin_notes", $admin_notes, PDO::PARAM_STR);
            $stmtAppeal->bindParam(":appeal_id", $appeal_id, PDO::PARAM_INT);
            $stmtAppeal->execute();

            // 2. ปลดระงับ snippet
            $sqlSnippet = "UPDATE snippet SET is_suspended = 0 WHERE snippet_id = :snippet_id";
            $stmtSnippet = $this->db->prepare($sqlSnippet);
            $stmtSnippet->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSnippet->execute();

            // 3. บันทึกการ unsuspend ใน snippet_suspensions
            $sqlSuspension = "UPDATE snippet_suspensions 
                              SET admin_unsuspended = :admin_unsuspended, unsuspended_at = NOW() 
                              WHERE snippet_id = :snippet_id AND unsuspended_at IS NULL
                              ORDER BY suspension_id DESC LIMIT 1";
            $stmtSuspension = $this->db->prepare($sqlSuspension);
            $stmtSuspension->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmtSuspension->bindParam(":admin_unsuspended", $admin_username, PDO::PARAM_STR);
            $stmtSuspension->execute();

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            if($this->db->inTransaction()){
                $this->db->rollBack();
            }
            return false;
        }
    }

    function rejectAppealAdmin($appeal_id, $admin_username, $admin_notes = null){
        try{
            $sql = "UPDATE snippet_appeals 
                    SET status = 'rejected', reviewed_by = :reviewed_by, admin_notes = :admin_notes, reviewed_at = NOW() 
                    WHERE appeal_id = :appeal_id AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":reviewed_by", $admin_username, PDO::PARAM_STR);
            $stmt->bindParam(":admin_notes", $admin_notes, PDO::PARAM_STR);
            $stmt->bindParam(":appeal_id", $appeal_id, PDO::PARAM_INT);
            return $stmt->execute();
        }catch(PDOException $e){
            return false;
        }
    }

    function getPendingAppealsCountAdmin(){
        try{
            $sql = "SELECT COUNT(*) as count FROM snippet_appeals WHERE status = 'pending'";
            $stmt = $this->db->query($sql);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['count'] ?? 0);
        }catch(PDOException $e){
            return 0;
        }
    }
}


?>
