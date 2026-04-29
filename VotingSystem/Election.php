<?php
require_once "database.php";

class Election extends Database
{
    public $title = "";
    public $description = "";
    public $start_date = "";
    public $end_date = "";
    public $status = "upcoming";

    public function createElection($created_by)
    {
        $sql = "INSERT INTO elections (title, description, start_date, end_date, status, created_by)
                VALUES (:title, :description, :start_date, :end_date, :status, :created_by)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":description", $this->description);
        $query->bindParam(":start_date", $this->start_date);
        $query->bindParam(":end_date", $this->end_date);
        $query->bindParam(":status", $this->status);
        $query->bindParam(":created_by", $created_by);
        if ($query->execute()) {
            return $this->connect()->lastInsertId();
        }
        return false;
    }

    public function updateElection($id)
    {
        $sql = "UPDATE elections SET title=:title, description=:description, 
                start_date=:start_date, end_date=:end_date, status=:status WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":title", $this->title);
        $query->bindParam(":description", $this->description);
        $query->bindParam(":start_date", $this->start_date);
        $query->bindParam(":end_date", $this->end_date);
        $query->bindParam(":status", $this->status);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function deleteElection($id)
    {
        $sql = "DELETE FROM elections WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    public function getAllElections()
    {
        $sql = "SELECT e.*, u.full_name as created_by_name,
                (SELECT COUNT(*) FROM positions WHERE election_id = e.id) as position_count,
                (SELECT COUNT(*) FROM candidates WHERE election_id = e.id AND status = 'approved') as candidate_count
                FROM elections e
                LEFT JOIN users u ON e.created_by = u.id
                ORDER BY e.created_at DESC";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getElectionById($id)
    {
        $sql = "SELECT e.*, u.full_name as created_by_name 
                FROM elections e
                LEFT JOIN users u ON e.created_by = u.id
                WHERE e.id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    public function getActiveElections()
    {
        $sql = "SELECT * FROM elections WHERE status = 'active' ORDER BY start_date ASC";
        $query = $this->connect()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function updateStatuses()
    {
        $now = date('Y-m-d H:i:s');
        // Activate upcoming elections that have started
        $sql = "UPDATE elections SET status = 'active' 
                WHERE status = 'upcoming' AND start_date <= :now AND end_date > :now2";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":now", $now);
        $query->bindParam(":now2", $now);
        $query->execute();

        // End active elections that have passed
        $sql2 = "UPDATE elections SET status = 'ended' 
                 WHERE status = 'active' AND end_date <= :now";
        $query2 = $this->connect()->prepare($sql2);
        $query2->bindParam(":now", $now);
        $query2->execute();
    }

    // Positions
    public function addPosition($election_id, $title, $description, $max_votes, $sort_order)
    {
        $sql = "INSERT INTO positions (election_id, title, description, max_votes, sort_order)
                VALUES (:election_id, :title, :description, :max_votes, :sort_order)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        $query->bindParam(":title", $title);
        $query->bindParam(":description", $description);
        $query->bindParam(":max_votes", $max_votes);
        $query->bindParam(":sort_order", $sort_order);
        if ($query->execute()) {
            return $this->connect()->lastInsertId();
        }
        return false;
    }

    public function getPositions($election_id)
    {
        $sql = "SELECT p.*,
                (SELECT COUNT(*) FROM candidates WHERE position_id = p.id AND status = 'approved') as candidate_count
                FROM positions p
                WHERE p.election_id = :election_id
                ORDER BY p.sort_order ASC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function deletePosition($id)
    {
        $sql = "DELETE FROM positions WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        return $query->execute();
    }

    // Voter stats
    public function getVoterTurnout($election_id)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT voter_id) as voted_count
                FROM votes WHERE election_id = :election_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        $query->execute();
        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function hasVoterVotedInPosition($voter_id, $election_id, $position_id)
    {
        $sql = "SELECT COUNT(*) as total FROM votes 
                WHERE voter_id = :voter_id AND election_id = :election_id AND position_id = :position_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":voter_id", $voter_id);
        $query->bindParam(":election_id", $election_id);
        $query->bindParam(":position_id", $position_id);
        $query->execute();
        return $query->fetch()['total'] > 0;
    }

    public function hasVoterCompletedElection($voter_id, $election_id)
    {
        // Voter has completed if they voted in at least one position
        $sql = "SELECT COUNT(DISTINCT position_id) as voted_positions FROM votes 
                WHERE voter_id = :voter_id AND election_id = :election_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":voter_id", $voter_id);
        $query->bindParam(":election_id", $election_id);
        $query->execute();
        $voted = $query->fetch()['voted_positions'];

        $sql2 = "SELECT COUNT(*) as total_positions FROM positions WHERE election_id = :election_id";
        $query2 = $this->connect()->prepare($sql2);
        $query2->bindParam(":election_id", $election_id);
        $query2->execute();
        $total = $query2->fetch()['total_positions'];

        return $total > 0 && $voted >= $total;
    }
}
