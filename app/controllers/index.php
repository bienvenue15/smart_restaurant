<?php
require_once 'src/controller.php';
require_once 'app/models/Stats.php';

class Index extends Controller {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        // Render the home page
        $statsModel = new Stats();
        $data = [
            'title' => 'Smart Restaurant - Welcome',
            'page' => 'home',
            'metrics' => $statsModel->getPublicMetrics()
        ];
        
        $this->view->render('home', $data);
    }
}
?>
