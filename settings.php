<?php

$tmpl = new OCP\Template( 'contacts', 'settings');
$tmpl->assign('addressbooks', OCA\Contacts\Addressbook::all(\OC::$server->getUserSession()->getUser()->getUId()));

$tmpl->printPage();
