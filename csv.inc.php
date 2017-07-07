<?php

class CSV
{
    protected $fileLoc = 'stock.csv'; // The File Location
    protected $testing = FALSE; // Trigger for Test Mode (CLI)
    protected $dbConfig = 'dbConfig.php'; // This is the source for the database config
    protected $sourceCharSet = 'UTF-8'; // This will set the course Character set
    protected $databaseCharSet = 'ISO-8859-1'; // This will set the Database Character set
    protected $arrayIn = array();
    protected $arrayOut = array();
    protected $arrayExists = array(); //Already in Database
    protected $fieldTitles = array();
    protected $arraySuccess = array(); //Successfully processed
    protected $arrayError = array(); //Invalid Field
    protected $arrayFail = array(); // Failed to input to DB
    protected $arraySkipped = array(); // Did not meet import rules
    protected $num = 0;
    protected $testMode;
    protected $fieldTypes = array('string', 'string', 'string', 'int', 'float',
        'boolean');
    protected $discontinued = array('y', 'Y', 'yes', 'Yes', 'YES', 'discontinued', 'Discontinued'
    , 'DISCONTINUED'); // Possible positive values for the discontinued column
    public function __construct($tempFile, $testOne = FALSE) //See Source 2
    {
        $this->fileLoc= $tempFile;
        $this->testMode= $testOne;
        $this->arrayIn= self::csvToArray($this->fileLoc); // See Source 3
        $this->arrayOut= self::length($this->arrayIn);  // Checking each row length
        $this->arrayOut= self::incorrectValues($this->arrayError); // Checking values such as special characters
        $this->arrayOut= self::rules($this->arrayOut); // Checking arrays against import rules
        $this->arrayOut= self::highValue($this->arrayOut);
        self::dbInsert($this->arrayOut);
    }
    protected function csvToArray($csv = 'stock.csv')// Access and extract CSV
    {
        if (!file_exists($csv) || !is_readable($csv)) {
            throw new Exception("CSV file Error" . PHP_EOL);
        }
        $arrOut = array();
        if (($file = fopen($csv, 'r')) !== FALSE) {// Build route to the file
            while (($row = fgetcsv($file)) !== FALSE) {
                if (!$this->fieldTitles) {
                    $this->fieldTitles = $row; // Store titles in the code to be placed when required
                    $this->num= count($this->fieldTitles);
                } else {
                    $arrOut[] = $row;
                }
            }
            fclose($file); // Close the route to the file
        } else {
            throw new Exception("CSV connection Error" . PHP_EOL);
        }
        return $arrOut;
    }
// end of csvToArray
    protected function length($arrIn) //checks if the correct amount of fields are present
    {
        $arrOut = array();
        foreach ($arrIn as $row) {
            if (count($row) !== $this->num) {
                $this->arrayError[] = $row;
            } else {
                $arrOut[] = self::parseRow($row, $this->fieldTypes);
            }
        }
        return $arrOut;
    }
    private function parseRow($row, $types)
    {
        for ($i = 0; $i < count($types); $i++) {
            if ($types[$i] === 'string') {
                $row[$i]= iconv($this->sourceCharSet,
                    $this->databaseCharSet . "//TRANSLIT", (string)$row[$i]);
            } else if ($types[$i] === 'int') {
                $row[$i] = (int)$row[$i];
            } else if ($types[$i] === 'float') {
                $row[$i] = (float)preg_replace("/([^0-9\\.])/i", "", $row[$i]);
            } else if ($types[$i] === 'boolean') {
                $row[$i] = (in_array($row[$i], $this->discontinued)) ? TRUE : FALSE; // Applying discontinued depending on boolean outcome
            }
        }
        return $row;
    }
            protected function incorrectValues($arrIn)
    {
        $arrayTemp = array();
        unset($this->arrayError);
        foreach ($arrIn as $row) {
            if (count($row) > $this->num) {
                $incorrectCommas = count($row) - $this->num;
                $arrayTemp[] = self::fieldValues($row, $incorrectCommas);
            } else {
                $this->arrayError[] = $row;
            }
        }
        $arrOut = self::length($arrayTemp); // Checking previous rules and reapplying
        return array_merge($arrOut, $this->arrayOut);
    }
    private function fieldValues($row, $incorrectCommas)
    {
        $tempRow = $row;
        for ($i = 1; $i < count($tempRow); $i++) {
            // Check for a space before altering
            if (substr($tempRow[$i], 0, 1) === ' ') {
                if ($i === 1 || (substr($tempRow[$i - 1], 0, 1) !== ' ' && $i !== 1)) { //Checking Previous fields
                    $tempRow[$i - 1] = '"' . $tempRow[$i - 1];
                }
                if (substr($tempRow[$i + 1], 0, 1) !== ' ') { // checking the next fields for entries in the text block
                    $tempRow[$i] = $tempRow[$i] . '"';
                }
            }
        }
        $csvRow = implode(",", $tempRow); // Reverting back to a string
        if (substr_count($csvRow, ', ') === $incorrectCommas) {
            $arrOut = str_getcsv($csvRow);
        } else {
            $arrOut = $row;
        }
        return $arrOut;
    }
    protected function rules($arrIn)
    {
        $arrOut = array();
        foreach ($arrIn as $row) {
            //Any Value in row 3 (stock) less than 10 and any Value in row 4 (price) less than 5 will be skipped

            if ($row[3] < 10 && $row[4] < 5.0) {
                $this->arraySkipped[] = $row;
            } else {
                $arrOut[] = $row;
            }
        }
        return $arrOut;
    }
    public function highValue($arrIn) // High value check, any Value in row 4 over 1000 will be skipped
    {
        $arrOut = array();
        foreach ($arrIn as $row) {
            if ($row [4] > 1000) {
                $this->arraySkipped[] = $row;
            } else {
                $arrOut[] = $row;
            }
        }
        return $arrOut;
    }
    protected function dbInsert($arrIn) // Processes the arrays results using a switch statement
    {
        self::setdbConfig($this->dbConfig);
        $salegroupdb = new database('tblProductData');
        $salegroupdb->beginTransaction();
        foreach ($arrIn as $row) {
            $arrayReturned = $salegroupdb->executeInsert($row);
            switch ($arrayReturned[0]) {
                case 'success':
                    $this->arraySuccess[] = $arrayReturned[1];
                    break;
                case 'fail':
                    $this->arraySkipped[] = $arrayReturned[1];
                    break;
                case 'exists':
                    $this->arrayExists[] = $arrayReturned[1];
                    break;
                default:
                    echo 'error on insert';
            }
        }
        if ($this->testMode) {
            $salegroupdb->cancelTransaction();
        } else {
            $salegroupdb->commitTransaction();
        }
        return TRUE;
    }
    protected function setdbConfig($dbConfig) // Preparing database config
    {
        if (file_exists($dbConfig) && is_readable($dbConfig)) {
            require 'dbConfig.php';
        } else {
            throw new Exception('No File Found!' . PHP_EOL);
        }
        return TRUE;
    }
    public function getArrayIn()
    {
        return (isset($this->arrayIn)) ? $this->arrayIn : NULL;
    }
    public function getArrayError()
    {
        return (isset($this->arrayError)) ? $this->arrayError : NULL;
    }
    public function getArrayOut()
    {
        return (isset($this->arrayOut)) ? $this->arrayOut : NULL;
    }
    public function getArraySkipped()
    {
        return (isset($this->arraySkipped)) ? $this->arraySkipped : NULL;
    }
    public function getArrayExists()
    {
        return (isset($this->arrayExists)) ? $this->arrayExists : NULL;
    }
    public function getArrayFail()
    {
        return (isset($this->arrayFail)) ? $this->arrayFail : NULL;
    }
    public function getArraySuccess()
    {
        return (isset($this->arraySuccess)) ? $this->arraySuccess : NULL;
    }
    public function getNum()
    {
        return $this->num;
    }
    public function getOutput()
    {
        $stringOutput = '';
        if ($this->testMode) {
            $stringOutput .= str_pad("test", 80, "-", //See Source 6-7
                    STR_PAD_BOTH) . PHP_EOL;
        }
        $stringOutput .= str_pad("Stock Processed: ", 20) . count($this->arrayIn) . PHP_EOL;
        $stringOutput .= str_pad("Stock Successful: ", 20) . count($this->arraySuccess) . PHP_EOL;
        $stringOutput .= str_pad("Stock Skipped: ", 20) . count($this->arraySkipped) . PHP_EOL;
        $numErr = count($this->arrayError) + count($this->arrayFail) + count($this->arrayExists);
        $stringOutput .= str_pad("Stock Failed: ", 20) . $numErr . PHP_EOL;
        if (count($this->arrayError)) {
            $stringOutput .= str_pad("Skipped Stock(Error)", 80, "-",
                    STR_PAD_BOTH) . PHP_EOL;
            foreach ($this->arrayError as $currVal) {
                $stringOutput .= "|" . str_pad(implode(',', $currVal), 80) . "|" . PHP_EOL;
            }
        }
        if (count($this->arraySkipped)) {
            $stringOutput .= str_pad("Skipped Stock (Did Not Meet Import Rules)", 80, "-",
                    STR_PAD_BOTH) . PHP_EOL;
            foreach ($this->arraySkipped as $currVal) {
                $stringOutput .= self::printToScreen($currVal);
            }
        }
        if (count($this->arrayExists)) {
            $stringOutput .= str_pad("Skipped Stock (Item already in DB)", 80,
                    "-", STR_PAD_BOTH) . PHP_EOL;
            foreach ($this->arrayExists as $currVal) {
                $stringOutput .= self::printToScreen($currVal);
            }
        }
        if (count($this->arrayFail)) {
            $stringOutput .= str_pad("Skipped Stock (Item failed)",
                    80, "-", STR_PAD_BOTH) . PHP_EOL;
            foreach ($this->arrayFail as $currVal) {
                $stringOutput .= self::printToScreen($currVal);
            }
        }
        {
            if (count($this->arraySuccess)) {
                $stringOutput .= str_pad("Successful Stock", 80, "-",
                        STR_PAD_BOTH) . PHP_EOL;
                foreach ($this->arraySuccess as $currVal) {
                    $stringOutput .= self::printToScreen($currVal);
                }
            }
        }
        return $stringOutput;
    }
    private function printToScreen($row) //See source 5
    {
        return "|" . $row[0] . " |" . str_pad($row[1], 15) . "|" . str_pad($row[2], 38) . "|" . str_pad($row[3], 2) . "|" . str_pad($row[4], 8) . "|" . (($row[5]) ? "      " : "Active") . "|" . PHP_EOL;
    }
}
/**
 * Created by PhpStorm.
 * User: Jenni
 * Date: 18/05/2017
 * Time: 14:27
 */
/* Sources
//  1:https://code.tutsplus.com/tutorials/real-world-oop-with-php-and-mysql--net-1918: Real-World OOP With PHP and MySQL
    2:https://ebckurera.wordpress.com/2013/03/18/__construct-in-php-oop-with-php/: __construct() in PHP, OOP with PHP
    3:http://codegists.com/snippet/php/csvtoarrayphp_sivaschenko_php : csvToArray layout
    4:https://www.w3schools.com/php/php_mysql_select.asp: Object Orientated PhP
    5:http://php.net/manual/en/function.mysql-field-name.php: Print row help
    6:http://php.net/manual/en/function.str-pad.php: Ref 1 to padding
    7:https://www.w3schools.com/php/func_string_str_pad.asp: Ref 2 to padding
 *
 */