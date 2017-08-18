<?php
/**
 * Created by PhpStorm.
 * User: Francis
 * Date: 05/08/2017
 * Time: 16:40
 */
use PHPUnit\Framework\TestCase;
//require_once 'csv.inc.php';
//require_once 'db.inc.php';
/**
 * @covers CSV
 */
final class csvTest extends TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
    /**
     *
     */
    public function testClassConstructor()
    {
        $csvClass = new CSV(tempnam(sys_get_temp_dir(), "csv"),true);
        $this->assertInstanceOf(CSV::class, $csvClass);
    }

    /**
     * @covers CSV::highValue()
     */
    public function testHighValue()
    {
        $array = array(
            array(0,0,0,0,0),
            array(1,1,1,1,1),
            array(2,2,2,2,1001)
        );
        $csvClass = new CSV(tempnam(sys_get_temp_dir(), "csv"),true);
        $arrOut = $csvClass->highValue($array);
        $this->assertCount(2, $arrOut);

        $array = array(
            array(0,0,0,0,0),
            array(1,1,1,1,1),
            array(2,2,2,2,2)
        );
        $csvClass = new CSV(tempnam(sys_get_temp_dir(), "csv"),true);
        $arrOut = $csvClass->highValue($array);
        $this->assertCount(3, $arrOut);
    }
    public function testParseRowKeepsDataIntact()
    {
        $csvClass = new CSV(tempnam(sys_get_temp_dir(), "csv"),true);
        $row = array('string', 3, 4.5, true);
        $types = array('string', 'int', 'float', 'boolean');
        $output = $this->invokeMethod($csvClass, 'parseRow', array($row, $types));
        $this->assertEquals($row, $output);
    }

    public function testParseRowCorrectsBadData()
    {
        $csvClass = new CSV(tempnam(sys_get_temp_dir(), "csv"),true);
        $row = array('string😁', 3.9, '4.5hello', 'true');
        $types = array('string', 'int', 'float', 'boolean');
        $output = $this->invokeMethod($csvClass, 'parseRow', array($row, $types));
        $this->assertEquals($output[0], 'string?');
        $this->assertEquals($output[1], 3);
        $this->assertEquals($output[2], 4.5);
        $this->assertEquals($output[3], false);


    }
}