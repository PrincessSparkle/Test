<?php
// Read the options from the command line input
$opt = getopt("f:t");
// reading CSV in UTF-8
setlocale(LC_ALL, "en_GB.UTF-8");
// load requested classes

include 'bootstrap.php';

if (!isset($opt["f"])) {
    exit("No input file");
}
// Create a new instance to test
try {

    $stock = new CSV($opt["f"], isset($opt["t"]));
    // print_r(array_values($fruits));
    echo $stock->getOutput();
} catch (Exception $exc) {

    echo $exc->getMessage();
}
/**
 * Created by PhpStorm.
 * User: Jenni
 * Date: 19/05/2017
 * Time: 11:18*/
