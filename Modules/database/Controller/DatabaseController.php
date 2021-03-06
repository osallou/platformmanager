<?php

require_once 'Framework/Controller.php';
require_once 'Framework/Form.php';
require_once 'Modules/core/Controller/CoresecureController.php';
require_once 'Modules/database/Model/DatabaseTranslator.php';

/**
 * 
 * @author sprigent
 * Controller for the home page
 */
class DatabaseController extends CoresecureController {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        //$this->checkAuthorizationMenu("database");
    }
    
    /**
     * (non-PHPdoc)
     * @see Controller::indexAction()
     */
    public function indexAction($id_space) {
        $this->checkAuthorizationMenuSpace("database", $id_space, $_SESSION["id_user"]);

        $lang = $this->getLanguage();
        $this->render(array("id_space" => $id_space, "lang" => $lang));
    }
}
