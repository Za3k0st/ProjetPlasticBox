<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stats extends CI_Controller {

    public function index()
    {
        $this->load->model('Model');
        $data['commandes'] = $this->Model->get_commandes();
        $data['produits'] = $this->Model->get_produits();
        $data['commandes_pays'] = $this->Model->get_commandes_per_country();
        $data['ttc_country'] = $this->Model->get_ttc_country();
        $this->load->view('template/head');
        $this->load->view('stats', $data);
        $this->load->view('template/footer');
    }

    }