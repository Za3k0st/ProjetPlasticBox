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
		$this->M_orders->UpdateStockFinal($this->M_orders->selectAllProducts());
		$all_products = $this->M_orders->selectAllProducts();


		//Insertion de la liste des clients de la DB dans un tableau
		$all_clients = $this->M_orders->selectAllClientsInfo();

		//Récupération des données du formulaire
	  $n_week_order_number = $this->input->post('nbr_command');
	  $max_products = $this->input->post('nbr_max_product');
		$nbr_max_per_product = $this->input->post('nbr_max_product');
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

			$week_product_quantity = $this->M_orders->selectProductQuantity($start_date, $end_date);

			$nbr_heure = 0;

			foreach ($week_product_quantity as $wpq) {

				$all_products = $this->M_orders->selectAllProducts();
				$nbr_produits_stock_final = $all_products[$wpq['id_produit'] - 1]['stock_final'];
				$quantite_produit = $wpq['SUM(quantite)'];
				$nbr_produits_stock_A = $all_products[$wpq['id_produit'] - 1]['stock_A'];
				$nbr_produits_stock_B = $all_products[$wpq['id_produit'] - 1]['stock_B'];
				echo 'ID du produit: ' . $wpq['id_produit'] . '   -   Quantité: ' . $wpq['SUM(quantite)'] . '<br />';
				echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br />';
				//Vérification si le produit est déja en stock
				if($nbr_produits_stock_final >= $quantite_produit){

					//Présence de la quantité de produit nécessaire dans le stock
					$nbr_produits_stock_final = $nbr_produits_stock_final - $quantite_produit;
					echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br /><br />';
				}
				else {

					//Tant que le nombre de produit commandé est inférieur au produit sortant de la première machine alors on créer
					while ($nbr_produits_stock_A + $nbr_produits_stock_B + $nbr_produits_stock_final <= $quantite_produit) {

						//Ajout de 180 pièces dans le stock A
						$nbr_produits_stock_A = $nbr_produits_stock_A + 180;
						$nbr_heure++;
					}

					echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br />';

					//Passage du produit dans la machine B
					if($nbr_produits_stock_A > 900){

						$copy_nbr_produits_stock_A = $nbr_produits_stock_A;
						while ($copy_nbr_produits_stock_A != $nbr_produits_stock_B) {

								$nbr_produits_stock_B = $nbr_produits_stock_A > 900 ? $nbr_produits_stock_B + 900 : $nbr_produits_stock_B + $nbr_produits_stock_A;
								$nbr_produits_stock_A = $nbr_produits_stock_A > 900 ? $nbr_produits_stock_A - 900 : $nbr_produits_stock_A - $nbr_produits_stock_A;
								$nbr_heure += 3;
						}
					}
					else {
						$nbr_produits_stock_B = $nbr_produits_stock_A == 0 ? $nbr_produits_stock_B : $nbr_produits_stock_A;
						$nbr_produits_stock_A = 0;
						$nbr_heure += 3;
						echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br />';
					}

					//Passage du prduit dans la machine C
					if($nbr_produits_stock_B > 500){

						while ($nbr_produits_stock_final < $quantite_produit) {

								$nbr_produits_stock_final = $nbr_produits_stock_B > 500 ? $nbr_produits_stock_final + 500 : $nbr_produits_stock_final + $nbr_produits_stock_B;
								$nbr_produits_stock_B = $nbr_produits_stock_B > 500 ? $nbr_produits_stock_B - 500 : $nbr_produits_stock_B - $nbr_produits_stock_B;
								$nbr_heure += 2;
								echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br />';
						}

						$nbr_produits_stock_final = $nbr_produits_stock_final - $quantite_produit;
						echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br /><br />';
					}
					else {
						$nbr_produits_stock_final = $nbr_produits_stock_final + $nbr_produits_stock_B - $quantite_produit;
						$nbr_produits_stock_B = 0;
						$nbr_heure += 2;
						echo 'Stock A: ' . $nbr_produits_stock_A  .    ' -   Stock B: ' . $nbr_produits_stock_B . '   -   Stock final: ' . $nbr_produits_stock_final . '   -   Nombre d\'heures passée: ' . $nbr_heure . '<br /><br />';
					}
				}

				$this->M_orders->UpdateProductStocks($wpq['id_produit'], $nbr_produits_stock_A, $nbr_produits_stock_B, $nbr_produits_stock_final);
			}



			//print_r($test);
			echo '<br /><br /><br /><br /><br /><br />';

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
}
