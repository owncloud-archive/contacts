<?php

namespace OCA\Contacts\Backend;

class Mock extends AbstractBackend {

	public $name = 'mock';
    public $addressBooks;
    public $contacts;

    function __construct($userid = null, $addressBooks = null, $contacts = null) {

		$this->userid = $userid ? $userid : \OCP\User::getUser();
        $this->addressBooks = $addressBooks;
        $this->contacts = $contacts;

        if (is_null($this->addressBooks)) {
            $this->addressBooks = array(
                array(
                    'id' => 'foo',
                    'owner' => 'user1',
                    'displayname' => 'd-name',
                ),
            );

            $card2 = fopen('php://memory','r+');
            fwrite($card2,"BEGIN:VCARD\nVERSION:3.0\nUID:45678\nEND:VCARD");
            rewind($card2);
            $this->contacts = array(
                'foo' => array(
                    'card1' => "BEGIN:VCARD\nVERSION:3.0\nUID:12345\nEND:VCARD",
                    'card2' => $card2,
                ),
            );
        }

    }


    function getAddressBooksForUser($userid = null) {

        $books = array();
        foreach($this->addressBooks as $book) {
            if ($book['owner'] === $userid) {
                $books[] = $book;
            }
        }
        return $books;

    }

    function updateAddressBook($addressBookId, array $mutations) {

        foreach($this->addressBooks as &$book) {
            if ($book['id'] !== $addressBookId)
                continue;

            foreach($mutations as $key=>$value) {
                $book[$key] = $value;
            }
            return true;
        }
        return false;

    }

    function createAddressBook($principalUri, $url, array $properties) {

        $this->addressBooks[] = array_merge($properties, array(
            'id' => $url,
            'uri' => $url,
            'principaluri' => $principalUri,
        ));

    }

    function deleteAddressBook($addressBookId) {

        foreach($this->addressBooks as $key=>$value) {
            if ($value['id'] === $addressBookId)
                unset($this->addressBooks[$key]);
        }
        unset($this->contacts[$addressBookId]);

    }

    function getContacts($addressBookId) {

        $contacts = array();
        foreach($this->contacts[$addressBookId] as $uri=>$data) {
            $contacts[] = array(
                'uri' => $uri,
                'carddata' => $data,
            );
        }
        return $contacts;

    }

    function getContact($addressBookId, $id) {

        if (!isset($this->contacts[$addressBookId][$id])) {
            return false;
        }

        return array(
            'uri' => $id,
            'carddata' => $this->contacts[$addressBookId][$id],
        );

    }

    function createContact($addressBookId, $id, $contact) {

        $this->contacts[$addressBookId][$id] = $contact;

    }

    function updateContact($addressBookId, $id, $contact) {

        $this->contacts[$addressBookId][$id] = $contact;

    }

    function deleteContact($addressBookId, $id) {

        unset($this->contacts[$addressBookId][$id]);

    }

}
