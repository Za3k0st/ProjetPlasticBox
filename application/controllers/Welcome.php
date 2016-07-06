<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

	public function index()
	{
		$this->load->view('template/head');
		$this->load->view('welcome_message');
		$this->load->view('template/footer');
	}

	public function stats(){
		$this->load->model('Model');
		$this->Model->stats();
	}
}
