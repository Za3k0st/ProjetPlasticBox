<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_orders extends CI_Model{

	public function __construct(){
		parent::__construct();
		$this->load->database();
	}

  //Fonction inserant des commandes dans la DB
	public function addOrders($id_client, $order_day, $total_ttc){
		$sql = 'INSERT INTO commandes VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)';
	$this->db->query($sql, array('null', $order_day, 'null', 'null', 'null', 'null', $id_client, $total_ttc, 'En cours de fabrication'/*$date_souhaitee, $date_debut, $date_fin, $date_livraison, $id_client*/));
	}

	//Fonction inserant des commandes dans la DB
	public function addLinkedProductsOrder($products){
		$last_id = $this->db->insert_id();
		foreach ($products as $p) {
			$sql = 'INSERT INTO commandes_produits VALUES(?, ?, ?)';
			$this->db->query($sql, array($p['qty'], $last_id, $p['id_produit']));
		}
	}

	//Fonction permettant de mettre a jour le stock final de chaque produit
	public function UpdateStockFinal($all_products){
		foreach ($all_products as $ap) {
			$sql = 'UPDATE produits SET stock_final = ' . rand(20, 40) . ' WHERE id_produit = ' . $ap['id_produit'];
			$query = $this->db->query($sql);
		}
	}

	//Fonction permettant de mettre a jour l'état de tous les stocks d'un produit
	public function UpdateProductStocks($id_produit, $stock_A, $stock_B, $stock_final){
		$sql = 'UPDATE produits SET stock_A = ' . $stock_A . ', stock_B = ' . $stock_B . ', stock_final = ' . $stock_final . ' WHERE id_produit = ' . $id_produit;
		$query = $this->db->query($sql);
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

	//Fonction retournant la quantitee totale des produits commandés dans un intervalle de semaine donné
	public function selectProductQuantity($start_date, $end_date){
		$sql = 'SELECT id_produit, SUM(quantite) FROM commandes LEFT JOIN commandes_produits ON commandes.id_commande = commandes_produits.id_commande WHERE date_commande BETWEEN \'' . $start_date . '\' AND \'' . $end_date . '\'' . 'GROUP BY id_produit ORDER BY SUM(quantite) DESC';
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	//Fonction inserant des pièces dans le stock A
	public function AddInStockA($id_produit, $number){
		$sql = 'UPDATE produits SET stock_A = ' . $number . ' WHERE id_produit = ' . $id_produit;
		$query = $this->db->query($sql);
	}
}
