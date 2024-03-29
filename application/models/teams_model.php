<?php
class Teams_model extends MY_Model {

	public function __construct() {
        parent::__construct();
		// Load models we might be referencing

		
		// Basic variables which apply to all table operations
		$this->objectIDKey = "teamID";
		$this->dataTableName = "teamData";
		$this->relationTableName = "teams";
    }

	/**
	 * Returns all data about a specific team, including all team members in $team['users']
	 *  
	 * @return array
	 **/
	public function get($ID) {
		// Get all the teamData
		$team = $this->get_object($ID, $this->objectIDKey, $this->dataTableName);
		if(!$team) return FALSE;
		// Fetch the IDs for all users in the team
		$IDRows = $this->db->get_where('teamsUsers', array('teamID' => $ID))->result_array();
		// Loop through all result rows, get the ID and use that to put all the data into the output array 
		foreach($IDRows as $IDRow) {
			$team['users'][$IDRow['userID']] = $this->users_model->get($IDRow['userID']);
		}
		return $team;
	}
	
	/**
	 * Returns all data about all teams at current centre
	 *  
	 * @return array
	 **/
	public function get_all($where = false) {
		// Fetch the IDs for everything at the current sports centre
		if(is_array($where)) $this->db->where( $where );
		$this->db->where( array('centreID' => $this->centreID) );
		$IDRows = $this->db->get($this->relationTableName)->result_array();
		// Create empty array to output if there are no results
		$all = array();
		// Loop through all result rows, get the ID and use that to put all the data into the output array 
		foreach($IDRows as $IDRow) {
			$all[$IDRow[$this->objectIDKey]] = $this->get($IDRow[$this->objectIDKey]);
		}
		return $all;
	}
	
	/**
	 * Creates a new team with data
	 * Returns the ID of the new object if it was successful.
	 * Returns FALSE on any error or insertion failure (including foreign key restraints).
	 *  
	 * @return int
	 **/
	public function insert($data, $relationIDs=array()) {
		$relationIDs['centreID']=$this->centreID;
		return $this->insert_object($data, $this->objectIDKey, $this->dataTableName, $this->relationTableName, $relationIDs);
	}

	/**
	 * Updates data for a specific team.
	 * Returns TRUE on success.
	 * Returns FALSE on any error or insertion failure (including foreign key restraints).
	 *
	 * @return boolean
	 **/
	public function update($ID, $data, $relationIDs=array()) {
		return $this->update_object($ID, $data, $this->objectIDKey, $this->dataTableName, $this->relationTableName, $relationIDs);
	}
	
	/**
	 * Deletes a team with data.
	 * Also deletes all objects which depend on it, unless $testRun is TRUE in which case a string is returned showing all
	 * Returns TRUE on success.
	 * Returns FALSE on any error or deletion failure (most likely forgotten foreign key restraints).
	 *
	 * @return boolean
	 **/
	public function delete($ID, $testRun=TRUE) {
		$output = "";
		$deletedRows = $this->delete_object($ID, $this->objectIDKey, $this->relationTableName, $testRun);
		if($testRun) {
			foreach( $deletedRows as $deletedObject ) $output .= "<li>$deletedObject</li>";
			return $output;
		}
		return $deletedRows;
	}
	
	/**
	 * Adds user IDs to teamsUsers table
	 *  
	 * @return bool
	 **/
	public function add_team_members($ID, $userIDs) {	
		$this->db->trans_start();
		foreach($userIDs as $userID) {
			$this->db->insert("teamsUsers", array($this->objectIDKey => $ID, "userID" => $userID) ); 
		}
		$this->db->trans_complete();
		return ($this->db->trans_status() === FALSE ? FALSE : TRUE);
	}
	
}