<?php

namespace IdnoPlugins\KnownKeen\Keen {

	use KeenIO\Client\KeenIOClient;

	class KnownKeenIO {

		/**
		 * gets the KeenIOClient configured with our settings
		 * 
		 * @return KeenIOClient
		 */
		public static function getClient() {
			$client = KeenIOClient::factory([
						'projectId' => \Idno\Core\site()->config()->keen_project_id,
						'writeKey' => \Idno\Core\site()->config()->keen_write_api_key,
						'readKey' => \Idno\Core\site()->config()->keen_read_api_key
			]);

			return $client;
		}

		/**
		 * 
		 * 
		 * @param string $collection
		 * @param array $data
		 */
		public static function recordData($collection, $data) {
			if (!isset($GLOBALS['keenevents'])) {
				$GLOBALS['keenevents'] = array();
			}

			// add in our time
			if (!isset($data['keen'])) {
				$data['keen'] = array(
					'timestamp' => gmdate('c')
				);
			}

			if (!isset($data['user'])) {
				$data['user'] = self::getUserData();
			}

			if (!isset($data['referrer'])) {
				$data['referrer'] = self::getReferrerData();
			}

			if (!isset($data['useragent'])) {
				$data['useragent'] = self::getUserAgentData();
			}

			$GLOBALS['keenevents'][$collection][] = $data;
		}

		/**
		 * send our events to keen.io
		 */
		public static function sendData() {
			if (isset($GLOBALS['keenevents']) && $GLOBALS['keenevents']) {
				try {
					$client = \IdnoPlugins\KnownKeen\Keen\KnownKeenIO::getClient();
					$client->addEvents($GLOBALS['keenevents']);
				} catch (Exception $exc) {
					// @TODO - anything?
				}
			}
		}

		/**
		 * record a page view
		 */
		public static function recordPageView() {
			$url = \Idno\Core\site()->currentPage()->currentUrl();
			$parts = parse_url($url);

			$data = array(
				'url' => array(
					'source' => $url,
					'protocol' => isset($parts['scheme']) ? $parts['scheme'] : '',
					'domain' => isset($parts['host']) ? $parts['host'] : '',
					'port' => isset($parts['port']) ? $parts['port'] : '',
					'path' => isset($parts['path']) ? $parts['path'] : '',
					'query' => isset($parts['query']) ? $parts['query'] : '',
					'anchor' => isset($parts['fragment']) ? $parts['fragment'] : ''
				)
			);

			self::recordData('pageviews', $data);
		}

		/**
		 * standard global data regarding the currently logged in user
		 */
		public static function getUserData($user = null) {
			static $usercache;
			if (!is_array($usercache)) {
				$usercache = array();
			}

			if ($user === null) {
				$user = \Idno\Core\site()->session()->currentUser();
			}

			// no need to re-run everything
			if ($user && isset($usercache[$user->handle])) {
				return $usercache[$user->handle];
			}

			$data = array(
				'uuid' => $user ? $user->getUUID() : '',
				'name' => $user ? $user->getName() : '',
				'username' => $user ? $user->handle : '',
				'ip' => self::getIP(),
				'created' => $user ? gmdate('c', $user->created) : '',
				'is_admin' => $user ? $user->isAdmin() : false
			);

			// cache it as we're likely to need it again
			if ($user) {
				$usercache[$user->handle] = $data;
			}

			return $data;
		}

		/**
		 * 
		 * @return string ip address
		 */
		public static function getIP() {
			static $realip;
			if ($realip) {
				return $realip;
			}

			// note we need to look at these values first before REMOTE_ADDR
			// as cloud hosting routes through other servers giving false
			// or invalid internal ips
			if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
				$realip = $_SERVER['HTTP_CLIENT_IP'];
			} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			} else {
				$realip = $_SERVER['REMOTE_ADDR'];
			}
			return $realip;
		}

		public static function recordLoginSuccess($event) {
			$eventdata = $event->data();
			$user = $eventdata['user'];
			if (!($user instanceof \Idno\Entities\User)) {
				return;
			}

			$data = array(
				'event' => array(
					'name' => 'login/success',
					'data' => self::getUserData($user)
				)
			);

			self::recordData('events', $data);
		}
		
		
		public static function recordEntitySave($event) {
			$eventdata = $event->data();
			$object = $eventdata['object'];
			
			$data = array(
				'event' => array(
					'name' => 'save',
					'data' => self::getEntityData($object)
				)
			);
			
			self::recordData('events', $data);
		}

		public static function getReferrerData() {
			static $data;
			if ($data) {
				return $data;
			}

			$referrer_parts = parse_url($_SERVER['HTTP_REFERER']);

			$data = array(
				'source' => $_SERVER['HTTP_REFERER'],
				'protocol' => isset($referrer_parts['scheme']) ? $referrer_parts['scheme'] : '',
				'domain' => isset($referrer_parts['host']) ? $referrer_parts['host'] : '',
				'port' => isset($referrer_parts['port']) ? $referrer_parts['port'] : '',
				'path' => isset($referrer_parts['path']) ? $referrer_parts['path'] : '',
				'query' => isset($referrer_parts['query']) ? $referrer_parts['query'] : '',
				'anchor' => isset($referrer_parts['fragment']) ? $referrer_parts['fragment'] : ''
			);

			return $data;
		}

		public static function getUserAgentData() {
			static $data;
			if ($data) {
				return $data;
			}

			$ua_parser = \UAParser\Parser::create();
			$ua_result = $ua_parser->parse($_SERVER['HTTP_USER_AGENT']);

			$data = array(
				'user_agent' => $ua_result->originalUserAgent,
				'browser' => array(
					'browser' => $ua_result->ua->toString(),
					'name' => $ua_result->ua->family,
					'version' => $ua_result->ua->toVersion(),
					'major' => $ua_result->ua->major
				),
				'os' => array(
					'os' => $ua_result->os->toString(),
					'name' => $ua_result->os->family,
					'version' => $ua_result->os->major,
					'device' => $ua_result->device->family
				)
			);
			
			return $data;
		}
		
		
		public static function getEntityData($entity) {
			if (!($entity instanceof \Idno\Common\Entity)) {
				return array();
			}
			
			return array(
				'id' => $entity->getID(),
				'uuid' => $entity->getUUID(),
				'access' => $entity->getAccess(true),
				'attributes' => $entity->getAttributes(),
				'contenttype' => $entity->getContentTypeCategoryTitle(),
				'ownerID' => $entity->getOwnerID(),
				'tags' => $entity->getTags(),
				'title' => $entity->getTitle(),
				'url' => $entity->getURL(),
			);
		}

	}

}