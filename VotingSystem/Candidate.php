<?php
require_once "database.php";

class Candidate extends Database
{
    public $election_id = "";
    public $position_id = "";
    public $user_id = null;
    public $full_name = "";
    public $student_id = "";
    public $course = "";
    public $year_level = 1;
    public $photo = "";
    public $platform = "";
    public $status = "pending";

    public function addCandidate()
    {
        $sql = "INSERT INTO candidates (election_id, position_id, user_id, full_name, student_id, course, year_level, photo, platform, status)
                VALUES (:election_id, :position_id, :user_id, :full_name, :student_id, :course, :year_level, :photo, :platform, :status)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $this->election_id);
        $query->bindParam(":position_id", $this->position_id);
        $query->bindParam(":user_id", $this->user_id);
        $query->bindParam(":full_name", $this->full_name);
        $query->bindParam(":student_id", $this->student_id);
        $query->bindParam(":course", $this->course);
        $query->bindParam(":year_level", $this->year_level);
        $query->bindParam(":photo", $this->photo);
        $query->bindParam(":platform", $this->platform);
        $query->bindParam(":status", $this->status);
        return $query->execute();
    }

    public function getCandidatesByElection($election_id, $status = '')
    {
        if ($status) {
            $sql = "SELECT c.*, p.title as position_title 
                    FROM candidates c
                    JOIN positions p ON c.position_id = p.id
                    WHERE c.election_id = :election_id AND c.status = :status
                    ORDER BY p.sort_order ASC, c.full_name ASC";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":election_id", $election_id);
            $query->bindParam(":status", $status);
        } else {
            $sql = "SELECT c.*, p.title as position_title 
                    FROM candidates c
                    JOIN positions p ON c.position_id = p.id
                    WHERE c.election_id = :election_id
                    ORDER BY p.sort_order ASC, c.full_name ASC";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":election_id", $election_id);
        }
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getCandidatesByPosition($position_id, $status = 'approved')
    {
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM votes WHERE candidate_id = c.id) as vote_count
                FROM candidates c
                WHERE c.position_id = :position_id AND c.status = :status
                ORDER BY c.full_name ASC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":position_id", $position_id);
        $query->bindParam(":status", $status);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getCandidateById($id)
    {
        $sql = "SELECT c.*, p.title as position_title, e.title as election_title
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                JOIN elections e ON c.election_id = e.id
                WHERE c.id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE candidates SET status = :status WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":status", $status);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function updateCandidate($id)
    {
        $sql = "UPDATE candidates SET full_name=:full_name, course=:course, year_level=:year_level,
                platform=:platform, position_id=:position_id WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":full_name", $this->full_name);
        $query->bindParam(":course", $this->course);
        $query->bindParam(":year_level", $this->year_level);
        $query->bindParam(":platform", $this->platform);
        $query->bindParam(":position_id", $this->position_id);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function deleteCandidate($id)
    {
        $sql = "DELETE FROM candidates WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function getResultsByElection($election_id)
    {
        $sql = "SELECT c.*, p.title as position_title, p.sort_order,
                COUNT(v.id) as vote_count,
                (SELECT COUNT(*) FROM votes WHERE position_id = c.position_id) as total_position_votes
                FROM candidates c
                JOIN positions p ON c.position_id = p.id
                LEFT JOIN votes v ON v.candidate_id = c.id
                WHERE c.election_id = :election_id AND c.status = 'approved'
                GROUP BY c.id
                ORDER BY p.sort_order ASC, vote_count DESC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
}