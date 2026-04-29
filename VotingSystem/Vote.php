<?php
require_once "database.php";

class Vote extends Database
{
    /**
     * Cast a vote. Checks max_votes for the position to allow multi-vote.
     */
    public function castVote($election_id, $position_id, $voter_id, $candidate_id)
    {
        try {
            // Get max_votes for this position
            $maxSql = "SELECT max_votes FROM positions WHERE id = :position_id";
            $maxQuery = $this->connect()->prepare($maxSql);
            $maxQuery->bindParam(":position_id", $position_id);
            $maxQuery->execute();
            $posData = $maxQuery->fetch(PDO::FETCH_ASSOC);
            $maxVotes = $posData ? (int)$posData['max_votes'] : 1;

            // Count how many votes this voter already cast for this position
            $check = "SELECT COUNT(*) as total FROM votes 
                      WHERE election_id = :election_id AND position_id = :position_id AND voter_id = :voter_id";
            $checkQuery = $this->connect()->prepare($check);
            $checkQuery->bindParam(":election_id", $election_id);
            $checkQuery->bindParam(":position_id", $position_id);
            $checkQuery->bindParam(":voter_id", $voter_id);
            $checkQuery->execute();
            $currentVotes = (int)$checkQuery->fetch()['total'];

            if ($currentVotes >= $maxVotes) {
                return false; // Already at max votes for this position
            }

            // Check if already voted for this specific candidate
            $dupCheck = "SELECT COUNT(*) as total FROM votes 
                         WHERE election_id = :election_id AND position_id = :position_id 
                         AND voter_id = :voter_id AND candidate_id = :candidate_id";
            $dupQuery = $this->connect()->prepare($dupCheck);
            $dupQuery->bindParam(":election_id", $election_id);
            $dupQuery->bindParam(":position_id", $position_id);
            $dupQuery->bindParam(":voter_id", $voter_id);
            $dupQuery->bindParam(":candidate_id", $candidate_id);
            $dupQuery->execute();
            if ($dupQuery->fetch()['total'] > 0) {
                return false; // Already voted for this candidate
            }

            $sql = "INSERT INTO votes (election_id, position_id, voter_id, candidate_id)
                    VALUES (:election_id, :position_id, :voter_id, :candidate_id)";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":election_id", $election_id);
            $query->bindParam(":position_id", $position_id);
            $query->bindParam(":voter_id", $voter_id);
            $query->bindParam(":candidate_id", $candidate_id);
            return $query->execute();
        } catch (Exception $e) {
            error_log("Vote cast error: " . $e->getMessage());
            return false;
        }
    }

    public function getVoterHistory($voter_id, $election_id)
    {
        $sql = "SELECT v.*, c.full_name as candidate_name, p.title as position_title
                FROM votes v
                JOIN candidates c ON v.candidate_id = c.id
                JOIN positions p ON v.position_id = p.id
                WHERE v.voter_id = :voter_id AND v.election_id = :election_id
                ORDER BY p.sort_order ASC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":voter_id", $voter_id);
        $query->bindParam(":election_id", $election_id);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getTotalVotesForElection($election_id)
    {
        $sql = "SELECT COUNT(DISTINCT voter_id) as total FROM votes WHERE election_id = :election_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        $query->execute();
        return $query->fetch()['total'];
    }
    
    public function getVotesPerPosition($election_id)
    {
        $sql = "SELECT p.title, COUNT(v.id) as vote_count 
                FROM positions p
                LEFT JOIN votes v ON v.position_id = p.id
                WHERE p.election_id = :election_id
                GROUP BY p.id
                ORDER BY p.sort_order ASC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":election_id", $election_id);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    /**
     * Get count of votes a voter has cast for a specific position
     */
    public function getVoterVoteCountForPosition($voter_id, $election_id, $position_id)
    {
        $sql = "SELECT COUNT(*) as total FROM votes 
                WHERE voter_id = :voter_id AND election_id = :election_id AND position_id = :position_id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":voter_id", $voter_id);
        $query->bindParam(":election_id", $election_id);
        $query->bindParam(":position_id", $position_id);
        $query->execute();
        return (int)$query->fetch()['total'];
    }

    public function logAction($user_id, $action, $details, $ip)
    {
        $sql = "INSERT INTO audit_log (user_id, action, details, ip_address)
                VALUES (:user_id, :action, :details, :ip)";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);
        $query->bindParam(":action", $action);
        $query->bindParam(":details", $details);
        $query->bindParam(":ip", $ip);
        return $query->execute();
    }

    public function getAuditLog($limit = 100)
    {
        $sql = "SELECT a.*, u.full_name, u.student_id 
                FROM audit_log a
                LEFT JOIN users u ON a.user_id = u.id
                ORDER BY a.created_at DESC
                LIMIT :limit";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":limit", $limit, PDO::PARAM_INT);
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
}
