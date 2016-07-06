<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_orders extends CI_Model{

	public function __construct(){
		parent::__construct();
		$this->load->database();
	}

  //Fonction inserant des commandes dans la DB
	public function addOrders($id_client, $order_day, $total_ttc){
		$sql = 'INSERT INTO commandes VALUES(?, ?, ?, ?, ?, ?, ?, ?)';
	$this->db->query($sql, array('null', $order_day, 'null', 'null', 'null', 'null', $id_client, $total_ttc/*$date_souhaitee, $date_debut, $date_fin, $date_livraison, $id_client*/));
	}

	//Fonction inserant des commandes dans la DB
	public function addLinkedProductsOrder($products){
		$last_id = $this->db->insert_id();
		foreach ($products as $p) {
			$sql = 'INSERT INTO commandes_produits VALUES(?, ?, ?)';
			$this->db->query($sql, array($p['qty'], $last_id, $p['id_produit']));
		}
	}

	//Fonction selectionnant tous les produits de la DB
	public function selectAllProducts(){
		$sql = 'SELECT * FROM produits';
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	//Fonction selectionnant toutes les informations concernants les clients de la DB
	public function selectAllClientsInfo(){
		$sql = 'SELECT * FROM clients';
		$query = $this->db->query($sql);
		return $query->result_array();
	}
}
