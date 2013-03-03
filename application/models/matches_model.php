<?php
class Matches_model extends CI_Model {

	/**
	 * $matchID is int(11)
	 *  
	 * @return boolean
	 **/

	public function match_exists($matchID)
	{
		$output = array();
		$queryString = 	"SELECT ".$this->db->escape($matchID)." AS matchID, ".
						"EXISTS(SELECT 1 FROM matches WHERE matchID = ".$this->db->escape($matchID).") AS `exists`";
		$queryData = $this->db->query($queryString);
		$output = $queryData->row_array();
		return $output['exists'];
	}

	/**
	 * Returns if the match dates are valid
	 * @param startTime - the start of the match
	 * @param endTime - the end of the match
	 * @param tournamentID - the tournament we want to check
	 * @return boolean
	 **/
	public function are_valid_dates_in_tournament($startTime, $endTime, $tournamentID)
	{
		$this->load->model('tournaments_model');
		$tournament = $this->tournaments_model->get_tournament($tournamentID);
		$tournamentStart 	= DateTime::createFromFormat(DATE_FORMAT,$tournament['tournamentStart']);
		$tournamentEnd 		= DateTime::createFromFormat(DATE_FORMAT,$tournament['tournamentEnd']);
		return (($startTime<$endTime) && 
				($tournamentStart<=$startTime) && 
				($tournamentStart<=$endTime) && 
				($startTime<$tournamentEnd) && 
				($endTime<$tournamentEnd));
	}

	/**
	 * Verifies that the tournament dates make in the order they were written.
	 *  
	 * @param matchTimes, an associative array of 'startTime'=>value and endTime'=>value 
	 * @param matchID, a match id of type int(11)
	 * @return boolean
	 **/
	public function are_valid_dates(
		$matchTimes,
		$matchID )
	{

		$match = $this->get_match($matchID);

		// initialize variables
		$startTime;
		$endTime;

		// Get the data for the tournament
		$tournament = $this->get_tournament($tournamentID);

		// If we find that a variable has been defined, we replace it. Else, we ignore it.
		if( array_key_exists('startTime',$matchTimes ) 	{ $startTime = $matchTimes['startTime'	]; }
		else { $startTime 	= DateTime::createFromFormat(DATE_TIME_FORMAT, $match['startTime']); 	}
		if( array_key_exists('endTime',$matchTimes ) 	{ $endTime 	= $matchTimes['endTime'	]; }
		else { $endTime 	= DateTime::createFromFormat(DATE_TIME_FORMAT, $match['endTime']); 

		if(!($match['tournamentID']==="0"))
			return $this->are_valid_dates_in_tournament($startTime, $endTime, $match['tournamentID']);
		else
			return ($startTime<$endTime);
	}

	/**
	 * Returns a 2d array of match data
	 *  
	 * @return array
	 **/
	public function get_matches($centreID)
	{
		$output = array();
		$queryString = "SELECT matchID FROM matches LEFT JOIN venues ON matches.venueID = venues.venueID WHERE venues.centreID = ".$this->db->escape($centreID);
		$queryData = $this->db->query($queryString);
		$data = $queryData->result_array();
		foreach($data as $match) {
			$output[] = $this->get_match($match['matchID']);
		}
		return $output;
	}

	/**
	 * Returns a 2d array of match data
	 *  
	 * @return array
	 **/
	public function get_venue_matches($venueID)
	{
		$output = array();
		$queryString = "SELECT matchID FROM matches WHERE venueID = ".$this->db->escape($venueID);
		$queryData = $this->db->query($queryString);
		$data = $queryData->result_array();
		foreach($data as $match) {
			$output[] = $this->get_match($match['matchID']);
		}
		return $output;
	}
	
	/**
	 * Returns a 2d array of match data
	 *  
	 * @return array
	 **/
	public function get_tournament_matches($tournamentID)
	{
		$output = array();
		$queryString = "SELECT matchID FROM matches WHERE tournamentID = ".$this->db->escape($tournamentID);
		$queryData = $this->db->query($queryString);
		$data = $queryData->result_array();
		foreach($data as $match) {
			$output[] = $this->get_match($match['matchID']);
		}
		return $output;
	}

	/**
	 * Returns an array of data from a specific match
	 *  
	 * @return array
	 **/
	public function get_match($matchID)
	{
		/* Return the fields that can be discovered within this match */
		$fields = array();
		$fieldsString = "SELECT `key` FROM `matchData` WHERE `matchID` = ".$this->db->escape($matchID);
		$fieldsQuery = $this->db->query($fieldsString);
		$fieldsResult = $fieldsQuery->result_array();
		foreach($fieldsResult as $fieldResult) {
			$fields[] = $fieldResult['key'];
		}
		if(count($fields)==0)
			return array();

		/* Query the ids that are associated with this match */
		$relational = array();
		$relationalString = "SELECT matchID, sportID, venueID, tournamentID FROM matches WHERE matchID = ".$this->db->escape($matchID);
		$relationalQuery = $this->db->query($relationalString);
		$relationalResult = $relationalQuery->result_array();

		/* Fetch the data */
		$dataString = "SELECT ";
		$i = 0;
		$len = count($fields);
		foreach($fields as $field) {
			$dataString .= "MAX(CASE WHEN `key`=".$this->db->escape($field)." THEN value END ) AS ".$this->db->escape($field);
			if($i<$len-1)
				$dataString .= ", ";
			else
				$dataString .= " ";
			$i++;
		}
		$dataString .= "FROM matchData WHERE matchID = ".$this->db->escape($matchID);
		$dataQuery = $this->db->query($dataString);
		$dataResult = $dataQuery->result_array();

		/* Fetch Sport Data */
		$this->load->model('sports_model');
		$relationalResult[0] = array_merge($relationalResult[0], $this->sports_model->get_sport($relationalResult[0]['sportID']));

		$output = array_merge(array("matchID"=>$matchID), $relationalResult[0], $dataResult[0]);
		return $output;
	}

	/**
	 * Creates a match with data.
	 * returns the matchID of the new match if it was
	 * successful. If not, it should return -1.
	 *  
	 * @return int
	 **/
	public function insert_match($centreID, $data)
	{	
		$this->db->trans_start();

		$this->db->query("INSERT INTO matches (centreID) VALUES (".$this->db->escape($centreID).")");
		$matchID = $this->db->insert_id();

		$insertDataArray = array();
		foreach($data as $key=>$value) {
			$dataArray = array(
					'matchID' => $this->db->escape($matchID),
					'key' => $this->db->escape($key),
					'value' => $this->db->escape($value)
				);
			$insertDataArray[] = $dataArray;
		}
		if ($this->db->insert_batch('matchData',$insertDataArray)) {
			// db success
			$this->db->trans_complete();
			return $matchID;
		} else {
			// db fail
			return -1;
		}
	}

	/**
	 * Updates a match with data.
	 *
	 * @return boolean
	 **/
	public function update_match($matchID, $data){

		$this->db->trans_start();

		if($this->match_exists($matchID)){
			foreach($data as $key=>$value) {
				$escKey = $this->db->escape($key);
				$escValue = $this->db->escape($value);
				$dataQueryString = 	"UPDATE `matchData` ".
									"SET `value`=$escValue ".
									"WHERE `key`=$escKey ".
									"AND `matchID`=$matchID";
				$this->db->query($dataQueryString);
			}
			$this->db->trans_complete();
			return true;
		} else {
			return false;
		}
	}
}