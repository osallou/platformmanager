<?php

require_once 'Framework/Controller.php';
require_once 'Framework/Form.php';
require_once 'Modules/core/Controller/CoresecureController.php';
require_once 'Modules/booking/Model/BookingTranslator.php';
require_once 'Modules/core/Model/CoreStatus.php';


/**
 * 
 * @author sprigent
 * Controller for the home page
 */
class BookingsettingsController extends CoresecureController {

    /**
     * Constructor
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        //$this->checkAuthorizationMenu("bookingsettings");
        $_SESSION["openedNav"] = "bookingsettings";
    }
    
    /**
     * (non-PHPdoc)
     * @see Controller::indexAction()
     */
    public function indexAction($id_space) {

        $this->checkAuthorizationMenuSpace("booking", $id_space, $_SESSION["id_user"]);
        
        $lang = $this->getLanguage();
        $this->render(array("id_space" => $id_space, "lang" => $lang));
    }
}
