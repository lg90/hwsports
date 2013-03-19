<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sis extends MY_Controller {

	function __construct()
	{
		parent::__construct();

		$this->data['currentUser'] = $currentUser = $this->users_model->get_logged_in();
	}
	
	/**
	 * A short hand method to basically print out the page with a certain pageid and title
	 *
	 * @param view 		The view to load
	 * @param page 		The page ID it will have
	 * @param title 	
	 * @param data 		passed in data
	 */
	public function view($view,$page,$title,$data){
		$data['title'] = $title;
		$data['page'] = $page;
		$this->load->view('sis/header',$data);
		$this->load->view('sis/'.$view,$data);
		$this->load->view('sis/footer',$data);
	}

	/**
	 * A short hand method to redirect user with message
	 *
	 * @param status 	Type of flash message to display (error, success)
	 * @param page 		The page to redirect to
	 * @param message	Message to display 	
	 */
	public function flash_redirect($status,$page,$message){
		$this->session->set_flashdata($status, $message);
		redirect($page, 'refresh');
	}

	public function index() {
		
		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		$this->view('home','sishome','Home',$this->data);
	}

	public function calendar()
	{
		$this->view('calendar','calendar','Calendar',$this->data);
	}

	public function matches() {			
		$matches = $this->matches_model->get_all();
		foreach($matches as $key => $match) {
			$matches[$key]['date'] = datetime_to_public_date($match['startTime']);
			$matches[$key]['startTime'] = datetime_to_public_time($match['startTime']);
			$matches[$key]['endTime'] = datetime_to_public_time($match['endTime']);
		}
		$this->data['matches'] = $matches;

		$this->view('matches','matches','Matches',$this->data);
	} 

	public function match($matchID)
	{
		$this->load->library('table');
		

		
		$match = $this->matches_model->get($matchID);
		if($match==FALSE) {
			$this->session->set_flashdata('message',  "Match ID $id does not exist.");
			redirect("/sis/matches", 'refresh');
		}
		
		$match['tournamentData']['name'] = ($match['tournamentData'] ? $match['tournamentData']['name'] : "None");
		$match['date'] = datetime_to_public_date($match['startTime']);
		$match['startTime'] = datetime_to_public_time($match['startTime']);
		$match['endTime'] = datetime_to_public_time($match['endTime']);
		
		$this->data['match'] = $match;
		
		$this->data['matchTable'] = array(
			array('<span class="bold">Name:</span>',$match['name']),
			array('<span class="bold">Description:</span>',$match['description']),
			array('<span class="bold">Sport:</span>',$match['sportData']['name']),
			array('<span class="bold">Venue:</span>',$match['venueData']['name']),
			array('<span class="bold">Tournament:</span>',$match['tournamentData']['name']),
			array('<span class="bold">Date:</span>',		$match['date']),
			array('<span class="bold">Start Time:</span>',	$match['startTime']),
			array('<span class="bold">End Time:</span>',	$match['endTime'])
		);
		$this->view('match','match',$match['name'].' | Match',$this->data);
	}

	public function tournaments()
	{	

		$this->data['tournaments'] = $this->tournaments_model->get_all();
		
		$this->view('tournaments','tournaments','Tournaments',$this->data);
	}

	public function tournament($tournamentID)
	{
		$this->load->library('table');

		
		$tournament = $this->tournaments_model->get($tournamentID);
		if($tournament==FALSE) {
			$this->session->set_flashdata('message',  "Tournament ID $id does not exist.");
			redirect("/sis/tournaments", 'refresh');
		}
		
		$this->data['tournament'] = $tournament;
		
		$this->data['tournamentTable'] = array(
			array('<span class="bold">Name:</span>',$tournament['name']),
			array('<span class="bold">Description:</span>',$tournament['description']),
			array('<span class="bold">Sport:</span>',$tournament['sportData']['name']),
			array('<span class="bold">Start Date:</span>',$tournament['tournamentStart']),
			array('<span class="bold">End Date:</span>',$tournament['tournamentEnd']),
		);
		
		$this->view('tournament','tournament',$tournament['name'].' | Tournament',$this->data);
	}
	public function ticketsinfo()
	{
		$this->view('ticketsinfo','ticketsinfo','Tickets',$this->data);
	}
	public function account()
	{
		//set the flash data error message if there is one
		$this->data['message'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message');
		$this->view('account','account','Account',$this->data);
	}
	
	// sign up for tournament
	public function signup($tournamentID)
	{
		if( !$this->ion_auth->logged_in() ){
			$this->session->set_flashdata('message_warning',  "You must be logged in to sign up for a tournament: Please log in below:");
			redirect('/auth/login','refresh'); 
		}
		
		// Give tournament data to the view
		$this->data['tournament'] = $tournament = $this->tournaments_model->get($tournamentID);
		if($tournament==FALSE) {
			$this->session->set_flashdata('message',  "Tournament ID $tournamentID does not exist.");
			redirect("/sis/tournaments", 'refresh');
		}
		
		// Get all role info, includng all sections and descendent inputs, so the view can output markup
		$this->data['roles'] = $roles = $this->sports_model->get_sport_category_roles($tournament['sportData']['sportCategoryID']);

		// We have post data, let's process it
		if( $this->input->post() ) {
			// Loop through input data and deal with it bit by bit
			foreach($_POST as $inputKey => $value) {
				// Get the role ID
				if($inputKey == 'role') 
					$roleID = $value;
					continue;
				// Get team member IDs from CSV if we've got some
				if($inputKey == 'teamMemberIDs') {
					$teamMemberIDs = array_map("intval", explode(",", $this->input->post('teamMemberIDs') ));
					$teamID = $this->teams_model->insert(array());
					if($teamID === FALSE)  
						$this->error_redirect('message_error','/sis/tournaments','Creating team failed');
					if($this->teams_model->add_team_members($teamID,$teamMemberIDs) === FALSE)  
						$this->error_redirect('message_error','/sis/tournaments','Adding members to team failed');
					// Done with teamMemberIDs, skip to next POST input
					continue;
				}
				// Split object:key by colon to get object and key to add
				sscanf($inputKey, "%s:%s", $object, $key);
				// Put value into sub array based on object name so we can add data in bulk later
				$objectData[$object][$key] = $value;
			}
			
			// Now we have all the input data categorised by object, submit it to the correct places in the DB using the relevant model
			foreach($objectData as $object => $data) {
				switch($object) {
					case "users":
						// Update the current logged in user with the new userData 
						if($this->objects_models[$object]->update($currentUser->userID, $data) === FALSE)  
							$this->error_redirect('message_error','/sis/tournaments','Adding additional data to user failed');
					break;
					case "teams":
						if($this->objects_models[$object]->update($teamID, $data) === FALSE)  
							$this->error_redirect('message_error','/sis/tournaments','Adding additional data to team failes');
					case "tournament_actors":
						// Add this user as an actor with the correct role in this specific tournament,
						// and add the tournament-specific data for this user to the tournamentActorData
						$tournamentActorRelations = array(
							'tournamentID' => $tournamentID,
							'actorID' => $currentUser->userID,
							'roleID' => $roleID
						);
						$tournamentActorID = $this->objects_models['tournament_actors']->insert($data, $tournamentActorRelations);
						if($tournamentActorID === FALSE) 
							$this->error_redirect('message_error','/sis/tournaments','Creating new tournamentActor failed');
					break;
				}
			}
			
			$this->flash_redirect('message_success','/sis/tournaments',"Signup successfull! New tournamentActorID: $tournamentActorID");
		} else {
			$this->view('signup','signup','Signup',$this->data);
		}
	}
	
	
	//create a new team member user account
	function addTeamMember($tournamentID,$sectionID)
	{
		$this->data['tournamentID'] = $tournamentID;
		$this->data['sectionID'] = $sectionID;
		
		$this->data['tournament'] = $tournament = $this->tournaments_model->get($tournamentID);
		$sectionInputs = $this->sports_model->get_sport_category_role_input_section_inputs($sectionID);
		$teamMemberInputs = array(); 
		foreach($sectionInputs as $inputID => $input) {
			if(strpos($input['inputType'],'tm-') === 0) {
				$input['inputType'] = substr($input['inputType'],3);
				$teamMemberInputs[] = $input;
			}
		}
		
		// Set up form validation rules for any input type
		foreach($teamMemberInputs as $tminput) {
			switch($tminput['inputType']) {
				case "email":
					$this->form_validation->set_rules($tminput['objectName'].':'.$tminput['tableKeyName'], $tminput['formLabel'], 'required|valid_email');
				break;
				default: 
					$this->form_validation->set_rules($tminput['objectName'].':'.$tminput['tableKeyName'], $tminput['formLabel'], 'required|xss_clean');
			}
		}
		
		// Set up validation for standard inputs
		$this->form_validation->set_rules('first_name', 'First Name', 'required|xss_clean');
		$this->form_validation->set_rules('last_name', 'Last Name', 'required|xss_clean');
		$this->form_validation->set_rules('email', 'Email Address', 'required|valid_email');
		
		// This variable will contain ID of newly created user if this function succeeds
		$newUserID = false;
		$updateUserResponse = false;
		
		// Set up input data
		if ( $this->form_validation->run() ) {
			$username = $email = $this->input->post('email');
			$centreID = $this->data['centre']['centreID'];
			$userIDtoUpdate = $this->input->post('updateUser');

			$additional_data = array(
				'centreID' => $centreID,
				'firstName' => $this->input->post('first_name'),
				'lastName'  => $this->input->post('last_name')
			);
				
			// Grab input data for dynamic inputs
			foreach($teamMemberInputs as $tminput) {
				$additional_data[$tminput['objectName'].':'.$tminput['tableKeyName']] = $this->input->post($tminput['objectName'].':'.$tminput['tableKeyName']);
			}
			
			if( $userIDtoUpdate ) {
				$updateUserResponse = $this->users_model->update($userIDtoUpdate,$additional_data);
				$this->data['user'] = $additional_data;
				$this->data['user']['id'] = $userIDtoUpdate;
				$this->data['user']['email'] = $email;
				$this->data['user']['password'] = "[user specified]";
			} else {
				$password = generatePassword();
				$newUserID = $this->ion_auth->register($username, $password, $email, $additional_data);
				$this->data['user'] = $additional_data;
				$this->data['user']['id'] = $newUserID;
				$this->data['user']['email'] = $email;
				$this->data['user']['password'] = $password;
			}
		}
		
		// Registration success
		if ($newUserID != false) {
			// Successful team member creation, show success message
			$this->data['success'] = $this->ion_auth->messages();
			$this->data['updateUser'] = false;
			$this->load->view('sis/addTeamMember',$this->data);
		} elseif ($updateUserResponse != false) {
			// Successful team member creation, show success message
			$this->data['success'] = "Updated user: ".$updateUserResponse;
			$this->data['updateUser'] = false;
			$this->load->view('sis/addTeamMember',$this->data);
		} else {
			//display the add team member form
			//set the flash data error message if there is one
			$this->data['message'] = (validation_errors() ? validation_errors() : ($this->ion_auth->errors() ? $this->ion_auth->errors() : $this->session->flashdata('message')));

			$this->data['first_name'] = array(
				'name'  => 'first_name',
				'id'    => 'first_name',
				'type'  => 'text',
				'required' => '',
				'value' => $this->form_validation->set_value('first_name'),
			);
			$this->data['last_name'] = array(
				'name'  => 'last_name',
				'id'    => 'last_name',
				'type'  => 'text',
				'required' => '',
				'value' => $this->form_validation->set_value('last_name'),
			);
			$this->data['email'] = array(
				'name'  => 'email',
				'id'    => 'email',
				'type'  => 'email',
				'required' => '',
				'value' => $this->form_validation->set_value('email'),
			);
			
			// Add extra inputs as required by sport category
			foreach($teamMemberInputs as $tminput) {		
			
				$this->data['extraInputs'][ $tminput['objectName'].':'.$tminput['tableKeyName'] ] = array(
					'name'  => $tminput['objectName'].':'.$tminput['tableKeyName'],
					'id'    => $tminput['objectName'].':'.$tminput['tableKeyName'],
					'type'  => $tminput['inputType'],
					'required' => '',
					'inputType'  => $tminput['inputType'],
					'formLabel'  => $tminput['formLabel'],
					'value' => $this->form_validation->set_value($tminput['objectName'].':'.$tminput['tableKeyName']),
				);
			}

			$this->data['updateUser'] = $newUserID;
			$this->load->view('sis/addTeamMember',$this->data);
		}
	}	
	
	//create a new team member user account
	function addLoginTeamMember($tournamentID,$sectionID)
	{	
		$this->data['tournamentID'] = $tournamentID;
		$this->data['sectionID'] = $sectionID;
		
		$this->data['tournament'] = $tournament = $this->tournaments_model->get($tournamentID);
		$sectionInputs = $this->sports_model->get_sport_category_role_input_section_inputs($sectionID);
		$teamMemberInputs = array(); 
		foreach($sectionInputs as $inputID => $input) {
			if(strpos($input['inputType'],'tm-') === 0) {
				$input['inputType'] = substr($input['inputType'],3);
				$teamMemberInputs[] = $input;
			}
		}
		
		//validate form input
		$this->form_validation->set_rules('identity', 'Identity', 'required');
		$this->form_validation->set_rules('password', 'Password', 'required');
		
		if ($this->form_validation->run() == true) {
			$userID = $this->ion_auth->account_check($this->input->post('identity'), $this->input->post('password'));
			if ( $userID !== false ) {
				// log in details valid, get user data
				$user = $this->users_model->get($userID);
				$this->data['first_name'] = array(
					'name'  => 'first_name',
					'id'    => 'first_name',
					'type'  => 'text',
					'required' => '',
					'value' => (isset($user['firstName']) ? $user['firstName'] : '')
				);
				$this->data['last_name'] = array(
					'name'  => 'last_name',
					'id'    => 'last_name',
					'type'  => 'text',
					'required' => '',
					'value' => (isset($user['lastName']) ? $user['lastName'] : '')
				);
				$this->data['email'] = array(
					'name'  => 'email',
					'id'    => 'email',
					'type'  => 'email',
					'required' => '',
					'value' => $this->input->post('identity')
				);
								
				// Add extra inputs as required by sport category
				foreach($teamMemberInputs as $tminput) {
				
					$this->data['extraInputs'][ $tminput['objectName'].':'.$tminput['tableKeyName'] ] = array(
						'name'  => $tminput['objectName'].':'.$tminput['tableKeyName'],
						'id'    => $tminput['objectName'].':'.$tminput['tableKeyName'],
						'type'  => $tminput['inputType'],
						'required' => '',
						'inputType'  => $tminput['inputType'],
						'formLabel'  => $tminput['formLabel'],
						'value' => (isset($user[$tminput['objectName'].':'.$tminput['tableKeyName']]) ? $user[$tminput['objectName'].':'.$tminput['tableKeyName']] : '')
					);
				}
				
				$this->data['updateUser'] = $userID;
				
				$this->load->view('sis/addTeamMember', $this->data);
			} else {
				// Do the equivalent of redirecting, but from within a fancyform
				$this->session->set_flashdata('message_error','Incorrect login details, please try again!');
				$this->data['data'] = "<script type='text/javascript'>
					$('a.addLoginTeamMember').click();
				</script>";
				$this->load->view('data',$this->data);
			}
		} else {
			//the user is not logging in so display the login page
			$this->data['message'] = $this->session->flashdata('message');
			$this->data['message_information'] = $this->session->flashdata('message_information');
			$this->data['message_success'] = $this->session->flashdata('message_success');
			$this->data['message_warning'] = $this->session->flashdata('message_warning');
			$this->data['message_error'] = (validation_errors()) ? validation_errors() : $this->session->flashdata('message_error');
			
			$this->data['identity'] = array('name' => 'identity',
				'id' => 'identity',
				'type' => 'text'
			);
			$this->data['password'] = array('name' => 'password',
				'id' => 'password',
				'type' => 'password'
			);

			$this->load->view('sis/teamMemberLogin', $this->data);
		}
	}
	
	public function info() {
		$this->view('info','info','About Us',$this->data);
	}
	public function help() {
		$this->view('help','help','Help',$this->data);
	}
	public function playground() {
		$this->view('playground','playground','Branding Playground',$this->data);
	}
}
?>