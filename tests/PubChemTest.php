<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '../src/IPubChem.php';
require_once __DIR__ . '/../src/PubChem.php';


class PubChemTest extends PHPUnit_Framework_TestCase
{
	public function setUp(): void
	{
	}


	public function tearDown(): void
	{
	}


	public function testMinimumViableTest(): void
	{
		/** @noinspection PhpUndefinedMethodInspection */
		$this->assertTrue(true, 'true didn\'t end up being false!');
	}


	public function testFetch(): void
	{
		$pubChem = new \kdaviesnz\pubchem\PubChem(280);
		echo $pubChem;
	}
}
