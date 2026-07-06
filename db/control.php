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
        }catch(PDOException){
            echo $e->Message();
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

    function searchSnippets($keyword, $language_id = null){
        try{
            $sql = "SELECT a.*, b.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet a
                INNER JOIN language b ON a.language_id = b.language_id
                LEFT JOIN snippet_tag st ON a.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE a.visibility IN ('public','protected')
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
                ORDER BY a.snippet_id DESC";
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

    function getSnippetById($snippet_id, $current_user_id = 0){
        try{
            $sql = "SELECT s.*, l.language_name,
                GROUP_CONCAT(DISTINCT t.tag_name ORDER BY t.tag_name SEPARATOR ', ') AS tags
                FROM snippet s
                INNER JOIN language l ON s.language_id = l.language_id
                LEFT JOIN snippet_tag st ON s.snippet_id = st.snippet_id
                LEFT JOIN tag t ON st.tag_id = t.tag_id
                WHERE s.snippet_id = :snippet_id
                AND (s.visibility IN ('public','protected') OR s.user_id = :user_id)
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

            $sql = "UPDATE snippet SET title = :title, source_code = :source_code, description = :description, visibility = :visibility, image_path = :image_path, language_id = :language_id WHERE snippet_id = :snippet_id AND user_id = :user_id";
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

            // Delete snippet row only if it belongs to the user
            $sql = "DELETE FROM snippet WHERE snippet_id = :snippet_id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->bindParam(":user_id", $user_id, PDO::PARAM_INT);
            $stmt->execute();

            if($stmt->rowCount() === 0){
                $this->db->rollBack();
                return false;
            }

            // Remove any tags attached to the snippet
            $sql = "DELETE FROM snippet_tag WHERE snippet_id = :snippet_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(":snippet_id", $snippet_id, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return true;
        }catch(PDOException $e){
            $this->db->rollBack();
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
}


?>