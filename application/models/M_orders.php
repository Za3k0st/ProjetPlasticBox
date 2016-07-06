<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_orders extends CI_Model{

	public function __construct(){
		parent::__construct();
		$this->load->database();
	}

  //Fonction inserant des commandes dans la DB
	public function addOrders(){
		$sql = 'INSERT INTO commandes VALUES(?, ?, ?, ?, ?, ?, ?)';
  $this->db->query($sql, array('null', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), 'null'/*$date_souhaitee, $date_debut, $date_fin, $date_livraison, $id_client*/));
	}

}
