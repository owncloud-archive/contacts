<?php
/**
 * @author Nicolas Mora
 * @copyright 2014 Nicolas Mora (mail@babelouest.org)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts\Controller;

use OCA\Contacts\App,
	OCA\Contacts\JSONResponse,
	OCA\Contacts\Utils\JSONSerializer,
	OCA\Contacts\Controller,
	OCP\AppFramework\Http;

/**
 * Controller class For Address Books
 */
class BackendController extends Controller {

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getLdapConnectors() {
		$params = $this->request->urlParams;
		//$targetInfo = $this->request->post['target'];

		$response = new JSONResponse();
		$prefix = "backend_ldap_";
		$suffix = "_connector.xml";
		$path = __DIR__ . "/../../formats/";
		$files = scandir($path);
		$formats = array();
		foreach ($files as $file) {
			if (!strncmp($file, $prefix, strlen($prefix)) && substr($file, - strlen($suffix)) === $suffix) {
				if (file_exists($path.$file)) {
					$format = simplexml_load_file ( $path.$file );
					if ($format) {
						if (isset($format['name'])) {
							$formatId = substr($file, strlen($prefix), - strlen($suffix));
							$formats[$formatId] = array('id' => $formatId, 'name' => (string)$format['name'], 'xml' => $format->asXML());
						}
					}
				}
			}
		}
		return $response->setData($formats);
	}
}

