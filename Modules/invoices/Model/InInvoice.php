<?php

require_once 'Framework/Model.php';

/**
 * Class defining the Area model
 *
 * @author Sylvain Prigent
 */
class InInvoice extends Model {

    /**
     * Create the site table
     * 
     * @return PDOStatement
     */
    public function __construct() {

        $this->tableName = "in_invoice";
        $this->setColumnsInfo("id", "int(11)", 0);
        $this->setColumnsInfo("number", "varchar(50)", "");
        $this->setColumnsInfo("id_space", "int(11)", "");
        $this->setColumnsInfo("period_begin", "date", "0000-00-00");
        $this->setColumnsInfo("period_end", "date", "0000-00-00");
        $this->setColumnsInfo("date_generated", "date", "0000-00-00");
        $this->setColumnsInfo("date_paid", "date", "0000-00-00");
        $this->setColumnsInfo("id_unit", "int(11)", 0);
        $this->setColumnsInfo("id_responsible", "int(11)", 0);
        $this->setColumnsInfo("total_ht", "varchar(50)", "0");
        $this->setColumnsInfo("id_project", "int(11)", 0);
        $this->setColumnsInfo("is_paid", "int(1)", 0);
        $this->setColumnsInfo("module", "varchar(200)", "");
        $this->setColumnsInfo("controller", "varchar(200)", "");
        $this->primaryKey = "id";
    }

    public function get($id){
        $sql = "SELECT * FROM in_invoice WHERE id=?";
        return $this->runRequest($sql, array($id))->fetch();
    }
    
    public function setTotal($id_invoice, $total){
        $sql = "UPDATE in_invoice SET total_ht=? WHERE id=?";
        $this->runRequest($sql, array($total, $id_invoice));
    }
    
    public function setDatePaid($id, $date){
        //echo "set date = " . $date . "<br/>";
        //echo "where id = " . $id . "<br/>";
        $sql = "UPDATE in_invoice SET date_paid=? WHERE id=?";
        $this->runRequest($sql, array($date, $id));
    }
    
    public function addInvoice($module, $controller, $id_space, $number, $date_generated, $id_unit, $id_responsible, $total_ht = 0, $period_begin = "0000-00-00", $period_end = "0000-00-00", $id_project = 0) {
        $sql = "INSERT INTO in_invoice (module, controller, id_space, number, date_generated, id_unit, id_responsible, total_ht, period_begin, period_end, id_project) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $this->runRequest($sql, array($module, $controller, $id_space, $number, $date_generated, $id_unit, $id_responsible, $total_ht, $period_begin, $period_end, $id_project));
        return $this->getDatabase()->lastInsertId();
    }

    public function getAll($sortentry = "number") {
        $sql = "SELECT in_invoice.*, ec_units.name AS unit, core_users.name AS resp, core_users.firstname AS respfirstname "
                . "FROM in_invoice "
                . "INNER JOIN ec_units ON ec_units.id=in_invoice.id_unit "
                . "INNER JOIN core_users ON core_users.id=in_invoice.id_responsible "
                . "ORDER BY " . $sortentry . " DESC;";
        return $this->runRequest($sql)->fetchAll();
    }
    
    public function getBySpace($id_space, $sortentry = "number"){
                $sql = "SELECT in_invoice.*, ec_units.name AS unit, core_users.name AS resp, core_users.firstname AS respfirstname "
                . "FROM in_invoice "
                . "INNER JOIN ec_units ON ec_units.id=in_invoice.id_unit "
                . "INNER JOIN core_users ON core_users.id=in_invoice.id_responsible "
                . "WHERE in_invoice.id_space=?"        
                . "ORDER BY " . $sortentry . " DESC;";
        return $this->runRequest($sql, array($id_space))->fetchAll();
    }

    public function getNextNumber($previousNumber = "") {
        
        if ($previousNumber == ""){
            $sql = "SELECT * FROM in_invoice ORDER BY number DESC;";
            $req = $this->runRequest($sql);

            $lastNumber = "";
            if (count($req->rowCount()) > 0) {
                $bill = $req->fetch();
                $lastNumber = $bill["number"];
            }
        }
        else{
            $lastNumber = $previousNumber; 
        }
        if ($lastNumber != "") {
            //echo "lastNumber = " . $lastNumber . "<br/>";
            $lastNumber = explode("-", $lastNumber);
            $lastNumberY = $lastNumber[0];
            $lastNumberN = $lastNumber[1];

            if ($lastNumberY == date("Y", time())) {
                $lastNumberN = (int) $lastNumberN + 1;
            }
            else{
                return date("Y", time()) . "-0001";
            }
            $num = "";
            if ($lastNumberN < 10) {
                $num = "000" . $lastNumberN;
            } else if ($lastNumberN >= 10 && $lastNumberN < 100) {
                $num = "00" . $lastNumberN;
            } else if ($lastNumberN >= 100 && $lastNumberN < 1000) {
                $num = "0" . $lastNumberN;
            }
            return $lastNumberY . "-" . $num;
        } else {
            return date("Y", time()) . "-0001";
        }
    }

    
    public function getInvoicesPeriod($controller, $periodStart, $periodEnd){
        $sql = "select * from in_invoice WHERE date_generated >= ? AND date_generated <= ? AND controller=?";
        $user = $this->runRequest($sql, array($periodStart, $periodEnd, $controller));
        return $user->fetchAll();
    }
            
    public function getInvoiceNumber($id_invoice){
        $sql = "SELECT number FROM in_invoice WHERE id=?";
        $req = $this->runRequest($sql, array($id_invoice));
        if ($req->rowCount() == 1){
            $tmp = $req->fetch();
            return $tmp[0];
        }
        return "";
    }
            
    public function delete($id){
        $sql = "DELETE FROM in_invoice WHERE id=?";
        $this->runRequest($sql, array($id));
    }
}