<?php

require_once 'Framework/Model.php';

/**
 * Class defining the Area model
 *
 * @author Sylvain Prigent
 */
class ReState extends Model {

    /**
     * Create the site table
     * 
     * @return PDOStatement
     */
    public function __construct() {

        $this->tableName = "re_state";
        $this->setColumnsInfo("id", "int(11)", 0);
        $this->setColumnsInfo("name", "varchar(250)", "");
        $this->setColumnsInfo("color", "varchar(7)", "#ffffff");
        $this->setColumnsInfo("id_space", "int(11)", 0);
        $this->primaryKey = "id";
    }

    public function get($id) {
        $sql = "SELECT * FROM re_state WHERE id=?";
        return $this->runRequest($sql, array($id))->fetch();
    }

    public function getName($id) {
        $sql = "SELECT name FROM re_state WHERE id=?";
        $tmp = $this->runRequest($sql, array($id))->fetch();
        return $tmp[0];
    }

    public function getForSpace($id_space) {
        $sql = "SELECT * FROM re_state WHERE id_space=?";
        return $this->runRequest($sql, array($id_space))->fetchAll();
    }

    public function set($id, $name, $color, $id_space) {
        if ($this->exists($id)) {
            $sql = "UPDATE re_state SET name=?, color=?, id_space=? WHERE id=?";
            $id = $this->runRequest($sql, array($name, $color, $id_space, $id));
        } else {
            $sql = "INSERT INTO re_state (name, color, id_space) VALUES (?,?,?)";
            $this->runRequest($sql, array($name, $color, $id_space));
        }
        return $id;
    }

    public function exists($id) {
        $sql = "SELECT id from re_state WHERE id=?";
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
        $sql = "DELETE FROM re_state WHERE id = ?";
        $this->runRequest($sql, array($id));
    }

}
