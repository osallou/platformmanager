<?php

require_once 'Framework/Model.php';

/**
 * Class defining the Unit model for consomable module
 *
 * @author Sylvain Prigent
 */
class SeOrder extends Model {

    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `se_order` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_space` int(11) NOT NULL,
                `id_user` int(11) NOT NULL,
		`id_status` int(1) NOT NULL,
		`date_open` DATE NOT NULL,						
		`date_last_modified` DATE NOT NULL,
		`date_close` DATE NOT NULL,
                `no_identification` varchar(150) NOT NULL DEFAULT '',
                `id_invoice` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		);";
        $this->runRequest($sql);

        $sql2 = "CREATE TABLE IF NOT EXISTS `se_order_service` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_order` int(11) NOT NULL,
                `id_service` int(11) NOT NULL,
		`quantity` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
		);";

        $this->runRequest($sql2);
    }
    
    public function setInvoiceID($id, $id_invoice){
        $sql = "UPDATE se_order SET id_invoice=? WHERE id=?";
        $this->runRequest($sql, array($id_invoice, $id));
    }
    
    public function setService($id_order, $id_service, $quantity) {
        if ($this->isOrderService($id_order, $id_service)) {
            $sql = "UPDATE se_order_service SET quantity=? WHERE id_order=? AND id_service=?";
            $this->runRequest($sql, array($quantity, $id_order, $id_service));
        } else {
            $sql = "INSERT INTO se_order_service (id_order, id_service, quantity) VALUES (?,?,?)";
            $this->runRequest($sql, array($id_order, $id_service, $quantity));
        }
    }

    public function isOrderService($id_order, $id_service) {
        $sql = "SELECT * FROM se_order_service WHERE id_order=? AND id_service=?";
        $req = $this->runRequest($sql, array($id_order, $id_service));
        if ($req->rowCount() == 1) {
            return true;
        }
        return false;
    }

    public function getOrderServices($id_order) {
        $sql = "SELECT * FROM se_order_service WHERE id_order=?";
        $data = $this->runRequest($sql, array($id_order))->fetchAll();
        $services = array(); $quantities = array();
        foreach($data as $d){
            $services[] = $d["id_service"];
            $quantities[] = $d["quantity"];
        }
        return array("services" => $services, "quantities" => $quantities);
    }
    
    public function getOrderServiceQuantity($id_order, $id_service){
        $sql = "SELECT quantity FROM se_order_service WHERE id_order=? AND id_service=?";
        
        $req =  $this->runRequest($sql, array($id_order, $id_service));
        if ($req->rowCount() == 1){
            return $req->fetch();
        }
        return 0;
    }

    public function setOrder($id, $id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified = "", $date_close = ""){
        $id_status = 0;
        if ($date_close == "" || $date_close=="0000-00-00"){
            $id_status = 1;
        }
        if ($this->isOrder($id)){
            $this->updateEntry($id, $id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified, $date_close);
            return $id;
        }
        else{
            return $this->addEntry($id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified, $date_close);
        }
    }
    
    public function isOrder($id){
        $sql = "SELECT * FROM se_order WHERE id=?";
        $req = $this->runRequest($sql, array($id));
        if ($req->rowCount() == 1){
            return true;
        }
        return false;
    }
    
    public function addEntry($id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified = "", $date_close = "") {
        $sql = "INSERT INTO se_order (id_space, id_user, no_identification, id_status, date_open, date_last_modified, date_close)
				 VALUES(?,?,?,?,?,?,?)";
        $this->runRequest($sql, array(
            $id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified, $date_close
        ));
        return $this->getDatabase()->lastInsertId();
    }

    public function updateEntry($id, $id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified = "", $date_close = "") {
        $sql = "update se_order set id_space=?, id_user=?, no_identification=?, id_status=?, date_open=?, date_last_modified=?, date_close=?
		        where id=?";
        $this->runRequest($sql, array($id_space, $id_user, $no_identification, $id_status, $date_open, $date_last_modified, $date_close, $id));
    }

    public function entries($sortentry = 'id') {

        $sql = "select * from se_order order by " . $sortentry . " ASC;";
        $req = $this->runRequest($sql);
        $entries = $req->fetchAll();
        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
        }
        return $entries;
    }

    public function openedForResp($id_resp){
        $sql = "select * from se_order where id_status=1 AND id_user IN (SELECT id_user FROM ec_j_user_responsible WHERE id_resp=?)";
        $req = $this->runRequest($sql, array($id_resp));
        return $req->fetchAll();
    }
    
    public function openedEntries($sortentry = 'id') {
        $sql = "select * from se_order where id_status=1 order by " . $sortentry . " ASC;";
        $req = $this->runRequest($sql);

        $entries = $req->fetchAll();
        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
        }
        return $entries;
    }

    public function closedEntries($sortentry = 'id') {
        $sql = "select * from se_order where id_status=0 order by " . $sortentry . " ASC;";
        $req = $this->runRequest($sql);

        $entries = $req->fetchAll();

        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
        }
        return $entries;
    }

    public function defaultEntryValues() {

        $entry["id"] = "";
        $entry["id_user"] = "";
        $entry["id_space"] = 0;
        $entry["id_status"] = 1;
        $entry["date_open"] = date("Y-m-d", time());
        $entry["date_last_modified"] = "";
        $entry["date_close"] = "";
        $entry["orders"] = array();
        $entry["no_identification"] = "";
        return $entry;
    }

    public function getEntry($id) {
        $sql = "select * from se_order where id=?";
        $req = $this->runRequest($sql, array($id));
        $entry = $req->fetch();

        return $entry;
    }

    public function setEntryCloded($id) {
        $sql = "update se_order set id_status=0, date_close=?
		        where id=?";
        $this->runRequest($sql, array(date("Y-m-d", time()), $id));
    }

    public function openedItemsForResp($id_resp){
        
        $userList = " SELECT id_user FROM ec_j_user_responsible WHERE id_resp=? ";
        $orderList = " SELECT id FROM se_order WHERE id_user IN (".$userList.") AND id_status=1";
        $sql = "SELECT * FROM se_order_service WHERE id_order IN (".$orderList.")";
        return $this->runRequest($sql, array($id_resp))->fetchAll();
    }
    
    /**
     * Delete a unit
     * @param number $id Unit ID
     */
    public function delete($id) {

        $sql = "DELETE FROM se_order WHERE id = ?";
        $this->runRequest($sql, array($id));
    }

}