<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Controller {

	public function index()
	{
		$this->load->view('RandomDataGenerator');
	}

  public function addOrders(){
    $this->load->model('M_orders');
    $this->M_orders->addOrders();
    $this->load->view('RandomDataGenerator');
  }
}
