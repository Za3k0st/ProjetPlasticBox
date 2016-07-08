<?php
defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * Created by PhpStorm.
 * User: MathR
 * Date: 19/08/2015
 * Time: 23:17
 */
class Model extends CI_Model
{
    public function __construct(){
        parent::__construct();
        $this->load->database();
    }

    public function get_commandes()
    {
        $sql = 'SELECT MONTH(date_commande) AS mois, YEAR(date_commande) AS annee, COUNT(*) as number, SUM(total_ttc) as total FROM commandes GROUP BY annee, mois';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_produits()
    {
        $sql = 'SELECT MONTH(commandes.date_commande) AS mois, YEAR(commandes.date_commande) AS annee, COUNT(*) as number, SUM(commandes_produits.quantite) as total FROM commandes LEFT JOIN commandes_produits ON commandes_produits.id_commande = commandes.id_commande GROUP BY annee, mois';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_commandes_per_country()
    {
        $sql = 'SELECT COUNT(*) as number, clients.pays as pays FROM commandes LEFT JOIN clients ON clients.id_client = commandes.id_client GROUP BY clients.pays';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_ttc_country()
    {
        $sql = 'SELECT SUM(commandes.total_ttc) as number, clients.pays as pays FROM commandes LEFT JOIN clients ON clients.id_client = commandes.id_client GROUP BY clients.pays';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    public function get_chiffre_affaire_mois()
    {
        $sql = 'SELECT SUM(commandes.total_ttc) as number, MONTH(date_commande) AS mois, YEAR(date_commande) AS annee FROM commandes  GROUP BY annee, mois';
        $query = $this->db->query($sql);
        return $query->result_array();
    }

}
