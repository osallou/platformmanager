<?php

require_once 'Framework/Model.php';
require_once 'Modules/invoices/Model/InInvoice.php';

require_once 'Modules/clients/Model/ClClientUser.php';
require_once 'Modules/clients/Model/ClClient.php';

require_once 'Modules/services/Model/SePrice.php';
require_once 'Modules/services/Model/StockShelf.php';



/**
 * Class defining the Unit model for consomable module
 *
 * @author Sylvain Prigent
 */
class SeProject extends Model {

    public function createTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `se_project` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_space` int(11) NOT NULL,
		`name` varchar(150) NOT NULL DEFAULT '',
		`id_resp` int(11) NOT NULL,					
                `id_user` int(11) NOT NULL,
		`date_open` DATE,
		`date_close` DATE,
		`new_team` int(4) NOT NULL DEFAULT 1,
		`new_project` int(4) NOT NULL DEFAULT 1,
		`time_limit` varchar(100) NOT NULL DEFAULT '', 
                `id_origin` int(11) NOT NULL DEFAULT 0,
                `closed_by` int(11) NOT NULL DEFAULT 0,
                `in_charge` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		);";
        $this->runRequest($sql);
        $this->addColumn('se_project', 'id_origin', 'int(11)', 0);
        $this->addColumn('se_project', 'closed_by', 'int(11)', 0);
        $this->addColumn('se_project', 'in_charge', 'int(11)', 0);
        $this->addColumn('se_project', 'samplereturn', 'TEXT', '');
        $this->addColumn('se_project', 'samplereturndate', 'DATE', '0000-00-00');
        $this->addColumn('se_project', 'id_sample_cabinet', 'int(11)', 0);
        $this->addColumn('se_project', 'samplestocked', 'int(1)', 0);
        $this->addColumn('se_project', 'samplescomment', 'TEXT', "");


        $sql2 = "CREATE TABLE IF NOT EXISTS `se_project_service` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
                `id_project` int(11) NOT NULL,
                `id_service` int(11) NOT NULL,
                `date` date,
		`quantity` varchar(255) NOT NULL,
                `comment` varchar(255) NOT NULL,
                `id_invoice` int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		);";

        $this->runRequest($sql2);
    }

    public function setSampleStock($id, $samplestocked, $id_cabinet, $cabinetcomment){
        $sql = "UPDATE se_project SET samplestocked=?, id_sample_cabinet=?, samplescomment=? WHERE id=?";
        $this->runRequest($sql, array($samplestocked, $id_cabinet, $cabinetcomment, $id));
    }
    
    public function sampleReturn($id,$samplereturn, $samplereturndate){
        $sql = "UPDATE se_project SET samplereturn=?, samplereturndate=? WHERE id=?";
        $this->runRequest($sql, array($samplereturn, $samplereturndate, $id));
    }
    
    public function closeProject($id, $date_close, $closed_by) {
        $sql = "UPDATE se_project SET date_close=?, closed_by=? WHERE id=?";
        $this->runRequest($sql, array($date_close, $closed_by, $id));
    }

    public function getRespsPeriod($id_space, $periodStart, $periodEnds) {
        $sql = "SELECT DISTINCT id_resp "
                . " FROM se_project "
                . " WHERE id_space=? AND date_open<=? AND (date_close=0000-00-00 OR date_close>=?) ";
        $req = $this->runRequest($sql, array($id_space, $periodEnds, $periodStart));
        $data = $req->fetchAll();
        $modelClient = new ClClient();
        for ($i = 0; $i < count($data); $i++) {
            $clientInfo = $modelClient->get($data[$i]["id_resp"]);
            $data[$i]["name"] = $clientInfo["name"];
            $data[$i]["email"] = $clientInfo["email"];
        }
        return $data;
    }

    public function mergeUsers($users) {
        for ($i = 1; $i < count($users); $i++) {
            $sql = "UPDATE se_project SET id_resp=? WHERE id_resp=?";
            $this->runRequest($sql, array($users[0], $users[$i]));

            $sql2 = "UPDATE se_project SET id_user=? WHERE id_user=?";
            $this->runRequest($sql2, array($users[0], $users[$i]));
        }
    }

    public function setSampleReturn($id, $samplereturn, $samplereturndate) {
        $sql = "UPDATE se_project SET samplereturn=?, samplereturndate=? WHERE id=?";
        $this->runRequest($sql, array($samplereturn, $samplereturndate, $id));
    }

    public function getIdFromName($name, $id_space) {
        $sql = "SELECT id FROM se_project WHERE name=? AND id_space=?";
        $req = $this->runRequest($sql, array($name, $id_space));
        if ($req->rowCount() > 0) {
            $tmp = $req->fetch();
            return $tmp[0];
        }
        return 0;
    }

    public function allOpenedProjects($id_space) {
        $sql = "SELECT * FROM se_project WHERE id_space=? AND date_close=0000-00-00 ORDER BY date_open ASC;";
        $projects = $this->runRequest($sql, array($id_space))->fetchAll();
        return $projects;
    }

    public function allPeriodProjects($id_space, $periodStart, $periodEnd) {
        $sql = "SELECT * FROM se_project WHERE id_space=? AND ("
                . " ( date_open<=? AND date_close>=? AND date_close<=? ) "
                . " OR ( date_open>=? AND date_open<=? AND date_close>=? AND date_close<=? ) "
                . " OR ( date_open>=? AND date_open<=? AND date_close>=? ) "
                . " OR ( date_open<=? AND date_close>=?) "
                . " OR date_close=0000-00-00 "
                . ") ORDER BY date_open ASC;";
        $projects = $this->runRequest($sql, array($id_space,
                    $periodStart, $periodStart, $periodEnd,
                    $periodStart, $periodEnd, $periodStart, $periodEnd,
                    $periodStart, $periodEnd, $periodEnd,
                    $periodStart, $periodEnd
                ))->fetchAll();

        $modelUser = new CoreUser();

        for ($i = 0; $i < count($projects); $i++) {
            $projects[$i]["user_name"] = $modelUser->getUserFUllName($projects[$i]['id_user']);
            $projects[$i]["resp_name"] = $modelUser->getUserFUllName($projects[$i]['id_resp']);
        }
        return $projects;
    }

    public function allOpenedProjectsByInCharge($id_space, $id_incharge) {
        $sql = "SELECT * FROM se_project WHERE id_space=? AND in_charge=? AND date_close=0000-00-00 ORDER BY date_open ASC;";
        $projects = $this->runRequest($sql, array($id_space, $id_incharge))->fetchAll();
        return $projects;
    }

    public function allPeriodProjectsByInCharge($id_space, $id_incharge, $periodStart, $periodEnd) {
        $sql = "SELECT * FROM se_project WHERE id_space=? AND in_charge=? AND date_open<=? AND (date_close=0000-00-00 OR date_close>=?) ORDER BY date_open ASC;";
        $projects = $this->runRequest($sql, array($id_space, $id_incharge, $periodEnd, $periodStart))->fetchAll();
        return $projects;
    }

    public function deleteEntry($id) {
        $sql = "DELETE FROM se_project_service WHERE id=?";
        $this->runRequest($sql, array($id));
    }

    protected function extractYears($data) {

        if (count($data) > 0) {
            $firstDate = $data[0][0];
            $firstDateInfo = explode("-", $firstDate);
            $firstYear = $firstDateInfo[0];
            $i = 0;
            while ($firstYear == "0000") {
                $i++;
                $firstDate = $data[$i][0];
                $firstDateInfo = explode("-", $firstDate);
                $firstYear = $firstDateInfo[0];
            }

            $lastDate = $data[count($data) - 1][0];
            $lastDateInfo = explode("-", $lastDate);
            $lastYear = $lastDateInfo[0];

            $years = array();
            for ($i = $firstYear; $i <= $lastYear; $i++) {
                $years[] = $i;
            }
            return $years;
        }
        return array();
    }

    public function closedProjectsPeriods($id_space, $periodBegin, $periodEnd) {
        $sql = "SELECT date_close FROM se_project WHERE date_close!='0000-00-00' AND id_space=? ORDER BY date_close ASC";
        $data = $this->runRequest($sql, array($id_space))->fetchAll();

        // extract years
        if (count($data) > 0) {
            $firstDate = $data[0][0];
            $firstDateInfo = explode("-", $firstDate);
            $firstYear = $firstDateInfo[0];
            $i = 0;
            while ($firstYear == "0000") {
                $i++;
                $firstDate = $data[$i][0];
                $firstDateInfo = explode("-", $firstDate);
                $firstYear = $firstDateInfo[0];
            }

            $periodBeginInfo = explode('-', $periodBegin);
            if ($firstDateInfo[1] . "-" . $firstDateInfo[2] >= $periodBeginInfo[1] . "-" . $periodBeginInfo[2]) {
                $firstYear = $firstYear - 1;
            }

            // last year
            $lastDate = $data[count($data) - 1][0];
            $lastDateInfo = explode("-", $lastDate);
            $periodEndInfo = explode('-', $periodEnd);
            if ($lastDateInfo[1] . "-" . $lastDateInfo[2] >= $periodEndInfo[1] . "-" . $periodEndInfo[2]) {
                $lastYear = $lastDateInfo[0] + 1;
            } else {
                $lastYear = $lastDateInfo[0];
            }

            $years = array();
            for ($i = $firstYear; $i <= $lastYear; $i++) {
                $years[] = $i;
            }

            return $years;
        }
        return array();
    }

    public function closedProjectsYears($id_space) {
        $sql = "SELECT date_close FROM se_project WHERE date_close!='0000-00-00' AND id_space=? ORDER BY date_open ASC";
        //echo "sql = " . $sql . "</br>";
        $data = $this->runRequest($sql, array($id_space))->fetchAll();
        //print_r($data);
        return $this->extractYears($data);
    }

    public function allProjectsYears($id_space) {
        $sql = "SELECT date_open from se_project WHERE id_space=? ORDER BY date_open ASC";
        $data = $this->runRequest($sql, array($id_space))->fetchAll();

        return $this->extractYears($data);
    }

    public function getProjectsOpenedPeriodResp($beginPeriod, $endPeriod, $id_resp) {
        $sql = "SELECT id FROM se_project WHERE date_close='0000-00-00' AND date_open>=? AND date_open<? AND id_resp=?";
        $req = $this->runRequest($sql, array($beginPeriod, $endPeriod, $id_resp))->fetchAll();
        $data = array();
        foreach ($req as $d) {
            $data[] = $d["id"];
        }
        return $data;
    }

    public function getServicesInvoice($id_invoice) {
        $sql = "SELECT id FROM se_project_service WHERE id_invoice=?";
        return $this->runRequest($sql, array($id_invoice))->fetchAll();
    }

    public function setServiceInvoice($id, $id_invoice) {
        $sql = "UPDATE se_project_service SET id_invoice=? WHERE id=?";
        $this->runRequest($sql, array($id_invoice, $id));
    }

    public function getResp($id_project) {
        $sql = "SELECT id_resp FROM se_project WHERE id=?";
        $req = $this->runRequest($sql, array($id_project))->fetch();
        return $req[0];
    }

    public function getName($id_project) {
        $sql = "SELECT name FROM se_project WHERE id=?";
        $tmp = $this->runRequest($sql, array($id_project))->fetch();
        return $tmp[0];
    }

    public function getNoInvoicesServices($id_project) {
        $sql = "SELECT * FROM se_project_service WHERE id_project=? AND id_invoice=0";
        return $this->runRequest($sql, array($id_project))->fetchAll();
    }

    public function getOpenedProjectForList() {
        $sql = "SELECT * FROM se_project WHERE date_close = ?";
        $req = $this->runRequest($sql, array('0000-00-00'))->fetchAll();
        
        $ids = array();
        $names = array();
        $ids[] = 0;
        $names[] = "...";

        $modelClient = new ClClient();
        foreach ($req as $r) {
            $ids[] = $r["id"];
            $names[] = $modelClient->getName($r['id_resp']) . ": " . $r["name"];
        }
        return array("ids" => $ids, "names" => $names);
    }

    public function setOrigin($id, $id_origin) {
        $sql = "UPDATE se_project SET id_origin=? WHERE id=?";
        $this->runRequest($sql, array($id_origin, $id));
    }

    public function setClosedBy($id, $idClose) {
        $sql = "UPDATE se_project SET closed_by=? WHERE id=?";
        $this->runRequest($sql, array($idClose, $id));
    }

    public function setInCharge($id, $id_visa) {
        $sql = "UPDATE se_project SET in_charge=? WHERE id=?";
        $this->runRequest($sql, array($id_visa, $id));
    }

    public function setEntry($id_entry, $id_project, $id_service, $date, $quantity, $comment, $id_invoice = 0) {

        if ($id_entry > 0) {
            //echo "update service: p:" . $id_project . ", s" . $id_service . ", date:" . $date . "<br/>"; 
            $sql = "UPDATE se_project_service SET quantity=?, comment=?, id_invoice=?, id_project=?, id_service=?, date=? WHERE id=?";
            $this->runRequest($sql, array($quantity, $comment, $id_invoice, $id_project, $id_service, $date, $id_entry));
            return $id_entry;
        } else {
            //echo "add service: p:" . $id_project . ", s" . $id_service . ", date:" . $date . "<br/>"; 
            $sql = "INSERT INTO se_project_service (id_project, id_service, date, quantity, comment, id_invoice) VALUES (?,?,?,?,?,?)";
            $this->runRequest($sql, array($id_project, $id_service, $date, $quantity, $comment, $id_invoice));
            return $this->getDatabase()->lastInsertId();
        }
    }

    public function setService($id_project, $id_service, $date, $quantity, $comment, $id_invoice = 0) {

        if ($this->isProjectService($id_project, $id_service, $date)) {
            //echo "update service: p:" . $id_project . ", s" . $id_service . ", date:" . $date . "<br/>"; 
            $sql = "UPDATE se_project_service SET quantity=?, comment=?, id_invoice=? WHERE id_project=? AND id_service=? AND date=?";
            $this->runRequest($sql, array($quantity, $comment, $id_invoice, $id_project, $id_service, $date));
        } else {
            //echo "add service: p:" . $id_project . ", s" . $id_service . ", date:" . $date . "<br/>"; 
            $sql = "INSERT INTO se_project_service (id_project, id_service, date, quantity, comment, id_invoice) VALUES (?,?,?,?,?,?)";
            $this->runRequest($sql, array($id_project, $id_service, $date, $quantity, $comment, $id_invoice));
        }
    }

    public function addService($id_project, $id_service, $date, $quantity, $comment, $id_invoice = 0) {
        $sql = "INSERT INTO se_project_service (id_project, id_service, date, quantity, comment, id_invoice) VALUES (?,?,?,?,?,?)";
        $this->runRequest($sql, array($id_project, $id_service, $date, $quantity, $comment, $id_invoice));
    }

    public function removeUnsetServices($id_project, $servicesIds, $servicesDates) {

        $sql = "SELECT * FROM se_project_service WHERE id_project=?";
        $data = $this->runRequest($sql, array($id_project))->fetchAll();
        foreach ($data as $d) {
            $found = false;
            for ($i = 0; $i < count($servicesIds); $i++) {
                if ($servicesIds[$i] == $d["id_service"] && $servicesDates[$i] == $d["date"]) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                //echo "delete service id: " . $d["id_service"] . ", date: " . $d["date"] . "<br/>";
                $sql = "DELETE FROM se_project_service WHERE id_project=? AND id_service=? AND date=?";
                $this->runRequest($sql, array($id_project, $d["id_service"], $d["date"]));
            }
        }
    }

    public function isProjectService($id_project, $id_service, $date) {
        $sql = "SELECT * FROM se_project_service WHERE id_project=? AND id_service=? AND date=?";
        $req = $this->runRequest($sql, array($id_project, $id_service, $date));
        if ($req->rowCount() >= 1) {
            return true;
        }
        return false;
    }

    public function getProjectServicesDefault($id_project) {
        $sql = "SELECT * FROM se_project_service WHERE id_project=? ORDER BY date ASC";
        return $this->runRequest($sql, array($id_project))->fetchAll();
    }

    public function getProjectServicesBase($id_project) {
        $sql = "SELECT * FROM se_project_service WHERE id_project=?";
        $data = $this->runRequest($sql, array($id_project))->fetchAll();
        return $data;
    }

    public function getAllServices() {
        $sql = "SELECT * FROM se_project_service";
        $req = $this->runRequest($sql);
        return $req->fetchAll();
    }

    public function getProjectServices($id_project) {
        $sql = "SELECT * FROM se_project_service WHERE id_project=?";
        $data = $this->runRequest($sql, array($id_project))->fetchAll();
        $dates = array();
        $services = array();
        $quantities = array();
        $comments = array();
        foreach ($data as $d) {
            $dates[] = $d["date"];
            $services[] = $d["id_service"];
            $quantities[] = $d["quantity"];
            $comments[] = $d["comment"];
        }
        return array("services" => $services, "quantities" => $quantities, "dates" => $dates, "comments" => $comments);
    }

    public function getProjectServiceQuantity($id_project, $id_service) {
        $sql = "SELECT quantity FROM se_project_service WHERE id_project=? AND id_service=?";

        $req = $this->runRequest($sql, array($id_project, $id_service));
        if ($req->rowCount() == 1) {
            return $req->fetch();
        }
        return 0;
    }

    public function setProject($id, $id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit) {
        if ($this->isProject($id)) {
            $this->updateEntry($id, $id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit);
            return $id;
        } else {
            return $this->addEntry($id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit);
        }
    }

    public function isProject($id) {
        $sql = "SELECT * FROM se_project WHERE id=?";
        $req = $this->runRequest($sql, array($id));
        if ($req->rowCount() == 1) {
            return true;
        }
        return false;
    }

    public function addEntry($id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit) {
        $sql = "INSERT INTO se_project (id_space, name, id_resp, id_user, date_open, date_close, new_team, new_project, time_limit)
				 VALUES(?,?,?,?,?,?,?,?,?)";
        $this->runRequest($sql, array(
            $id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit
        ));
        return $this->getDatabase()->lastInsertId();
    }

    public function updateEntry($id, $id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit) {
        $sql = "update se_project set id_space=?, name=?, id_resp=?, id_user=?, date_open=?, date_close=?, new_team=?, new_project=?, time_limit=?
		        where id=?";
        $this->runRequest($sql, array($id_space, $name, $id_resp, $id_user, $date_open, $date_close, $new_team, $new_project, $time_limit, $id));
    }

    public function entries($id_space, $yearBegin = "", $yearEnd = "", $sortentry = 'id') {

        $sql = "SELECT * FROM se_project WHERE id_space=? ";
        if ($yearBegin != "" && $yearEnd != "") {
            $sql .= "AND date_open >= '" . $yearBegin . "' AND date_open <= '" . $yearEnd . "' ";
        }
        $sql .= " ORDER BY " . $sortentry . " ASC;";

        $req = $this->runRequest($sql, array($id_space));
        $entries = $req->fetchAll();
        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
            $entries[$i]["resp_name"] = $modelUser->getUserFUllName($entries[$i]['id_resp']);
        }
        return $entries;
    }

    public function openedEntries($id_space, $sortentry = 'id') {
        $sql = "select * from se_project WHERE date_close=? AND id_space=? order by " . $sortentry . " ASC;";
        $req = $this->runRequest($sql, array("0000-00-00", $id_space));

        $entries = $req->fetchAll();
        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
            $entries[$i]["resp_name"] = $modelUser->getUserFUllName($entries[$i]['id_resp']);
        }
        return $entries;
    }

    public function closedEntries($id_space, $yearBegin = "", $yearEnd = "", $sortentry = 'id') {
        $sql = "SELECT * FROM se_project WHERE date_close!='0000-00-00' AND id_space=? ";
        if ($yearBegin != "" && $yearEnd != "") {
            $sql .= "AND date_close >= '" . $yearBegin . "' AND date_close <= '" . $yearEnd . "' ";
        }
        $sql .= " order by " . $sortentry . " ASC;";
        //echo "yearBegin = " . $yearBegin . "<br/>";
        //echo "yearEnd = " . $yearEnd . "<br/>";
        //echo "sql = " . $sql . "<br/>";
        $req = $this->runRequest($sql, array($id_space));

        $entries = $req->fetchAll();
        $modelUser = new CoreUser();

        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["user_name"] = $modelUser->getUserFUllName($entries[$i]['id_user']);
            $entries[$i]["resp_name"] = $modelUser->getUserFUllName($entries[$i]['id_resp']);
        }
        return $entries;
    }

    public function defaultEntryValues() {

        $entry["id"] = "";
        $entry["id_space"] = "";
        $entry["name"] = "";
        $entry["id_resp"] = "";
        $entry["id_user"] = "";
        $entry["date_open"] = date("Y-m-d");
        $entry["date_close"] = "";
        $entry["new_team"] = "";
        $entry["new_project"] = "";
        $entry["time_limit"] = "";
        $entry["id_origin"] = "";
        $entry["in_charge"] = 0;
        return $entry;
    }

    public function getProjectEntry($id) {
        $sql = "select * from se_project_service where id=?";
        $req = $this->runRequest($sql, array($id));
        $entry = $req->fetch();

        return $entry;
    }

    public function getEntry($id) {
        $sql = "select * from se_project where id=?";
        $req = $this->runRequest($sql, array($id));
        $entry = $req->fetch();

        return $entry;
    }

    public function setEntryCloded($id, $date_close) {
        $sql = "update se_project set date_close=?
		        where id=?";
        $this->runRequest($sql, array($date_close, $id));
    }

    public function getInfoFromInvoice($id_invoice, $id_space) {

        $sql = "SELECT * FROM in_invoice_item WHERE id_invoice=?";
        $invoiceItem = $this->runRequest($sql, array($id_invoice))->fetch();
        //print_r($invoiceItem["details"]);
        $details = explode(";", $invoiceItem["details"]);
        //echo "details = " . $invoiceItem["details"] . "<br/>";
        //echo 'count details = ' . count($details) . "<br/>";
        $proj = explode("=", $details[count($details) - 2]);
        //$projName = $proj[0];

        $projUrl = explode("/", $proj[1]);
        $projID = $projUrl[2];
        $sqlp = "SELECT * FROM se_project WHERE id=?";
        $req = $this->runRequest($sqlp, array($projID));
        if ($req->rowCount() > 0) {
            $info = $req->fetch();
        } else {
            $info = array();
            $info['closed_by'] = "";
            $info['closed_by_in'] = "";
        }
        //else{
        //    echo 'cannot find project for name ' . $projName . "and id " . $projID ."<br>";
        //}


        $modelUser = new CoreUser();
        $sql2 = "SELECT id_user FROM se_visa WHERE id=? AND id_space=?";
        $id_user = $this->runRequest($sql2, array($info['closed_by'], $id_space))->fetch();
        $info['closed_by'] = $modelUser->getUserFUllName($id_user[0]);
        $info['closed_by_in'] = $modelUser->getUserInitials($id_user[0]);

        return $info;
    }

    public function getProjectsOpenedPeriod($beginPeriod, $endPeriod, $id_space) {
        $sql = "select * from se_project where date_open>=? AND date_open<=? AND id_space=?";
        $projects = $this->runRequest($sql, array($beginPeriod, $endPeriod, $id_space))->fetchAll();

        $modelUser = new CoreUser();
        $modelSampleCabinet = new StockShelf();
        for ($i = 0; $i < count($projects); $i++) {
            $sql = "SELECT id_user FROM se_visa WHERE id=? AND id_space=?";
            $id_user = $this->runRequest($sql, array($projects[$i]['closed_by'], $id_space))->fetch();
            $projects[$i]['closed_by'] = $modelUser->getUserFUllName($id_user[0]);
            $projects[$i]['closed_by_in'] = $modelUser->getUserInitials($id_user[0]);
            $projects[$i]["sample_cabinet"] = $modelSampleCabinet->getFullName($projects[$i]["id_sample_cabinet"]);
        }

        /*
          for($i = 0 ; $i < count($projects) ; $i++){
          $sql = "SELECT id_invoice FROM in_invoice_item WHERE details LIKE '%".$projects[$i]["name"]."%'";
          $req = $this->runRequest($sql);
          if ($req->rowCount() > 0){
          $id_invoice = $req->fetch();

          $sql = "SELECT * FROM in_invoice WHERE id=?";
          $data = $this->runRequest($sql, array($id_invoice[0]));
          $projects[$i]['invoice'] = $data->fetch();
          }
          }
         */

        return $projects;
    }

    public function getPeriodeServicesBalances($id_space, $beginPeriod, $endPeriod) {
        $sql = "select * from se_project where id_space=? AND (date_close>=? OR date_close='0000-00-00')";
        $req = $this->runRequest($sql, array($id_space, $beginPeriod));
        $projects = $req->fetchAll();

        
        $modelClient = new ClClient();
        $modelServices = new SeService();
        $items = $modelServices->getBySpace($id_space);
        //print_r($items);

        for ($i = 0; $i < count($projects); $i++) {

            $projectEntries = $this->getPeriodProjectEntries($projects[$i]["id"], $beginPeriod, $endPeriod);


            // get active items
            $activeItems = $this->getProjectItems($projectEntries);
            $itemsSummary = $this->getProjectItemsSymmary($projectEntries, $activeItems);
            //print_r($itemsSummary);

            $projects[$i]["entries"] = $itemsSummary;
            //print_r($itemsSummary);
            //$items = $this->getProjectServices($projects[$i]["id"]);

            
            $LABpricingid = $modelClient->getPricingID($projects[$i]["id_resp"]);
            $projects[$i]["total"] = $this->calculateProjectTotal($itemsSummary, $LABpricingid);
        }

        return array("items" => $items, "projects" => $projects);
    }

    public function getPeriodeBilledServicesBalances($id_space, $beginPeriod, $endPeriod) {

        // get the projects 
        $sql1 = "select * from se_project where id IN (SELECT DISTINCT id_project FROM se_project_service WHERE id_invoice IN(SELECT id FROM in_invoice WHERE date_generated>=? AND date_generated<=? AND id_space=?))";
        $req1 = $this->runRequest($sql1, array($beginPeriod, $endPeriod, $id_space));
        $projects = $req1->fetchAll();

        $items = array();
        $modelClient = new ClClient();
        for ($i = 0; $i < count($projects); $i++) {

            $projectEntries = $this->getPeriodBilledProjectEntries($projects[$i]["id"], $beginPeriod, $endPeriod);

            // get active items
            $activeItems = $this->getProjectItems($projectEntries);
            $itemsSummary = $this->getProjectItemsSymmary($projectEntries, $activeItems);
            //print_r($itemsSummary);

            $projects[$i]["entries"] = $itemsSummary;
            //print_r($itemsSummary);
            foreach ($itemsSummary as $itSum) {
                if ($itSum["pos"] > 0 && !in_array($itSum["id"], $items)) {
                    $items[] = $itSum["id"];
                }
            }

            $LABpricingid = $modelClient->getPricingID($projects[$i]["id_resp"]);
            $projects[$i]["total"] = $this->calculateProjectTotal($itemsSummary, $LABpricingid);
        }

        return array("items" => $items, "projects" => $projects);
    }

    protected function getPeriodBilledProjectEntries($id_proj, $beginPeriod, $endPeriod) {
        $sql = "select * from se_project_service where id_project=? AND id_invoice IN (SELECT id FROM in_invoice WHERE date_generated>=? AND date_generated<=? AND module='services')";
        $req = $this->runRequest($sql, array(
            $id_proj, $beginPeriod, $endPeriod
        ));
        $entries = $req->fetchAll();

        $modelBill = new InInvoice();
        for ($i = 0; $i < count($entries); $i++) {
            if ($entries[$i]["id_invoice"] > 0) {
                $entries[$i]["invoice"] = $modelBill->getInvoiceNumber($entries[$i]["id_invoice"]);
            }
        }
        return $entries;
    }

    public function getPeriodProjectEntries($id_proj, $beginPeriod, $endPeriod) {
        $sql = "select * from se_project_service where id_project=? AND date>=? AND date<=?";
        $req = $this->runRequest($sql, array(
            $id_proj, $beginPeriod, $endPeriod
        ));
        $entries = $req->fetchAll();

        $modelBill = new InInvoice();
        for ($i = 0; $i < count($entries); $i++) {
            //print_r($entries[$i]);
            if ($entries[$i]["id_invoice"] > 0) {
                $entries[$i]["invoice"] = $modelBill->getInvoiceNumber($entries[$i]["id_invoice"]);
            }
        }

        return $entries;
    }

    protected function getProjectItems($projectEntries) {

        $projectItems = array();
        foreach ($projectEntries as $entry) {
            //print_r($entry);
            $itemID = $entry["id_service"];
            $found = false;
            foreach ($projectItems as $item) {
                if ($item == $itemID) {
                    $found = true;
                }
            }
            if ($found == false) {
                $projectItems[] = $itemID;
            }
        }
        return $projectItems;
    }

    protected function getProjectItemsSymmary($projectEntries, $activeItems) {

        $activeItemsSummary = array();
        for ($i = 0; $i < count($activeItems); $i++) {
            $qi = 0;
            foreach ($projectEntries as $order) {
                //print_r($order);
                if ($order["id_service"] == $activeItems[$i]) {
                    $qi += $order["quantity"];
                }
            }
            $activeItemsSummary[$i]["id"] = $activeItems[$i];
            $activeItemsSummary[$i]["pos"] = 0;
            if ($qi > 0) {
                $activeItemsSummary[$i]["pos"] = 1;
                $activeItemsSummary[$i]["sum"] = $qi;
            }
        }
        return $activeItemsSummary;
    }

    protected function calculateProjectTotal($activeItems, $LABpricingid) {

        $totalHT = 0;
        $itemPricing = new SePrice();
        foreach ($activeItems as $item) {

            if ($item["pos"] > 0) {
                $unitaryPrice = $itemPricing->getPrice($item["id"], $LABpricingid);
                //print_r($unitaryPrice);
                $totalHT += (float) $item["sum"] * (float) $unitaryPrice;
            }
        }
        return $totalHT;
    }

    public function getReturnedSamples($id_space) {
        $sql = "SELECT * FROM se_project WHERE id_space=?";
        $data = $this->runRequest($sql, array($id_space))->fetchAll();
        $modelUser = new CoreUser();
        $modelClient = new ClClient();
        $modelSampleCabinet = new StockShelf();
        for ($i = 0; $i < count($data); $i++) {
            $clientInfo = $modelClient->get($data[$i]['id_resp']);
            
            $data[$i]['resp'] = $clientInfo["contact_name"];
            $data[$i]['user'] = $modelUser->getUserFUllName($data[$i]['id_user']);
            $data[$i]['unit'] = $modelClient->getInstitution($data[$i]['id_resp']);
            $data[$i]["sample_cabinet"] = $modelSampleCabinet->getFullName($data[$i]["id_sample_cabinet"]);
        }
        return $data;
    }

    /**
     * Delete a unit
     * @param number $id Unit ID
     */
    public function delete($id) {

        $sql = "DELETE FROM se_project WHERE id = ?";
        $this->runRequest($sql, array($id));
    }

}
