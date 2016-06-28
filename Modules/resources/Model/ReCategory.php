<?php

require_once 'Framework/Model.php';

/**
 * Class defining the Site model
 *
 * @author Sylvain Prigent
 */
class ReCategory extends Model {

    /**
     * Create the site table
     * 
     * @return PDOStatement
     */
    public function __construct() {

        $this->tableName = "re_category";
        $this->setColumnsInfo("id", "int(11)", 0);
        $this->setColumnsInfo("name", "varchar(250)", "");
        $this->setColumnsInfo("id_site", "int(11)", 0);
        $this->primaryKey = "id";
    }

    public function get($id) {
        $sql = "SELECT * FROM re_category WHERE id=?";
        return $this->runRequest($sql, array($id))->fetch();
    }

    public function getName($id) {
        $sql = "SELECT name FROM re_category WHERE id=?";
        $tmp = $this->runRequest($sql, array($id))->fetch();
        return $tmp[0];
    }

    public function getAll($sort = "name") {
        $sql = "SELECT re_category.*, ec_sites.name AS site "
                . " FROM re_category "
                . " INNER JOIN ec_sites ON ec_sites.id = re_category.id_site "
                . "ORDER BY re_category." . $sort . " ASC";
        return $this->runRequest($sql)->fetchAll();
    }

    public function set($id, $name, $id_site) {
        if ($this->exists($id)) {
            $sql = "UPDATE re_category SET name=?, id_site=? WHERE id=?";
            $id = $this->runRequest($sql, array($name, $id_site, $id));
        } else {
            $sql = "INSERT INTO re_category (name, id_site) VALUES (?, ?)";
            $this->runRequest($sql, array($name, $id_site));
        }
        return $id;
    }

    public function exists($id) {
        $sql = "SELECT id from re_category WHERE id=?";
        $req = $this->runRequest($sql, array($id));
        if ($req->rowCount() == 1) {
            return true;
        }
        return false;
    }

    /**
     * Delete a unit
     * @param number $id ID
     */
    public function delete($id) {
        $sql = "DELETE FROM re_category WHERE id = ?";
        $this->runRequest($sql, array($id));
    }

}
