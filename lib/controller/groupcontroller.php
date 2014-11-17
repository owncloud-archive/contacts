<?php
/**
 * @author Thomas Tanghus
 * @copyright 2013-2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App,
	OCA\Contacts\JSONResponse,
	OCA\Contacts\Controller,
	OCP\AppFramework\Http,
	OCP\ITags,
	OCP\IRequest;

/**
 * Controller class for groups/categories
 */
class GroupController extends Controller {

	public function __construct($appName, IRequest $request, App $app, ITags $tags) {
		parent::__construct($appName, $request, $app);
		$this->app = $app;
		$this->tags = $tags;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getGroups() {
		$tags = $this->tags->getTags();

		foreach ($tags as &$tag) {
			try {
				$ids = $this->tags->getIdsForTag($tag['id']);
				$tag['contacts'] = $ids;
				$tag['displayname'] = $this->displayName($tag);
			} catch(\Exception $e) {
				\OCP\Util::writeLog('contacts', __METHOD__ . ', ' . $e->getMessage(), \OCP\Util::ERROR);
			}
		}

		$favorites = $this->tags->getFavorites();

		$shares = \OCP\Share::getItemsSharedWith('addressbook', \OCA\Contacts\Share\Addressbook::FORMAT_ADDRESSBOOKS);
		$addressbookShare = new \OCA\Contacts\Share\Addressbook();
		foreach ($shares as $key => $share) {
			$children = $addressbookShare->getChildren($share['id']); // FIXME: This should be cheaper!
			$shares[$key]['length'] = count($children);
		}

		$groups = array(
			'categories' => $tags,
			'favorites' => $favorites,
			'shared' => $shares,
			'lastgroup' => \OCP\Config::getUserValue(\OCP\User::getUser(), 'contacts', 'lastgroup', 'all'),
			'sortorder' => \OCP\Config::getUserValue(\OCP\User::getUser(), 'contacts', 'groupsort', ''),
			);

		return new JSONResponse($groups);
	}

	/**
	 * @NoAdminRequired
	 */
	public function addGroup() {
		$name = $this->request->post['name'];

		$response = new JSONResponse();

		if (empty($name)) {
			$response->bailOut(App::$l10n->t('No group name given.'));
		}

		$id = $this->tags->add($name);

		if ($id === false) {
			$response->bailOut(App::$l10n->t('Error adding group.'));
		} else {
			$response->setParams(array('id'=>$id, 'name' => $name));
		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteGroup() {
		$id = $this->request->post['id'];
		$name = $this->request->post['name'];

		$response = new JSONResponse();
		if (empty($id)) {
			$response->bailOut(App::$l10n->t('No group ID given.'));
			return $response;
		}

		try {
			$ids = $this->tags->getIdsForTag($id);
		} catch(\Exception $e) {
			$response->setErrorMessage($e->getMessage());
			\OCP\Util::writeLog('contacts', __METHOD__.', ' . $e->getMessage(), \OCP\Util::ERROR);
			return $response;
		}

		$tagId = $id;
		if ($ids !== false) {

			$backend = $this->app->getBackend('local');

			foreach ($ids as $id) {
				$contact = $backend->getContact(null, $id, array('noCollection' => true));
				$obj = \Sabre\VObject\Reader::read(
					$contact['carddata'],
					\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
				);

				if ($obj) {

					if (!$obj->inGroup($name)) {
						continue;
					}

					if ($obj->removeFromGroup($name)) {
						$backend->updateContact(null, $id, $obj, array('noCollection' => true, 'isBatch' => true));
					}

				} else {
					\OCP\Util::writeLog('contacts', __METHOD__.', could not parse card ' . $id, \OCP\Util::DEBUG);
				}
			}

		}

		try {
			$this->tags->delete($tagId);
		} catch(\Exception $e) {
			$response->setErrorMessage($e->getMessage());
			\OCP\Util::writeLog('contacts', __METHOD__.', ' . $e->getMessage(), \OCP\Util::ERROR);
		}
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function renameGroup() {
		$from = $this->request->post['from'];
		$to = $this->request->post['to'];

		$response = new JSONResponse();

		if (empty($from)) {
			$response->bailOut(App::$l10n->t('No group name to rename from given.'));
			return $response;
		}

		if (empty($to)) {
			$response->bailOut(App::$l10n->t('No group name to rename to given.'));
			return $response;
		}

		if (!$this->tags->rename($from, $to)) {
			$response->bailOut(App::$l10n->t('Error renaming group.'));
			return $response;
		}

		$tag = $this->tags->getTag($from);

		if (!$tag) {
			$response->bailOut(App::$l10n->t('Error renaming group.'));
			return $response;
		}

		$response->setParams(array('displayname'=>$this->displayName($tag)));
		$ids = $this->tags->getIdsForTag($to);

		if ($ids !== false) {

			$backend = $this->app->getBackend('local');

			foreach ($ids as $id) {
				$contact = $backend->getContact(null, $id, array('noCollection' => true));
				$obj = \Sabre\VObject\Reader::read(
					$contact['carddata'],
					\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
				);

				if ($obj) {

					if (!isset($obj->CATEGORIES)) {
						continue;
					}

					$obj->CATEGORIES->renameGroup($from, $to);
					$backend->updateContact(null, $id, $obj, array('noCollection' => true));

				} else {
					\OCP\Util::writeLog('contacts', __METHOD__.', could not parse card ' . $id, \OCP\Util::DEBUG);
				}

			}

		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function addToGroup() {
		$response = new JSONResponse();
		$params = $this->request->urlParams;
		$categoryId = $params['categoryId'];
		$categoryName = $this->request->post['name'];
		$ids = $this->request->post['contactIds'];
		$response->debug('request: '.print_r($this->request->post, true));

		if (empty($categoryId)) {
			throw new \Exception(
				App::$l10n->t('Group ID missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		if (empty($categoryName)) {
			throw new \Exception(
				App::$l10n->t('Group name missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		if (is_null($ids)) {
			throw new \Exception(
				App::$l10n->t('Contact ID missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		$backend = $this->app->getBackend('local');

		foreach ($ids as $contactId) {

			$contact = $backend->getContact(null, $contactId, array('noCollection' => true));
			$obj = \Sabre\VObject\Reader::read(
				$contact['carddata'],
				\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
			);

			if ($obj) {

				if ($obj->addToGroup($categoryName)) {
					$backend->updateContact(null, $contactId, $obj, array('noCollection' => true));
				}

			}

			$response->debug('contactId: ' . $contactId . ', categoryId: ' . $categoryId);
			$this->tags->tagAs($contactId, $categoryId);
		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function removeFromGroup() {
		$response = new JSONResponse();
		$params = $this->request->urlParams;
		$categoryId = $params['categoryId'];
		$categoryName = $this->request->post['name'];
		$ids = $this->request->post['contactIds'];
		//$response->debug('request: '.print_r($this->request->post, true));

		if (empty($categoryId)) {
			throw new \Exception(
				App::$l10n->t('Group ID missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		if (empty($categoryName)) {
			throw new \Exception(
				App::$l10n->t('Group name missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		if (is_null($ids)) {
			throw new \Exception(
				App::$l10n->t('Contact ID missing from request.'),
				Http::STATUS_PRECONDITION_FAILED
			);
		}

		$backend = $this->app->getBackend('local');

		foreach ($ids as $contactId) {

			$contact = $backend->getContact(null, $contactId, array('noCollection' => true));

			if (!$contact) {
				$response->debug('Couldn\'t get contact: ' . $contactId);
				continue;
			}

			$obj = \Sabre\VObject\Reader::read(
				$contact['carddata'],
				\Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES
			);

			if ($obj) {

				if (!isset($obj->CATEGORIES)) {
					return $response;
				}

				if ($obj->removeFromGroup($categoryName)) {
					$backend->updateContact(null, $contactId, $obj, array('noCollection' => true));
				}

			} else {
				$response->debug('Error parsing contact: ' . $contactId);
			}

			$response->debug('contactId: ' . $contactId . ', categoryId: ' . $categoryId);
			$this->tags->unTag($contactId, $categoryId);
		}

		return $response;
	}

	/**
	* Returns a tag's name as it should be displayed.
	*
	* @param Tag
	* @return string
	*
	* If the tag belongs to the current user, simply returns the tag's name.
	* Otherwise, the tag's name is returned with it's owner's name appended
	* in parentheses, like "Tag (owner)".
	*/
	private function displayName($tag) {
		if ($tag['owner'] != \OCP\User::getUser()) {
			return $tag['name'] . ' ('. $tag['owner'] . ')';
		}
		return $tag['name'];
	}
}

