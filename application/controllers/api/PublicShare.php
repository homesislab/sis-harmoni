<?php

defined('BASEPATH') or exit('No direct script access allowed');

class PublicShare extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->load->model('Event_model', 'EventModel');
        $this->load->model('Post_model', 'PostModel');
        $this->load->model('Fundraiser_model', 'FundraiserModel');
        $this->load->model('Local_business_model', 'BusinessModel');
        $this->load->model('Local_product_model', 'ProductModel');
        $this->load->model('Meeting_minute_model', 'MinutesModel');
    }

    public function event(string $slug = ''): void
    {
        $row = $this->EventModel->find_public_by_slug($slug);
        if (!$row) { api_not_found(); return; }
        api_ok($row);
    }

    public function post(string $slug = ''): void
    {
        $row = $this->PostModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'published') { api_not_found(); return; }
        api_ok(['post' => $row]);
    }

    public function fundraiser(string $slug = ''): void
    {
        $row = $this->FundraiserModel->find_public_by_slug($slug);
        if (!$row) { api_not_found(); return; }
        api_ok(['fundraiser' => $row]);
    }

    public function business(string $slug = ''): void
    {
        $row = $this->BusinessModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'active') { api_not_found(); return; }
        $products = $this->ProductModel->list_by_business((int)$row['id'], 'active');
        api_ok(['business' => $row, 'products' => $products]);
    }

    public function meeting_minute(string $slug = ''): void
    {
        $row = $this->MinutesModel->find_public_by_slug($slug);
        if (!$row || ($row['status'] ?? '') !== 'published') { api_not_found(); return; }
        api_ok(['meeting_minutes' => $row, 'action_items' => []]);
    }
}
