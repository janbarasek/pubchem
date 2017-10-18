<?php

require_once("vendor/autoload.php");
require_once("src/IPubChem.php");
require_once("src/PubChem.php");


class PubChemTest extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testMinimumViableTest()
    {
        /** @noinspection PhpUndefinedMethodInspection */
        $this->assertTrue(true, "true didn't end up being false!");
    }

    public function testFetch()
    {
        $pubchem = new \kdaviesnz\pubchem\PubChem(280);
        echo $pubchem;

    }

}
