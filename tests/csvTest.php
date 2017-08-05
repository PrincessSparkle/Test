<?php
/**
 * Created by PhpStorm.
 * User: Francis
 * Date: 05/08/2017
 * Time: 16:40
 */
use PHPUnit\Framework\TestCase;
require_once 'csv.inc.php';
require_once 'db.inc.php';
/**
 * @covers CSV
 */
final class csvTest extends TestCase
{
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
}