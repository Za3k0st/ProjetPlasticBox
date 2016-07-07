<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Orders extends CI_Controller {

	public function index()
	{
		$this->load->view('RandomDataGenerator');
	}

  public function addOrders(){

	  //Chargement du model M_orders
	  $this->load->model('M_orders');

		//Insertion de la liste des produits de la DB dans un tableau
		$all_products = $this->M_orders->selectAllProducts();

		//Insertion de la liste des clients de la DB dans un tableau
		$all_clients = $this->M_orders->selectAllClientsInfo();

		//Récupération des données du formulaire
	  $n_week_order_number = $this->input->post('nbr_command');
	  $max_products = $this->input->post('nbr_max_product');
		$nbr_max_per_product = $this->input->post('nbr_max_product');
	  /*$start_date = $this->input->post('start_date');
	  $end_date = $this->input->post('end_date');*/
	  $result_countries['pays'] = $this->input->post('pays[]');

		//Création des dates d'intervalles
		$start_date = date('Y-m-d H:i:s', strtotime('-1 month'));
		$limit_date = date('Y-m-d H:i:s', strtotime('+3 months'));
		$end_date = $this->getEndDate($start_date);

		$cpt = 1;

		//Chargement de la classe order pour pouvoir créer l'objet
		$this->load->library('order');

		//Génération des commandes pour chaque semaine
		while (strtotime($end_date) < strtotime($limit_date)) {

			//Génération des produits pour chaque commande
			for ($i=0; $i < $n_week_order_number; $i++){

				$order = new Order();

				//Insertion des données de la commande
				$order->products = $this->getRandomProducts($max_products, $nbr_max_per_product, $all_products);
				$order->client = $this->getRandomClient($all_clients);
				$order->total_ttc = $this->getTotalTTC($order->products);

			  //Insertion des données dans la DB
			  $this->M_orders->addOrders($order->client['id_client'], $this->getRandomDate($start_date, $end_date),$order->total_ttc);
				$this->M_orders->addLinkedProductsOrder($order->products);
			}

			//calcul des intervalles de commandes hebdomadaires pour la semaine n+1
			$min_week_order_number = floor($n_week_order_number * 0.85);
			$max_week_order_number = floor($n_week_order_number * 1.15);

			//calcul de la quantitée de produit pour chaque commande


			//calcul des intervalles de la nouvelle semaine
			$start_date_timestamp = strtotime($start_date);
			$start_date = date('Y-m-d H:i:s', strtotime('+1 day' . $end_date));
			$end_date = $this->getEndDate($start_date);

			//Changement du nombre de commande pour la semaine n+1 (intervalle +- 15%)
			$n_week_order_number = rand($min_week_order_number, $max_week_order_number);
		}


	  //Redirection sur la vue principale
	  /*$this->load->view('template/head');
	  $this->load->view('welcome_message', $data);
	  $this->load->view('template/footer');*/
  }

  //Fonction permettant de générer un tableau de produits aléatoire
  private function getRandomProducts($max_products, $nbr_max_per_product, $all_products){

    //Insertion de produits aléatoires dans une nouvelle commande
    $random_number = rand(2,$max_products);
    $random_products = array_rand($all_products, $random_number);

    $i = 0;

    foreach ($random_products as $key){
	    $order_products[$i]['id_produit'] = $all_products[$key]['id_produit'];
	    $order_products[$i]['reference'] = $all_products[$key]['reference'];
	    $order_products[$i]['designation'] = $all_products[$key]['designation'];
	    $order_products[$i]['description'] = $all_products[$key]['description'];
	    $order_products[$i]['ttc'] = $all_products[$key]['ttc'];
			$order_products[$i]['qty'] = rand(1, $nbr_max_per_product);
	    $i++;
    }

    return $order_products;
  }

	//Fonction permettant de récupérer les informations d'un client aléatoire de la DB
	private function getRandomClient($all_clients){


		$random_client = $all_clients[rand(0, count($all_clients) - 1)];

		return $random_client;
	}

	//Fonction permettant de calculer le prix total ttc de la commande
	private function getTotalTTC($products){

		//Calcul du prix total
		$total_ttc = 0;

		foreach ($products as $p){
			$total_ttc = $total_ttc + $p['ttc'] * $p['qty'];
		}

		return $total_ttc;
	}

	//Fonction permettant de générer une date aléatoire comprise entre un intervalle de date donné
	private function getRandomDate($start_date, $end_date){

		//Convert to timestamp
		$min = strtotime($start_date);
		$max = strtotime($end_date);

		//Géneration d'un nombre aléatoire compris entre les deux intervalles
		$random_date = rand($min, $max);

		//Convertion de la date au format désiré
		return date('Y-m-d H:i:s', $random_date);
	}

	//Fonction permettant d'ajouter un temps donner à une date donnée
	private function getEndDate($start_date){

		//Changement du type de la date pour permettre le calcul
		$start_date_timestamp = strtotime($start_date);
		return date('Y-m-d H:i:s', strtotime('+1 week' . $start_date));
	}

	private function AlgoB(){


	}
}
