<?php
class User{
    private $db;
    function __construct($con){
        $this->db=$con;
    }
    function insertUser($firstname,$lastname,$username,$password){
        try{
            $result=$this->getUserByUsername($username);
            if($result["num"]>0){
                return false;
            }else{
                $new_password = md5($password.$username);
                $sql="INSERT INTO user(firstname,lastname,username,password)
                VALUES(:firstname,:lastname,:username,:password)";
                $stmt=$this->db->prepare($sql);
                $stmt->bindParam(":firstname",$firstname);
                $stmt->bindParam(":lastname",$lastname);
                $stmt->bindParam(":username",$username);
                $stmt->bindParam(":password",$new_password);
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
            $sql="SELECT * FROM user WHERE BINARY username=:username AND password=:password";
            $stmt=$this->db->prepare($sql);
            
            $stmt->bindParam(":username",$username);
            $stmt->bindParam(":password",$password);
            $stmt->execute();
            $result=$stmt->fetch();
            return $result;
        }catch(PDOException $e){
            echo $e->getMessage();
            return false;
        }
    }
}
?>