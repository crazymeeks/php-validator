<?php

namespace Tests;

use Crazymeeks\Validation\Validator;
use PHPUnit\Framework\TestCase as PHPUnit_Framework_TestCase;

abstract class TestCase extends PHPUnit_Framework_TestCase
{

    protected $validator;

    public function setUp()
    {
        parent::setUp();
        $_FILES = [];
        $this->validator = new Validator();
    }

    public function tearDown()
    {
        unset($_FILES, $_POST, $_GET);
        parent::tearDown();
    }
}