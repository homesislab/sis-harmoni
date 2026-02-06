<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FeedbackCategories extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->as_api();
        $this->require_auth();
        $this->load->model('Feedback_category_model', 'CategoryModel');
    }

    public function index(): void
    {
        api_ok(['items' => $this->CategoryModel->all_active()]);
    }
}
