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
		public static function recordEvent($collection, $data) {
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

			$referrer_parts = parse_url($_SERVER['HTTP_REFERER']);

			$ua_parser = \UAParser\Parser::create();
			$ua_result = $ua_parser->parse($_SERVER['HTTP_USER_AGENT']);

			$data = array(
				'url' => array(
					'source' => $url,
					'protocol' => isset($parts['scheme']) ? $parts['scheme'] : '',
					'domain' => isset($parts['host']) ? $parts['host'] : '',
					'port' => isset($parts['port']) ? $parts['port'] : '',
					'path' => isset($parts['path']) ? $parts['path'] : '',
					'query' => isset($parts['query']) ? $parts['query'] : '',
					'anchor' => isset($parts['fragment']) ? $parts['fragment'] : ''
				),
				'referrer' => array(
					'source' => $_SERVER['HTTP_REFERER'],
					'protocol' => isset($referrer_parts['scheme']) ? $referrer_parts['scheme'] : '',
					'domain' => isset($referrer_parts['host']) ? $referrer_parts['host'] : '',
					'port' => isset($referrer_parts['port']) ? $referrer_parts['port'] : '',
					'path' => isset($referrer_parts['path']) ? $referrer_parts['path'] : '',
					'query' => isset($referrer_parts['query']) ? $referrer_parts['query'] : '',
					'anchor' => isset($referrer_parts['fragment']) ? $referrer_parts['fragment'] : ''
				),
				'user_agent' => array(
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
				)
			);
error_log(print_r($data,1));
			self::recordEvent('pageviews', $data);
		}

		/**
		 * standard global data regarding the currently logged in user
		 */
		public static function getUserData() {
			static $usercache;
			if (!is_array($usercache)) {
				$usercache = array();
			}
			
			$user = \Idno\Core\site()->session()->currentUser();
			
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

	}

}