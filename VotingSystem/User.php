<?php
require_once "database.php";

class User extends Database
{
    public $student_id = "";
    public $full_name = "";
    public $email = "";
    public $password = "";
    public $course = "";
    public $year_level = 1;
    public $role = "voter";

    public function register()
    {
        $sql = "INSERT INTO users (student_id, full_name, email, password, course, year_level, role)
                VALUES (:student_id, :full_name, :email, :password, :course, :year_level, :role)";
        $query = $this->connect()->prepare($sql);
        $hashed = password_hash($this->password, PASSWORD_DEFAULT);

        $query->bindParam(":student_id", $this->student_id);
        $query->bindParam(":full_name", $this->full_name);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $hashed);
        $query->bindParam(":course", $this->course);
        $query->bindParam(":year_level", $this->year_level);
        $query->bindParam(":role", $this->role);

        return $query->execute();
    }

    public function login($student_id, $password)
    {
        $sql = "SELECT * FROM users WHERE student_id = :student_id AND is_active = 1";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":student_id", $student_id);

        if ($query->execute()) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function studentIdExists($student_id)
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE student_id = :student_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":student_id", $student_id);
        if ($query->execute()) {
            return $query->fetch()['total'] > 0;
        }
        return false;
    }

    public function emailExists($email)
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        if ($query->execute()) {
            return $query->fetch()['total'] > 0;
        }
        return false;
    }

    public function getUserById($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function getAllUsers($role = '')
    {
        if ($role) {
            $sql = "SELECT * FROM users WHERE role = :role ORDER BY full_name ASC";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":role", $role);
        } else {
            $sql = "SELECT * FROM users ORDER BY created_at DESC";
            $query = $this->connect()->prepare($sql);
        }
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function updateUser($id, $data)
    {
        $sql = "UPDATE users SET full_name=:full_name, email=:email, course=:course, 
                year_level=:year_level, role=:role, is_active=:is_active WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":full_name", $data['full_name']);
        $query->bindParam(":email", $data['email']);
        $query->bindParam(":course", $data['course']);
        $query->bindParam(":year_level", $data['year_level']);
        $query->bindParam(":role", $data['role']);
        $query->bindParam(":is_active", $data['is_active']);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function deleteUser($id)
    {
        $sql = "DELETE FROM users WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function changePassword($id, $new_password)
    {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":password", $hashed);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function getTotalVoters()
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE role = 'voter' AND is_active = 1";
        $result = $this->connect()->query($sql)->fetch();
        return $result['total'];
    }
}