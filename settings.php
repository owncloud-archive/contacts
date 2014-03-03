<?php

$tmpl = new OCP\Template( 'contacts', 'settings');
$tmpl->assign('addressbooks', OCA\Contacts\Model\Addressbook::all(OCP\USER::getUser()));

$tmpl->printPage();
