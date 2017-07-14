<?php
/**
 * Class database
 *
 * @property PDOStatement stmtInsert
 * @property PDOStatement stmtPresent
 */

class database
{
    protected $dbhost = 'db';
    protected $dbusername = 'root';
    protected $dbpassword = 'password';
    protected $dbname = 'salegroup_test';
    protected $connectdb;
    protected $err;
    protected $stmtInsert;
    protected $stmtPresent;
    // Set options
    protected $options = array(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//throw an exception if an error occurs.
    public function __construct($tbl)
    {
        $dsn = 'mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname;
        try {
            $this->connectdb = new PDO($dsn, $this->dbusername, $this->dbpassword, $this->options);// use a try/catch block to attempt to make a connection, or handle any exceptions if an error occurs.
        } catch (Exception $exc) {
            $this->err = 'Connection failed: ' . $exc->getMessage();
        }
        // Prepare queries
        self::prepareInsert($tbl);
        self::preparePresent($tbl);
    }
    //connect to DB
    public function prepareInsert($tableName)
    {
        $qryInsert = 'INSERT INTO ' . $tableName . ' (strProductCode, strProductName, strProductDesc, intProductStock, decProductCost, dtmAdded, dtmDiscontinued) '
            . 'VALUES (:code, :name, :description, :stock, :cost, CURRENT_TIMESTAMP, :discontinued)';
        try {
            $this->stmtInsert = $this->connectdb->prepare($qryInsert);
            return true;
        } catch(Exception $exc){
            $this->err = 'Connection failed: ' . $exc->getMessage();
            return false;
        }
    }
    private function preparePresent($table)
    {
        $qryPresent = 'SELECT COUNT(*) from ' . $table . ' WHERE strProductCode = ?';
        $this->stmtPresent = $this->connectdb->prepare($qryPresent);
        return True;
    }
    //transaction process
    public function executeInsert($row)//inserts array, if it isn't already there
    {
        $arrayIns = array(
            "productCode" => $row[0],
            "productName" => $row[1],
            "productDescription" => $row[2],
            "stockLevel" => $row[3],
            "productCost" => $row[4],
            "discontinuedStatus" => ($row[5]) ? date('Y-m-d H:i:s') : NULL);
        if (!self::executePresent($arrayIns['code'])) {
            try {
                $arr1 = array('success', array_values($arrayIns));
                $this->stmtInsert->execute($arrayIns);
            } catch (Exception $exc) {
                echo $arrayIns[0] . ' was not inserted ' . $exc->getMessage() . PHP_EOL;
                $arr1 = array('fail', array_values($arrayIns));
            }
        } else {
            $arr1 = array('exists', array_values($arrayIns));
        }
        return $arr1;
    }
    public function executePresent($code)
    {
        $this->stmtPresent->execute(array($code));
        return $this->stmtPresent->fetchColumn();
    }
    public function beginTransaction()
    {
        return $this->connectdb->beginTransaction();
    }
    //check for the existence in DB
    public function commitTransaction()
    {
        return $this->connectdb->commit();
    }
    public function cancelTransaction()
    {
        return $this->connectdb->rollBack();
    }
}
/*http://culttt.com/2012/10/01/roll-your-own-pdo-php-class/ Used as a framework
*/

/**
 * Created by PhpStorm.
 * User: Jenni
 * Date: 20/05/2017
 * Time: 01:16
 */