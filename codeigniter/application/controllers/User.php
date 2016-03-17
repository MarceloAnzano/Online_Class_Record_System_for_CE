<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class User extends CI_Controller 
{
	public function __construct() 
	{	
		parent::__construct(); //calling initial function
		//calling pre-defined libraries
		//kailangan ng libraries para magamit ung ibang function
		$this->load->library('form_validation'); 
		$this->load->library('session');
		$this->load->model('user_model');
		$this->load->helper('url');
	}

	public function index()
	{

		if (($this->session->userdata('Id')!=""))
		{
			//if user already and login and session is taken
			redirect(site_url('user/home'));
		}
		else
		{
			$this->load->view('pages/login_view');
		}
	}

	public function home()
	{
		if ($this->session->userdata('Id')!="")
    	{
    		//get user id
    		$userId = $this->session->userdata('Id');
			$this->load->model('User_model'); // load the model to be used
			//get the subjects taken
			$data['subj'] = $this->User_model->getUserSubjs($userId);
			//get the username and usertype
			$user['user'] = array(
				'Username' => $this->session->userdata('Username'),
				'ActiveHeader' => "home"
			);	
			//call the pages and include variables
			$this->load->view('templates/header',$user);
			$this->load->view('pages/faculty_page',$data);
			$this->load->view('templates/footer');
		}
		else
		{
			// redirection for unecessary url skipping
			redirect(site_url('user'));
		}
	}

    public function settings()
    {
    	if ($this->session->userdata('Id')!="")
    	{
    		$user['user'] = array(
				'Username' => $this->session->userdata('Username'),
				'ActiveHeader' => "settings"
			);
    		if ($this->session->userdata('UserType') == "Faculty")
    		{
	    		//to go in the settings tab of faculty
				$this->load->view('templates/header',$user);
				$this->load->view('pages/settings');
				$this->load->view('templates/footer');
			}
			else if ($this->session->userdata('UserType') == "Administrator")
			{
				//get faculty usernames for reset pass dropdown
				$this->load->model('Admin_model');
				$names['users'] = $this->Admin_model->get_usernames();
				$this->load->view('templates/header',$user);
				$this->load->view('pages/admin_settings_page',$names);
			}
			else 
			{
				die("Chair");
			}
			// $this->load->view('pages/chairperson_settings_page');
		}
		else 
		{
			$this->load->view("pages/login_view");
		}
    }

    public function archives()
    {
    	if ($this->session->userdata('Id')!="")
    	{
    		// to go in the archives tab
			$user['user'] = array(
				'Username' => $this->session->userdata('Username'),
				'ActiveHeader' => "archives"
			);
			$this->load->view('templates/header',$user);
			$this->load->view('pages/archives');
			$this->load->view('templates/footer');
		}
		else 
		{
			$this->load->view("pages/login_view");
		}
    }

    public function calendar()
    {
    	if ($this->session->userdata('Id')!="")
    	{
    		// to go in the archives tab
			$user['user'] = array(
				'Username' => $this->session->userdata('Username'),
				'UserType' => $this->session->userdata('UserType'),
				'ActiveHeader' => "calendar"
			);
			$this->load->model('Calendar_model');
			$data['info'] = $this->Calendar_model->getEvent();

			$this->load->view('templates/header',$user);
			$this->load->view('pages/calendar',$data);
		}
		else 
		{
			$this->load->view("pages/login_view");
		}
    }

    public function regView($page = 'register_view')
    {
    	// just use this to register dummy users
	    $data['Username']  = $this->session->userdata('Username');
	    $this->load->view('templates/header', $data);
	    $this->load->view('pages/'.$page);
	    $this->load->view('templates/footer', $data);
    }

	public function login()
	{
		// rules for validation
		$rules = array(
			array('field'=>'login_username','rules'=>'required'),
			array('field'=>'login_password','rules'=>'required')
		);
		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE)
		{
			// if validation is incorrect
			$this->index();
		}
		else if($this->form_validation->run() == TRUE)
		{
			// check if user is registered
			$auth = $this->user_model->login($this->input->post('login_username'),$this->input->post('login_password'));
			if($auth == TRUE)
			{
				// authentication is correct
				redirect(site_url('user'));
				return;
			}
			else
			{
				// make error message
				$data['error'] ="Invalid Username or Password";
				$this->load->view('pages/login_view',$data);
				return;
			}
		}
	}

	public function calendar_add_event()
	{
		if ($this->session->userdata('UserType') == "Administrator")
		{
		$event_name = $this->input->post('event_name');
		$event_date = $this->input->post('event_date');
		$UserId = $this->session->userdata('Id');
		$this->load->model('Calendar_model');
		$data = $this->Calendar_model->addEvent($event_date,$event_name,$UserId);
		header('Content-Type: application/json');
    	echo json_encode($data);
    	}
	}

	public function calendar_delete_event()
	{
		if ($this->session->userdata('UserType') == "Administrator")
		{
			$UserId = $this->session->userdata('Id');
			$event_date = $this->input->post('event_date');
			$event_name = $this->input->post('event_name');
			$this->load->model('Calendar_model');
			$data = $this->Calendar_model->delEvent($event_date,$event_name,$UserId);
			header('Content-Type: application/json');
    		echo json_encode($data);
		}
	}

	public function upload_pdf()
	{
		$file = array('classlist' => $_FILES['classlist']);
		$this->load->model('Upload_model');
		// $name = $_FILES["classlist"]["name"];
		// $temp = $_FILES["classlist"]["tmp_name"];
		$module = $this->input->post('module_type');
		// move_uploaded_file($temp, "resources/uploads/".$name);
		$status = $this->Upload_model->save_data($file,$module,$this->session->userdata('Id'));
		if ($status == TRUE)
		{
			$data['status'] ="PDF has been successfully uploaded.";
			if ($this->session->userdata('Id')!="")
	    	{
	    		//to go in the settings tab
				$user['user'] = array(
					'Username' => $this->session->userdata('Username'),
					'UserType' => $this->session->userdata('UserType')
				);
				$this->load->view('templates/header',$user);
				$this->load->view('pages/settings',$data);
				$this->load->view('templates/footer');
			}
			else 
			{
				$this->load->view("pages/login_view");
			}
		}
		else
		{
			//notification for user
			$data['status'] ="PDF has not been uploaded. Invalid file type or file already exists.";
			if ($this->session->userdata('Id')!="")
	    	{
	    		//to go in the settings tab
				$user['user'] = array(
					'Username' => $this->session->userdata('Username'),
					'UserType' => $this->session->userdata('UserType')
				);
				$this->load->view('templates/header',$user);
				$this->load->view('pages/settings',$data);
				$this->load->view('templates/footer');
			}
			else 
			{
				$this->load->view("pages/login_view");
			}
		}
	}

	public function get_class_table($table_type,$ClassId = 0)
	{
		$this->load->model('Table_model');
		// this function in the model will return object containing info about student,subject,class,module
		$data = $this->Table_model->get_class_table($table_type,$ClassId);
		// for status checking
		$data['status'] = "OK";
		$data['table_type'] = $table_type;
		// need to use json to response to the ajax request
		header('Content-Type: application/json');
    	echo json_encode($data);
    	return;
	}

	public function register()
	{
		$this->load->view('login_view'); //loads the login_view.php file in views folder
	}

	public function do_register()
	{
		$rules = array(
			array('field'=>'Username','label'=>'User Name','rules'=>'trim|required'),
			array('field'=>'Password','label'=>'Password','rules'=>'trim|required'),
			array('field'=>'PersonalInfo','label'=>'Personal Info','rules'=>'required'),
			array('field'=>'Fname','label'=>'First Name','rules'=>'required'),
			array('field'=>'Mname','label'=>'Middle Name','rules'=>'required'),
			array('field'=>'Lname','label'=>'Last Name','rules'=>'required'),
		);
		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE)
		{
			$this->load->view('pages/login_view');
		}
		else
		{
			$this->user_model->register_user();
			$this->load->view('pages/success');
		}
	}

	public function view_fac_page()
	{
		$rules = array(
			array('field'=>'subject_title[]','rules'=>'required'),
		);

		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE)
		{
			redirect(site_url('user'));
		}
		else
		{
			$this->user_model->save_settings($this->session->userdata('Id'));
			redirect(site_url('user/home'));
		}
	}	

	public function admin_settings($todo)
	{
		if ($todo == "change_pw")
		{
			$old_pw = $this->input->post('old_pw');
			$new_pw = $this->input->post('new_pw');
			$this->load->model('Admin_model');
			$data["status"] = $this->Admin_model->admin_change_pw($old_pw,$new_pw,$this->session->userdata('Id'));
			header('Content-Type: application/json');
	    	echo json_encode($data);
	    }
		else if ($todo == "change_un")
		{
			$old_un = $this->input->post('old_un');
			$new_un = $this->input->post('new_un');
			$this->load->model('Admin_model');
			$data["status"] = $this->Admin_model->admin_change_un($old_un,$new_un,$this->session->userdata('Id'));
			header('Content-Type: application/json');
	    	echo json_encode($data);
		}
		else if ($todo == "create_acc")
		{
			$this->user_model->register_user();
			$data["status"] = "OK";
			header('Content-Type: application/json');
	    	echo json_encode($data);
		}
		else if ($todo == "fac_reset_pass")
		{
			$user = $this->input->post('data');
			$this->load->model('Admin_model');
			$data["pass"] = $this->Admin_model->acc_reset_pass($user);
			$data["status"] = "OK";
			header('Content-Type: application/json');
	    	echo json_encode($data);
		}
	}

	public function logout()
	{
		// destroy session if user logout
		$this->session->sess_destroy();
		redirect(site_url());
	}
}