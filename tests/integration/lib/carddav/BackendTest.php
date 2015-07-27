<?php

namespace OCA\Contacts\CardDAV;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\App;
use OCP\Files\File;

class CardDAVBackendTest extends PHPUnit_Framework_TestCase {

    private $backend;
    private $userId = 'test';

    public function setUp() {
        $app = new App('contacts');
        $container = $app->getContainer();
        $container->registerService('UserId', function($c) {
            return $this->userId;
        });
        $this->backend = $container->query(
            'OCA\Contacts\CardDAV\Backend'
        );
    }

    public function testDoesItWork() {
        $this->assertEquals($this->app, 'something');
    }

}

?>
