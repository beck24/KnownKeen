<?php

namespace IdnoPlugins\KnownKeen\Keen {

	use KeenIO\Client\KeenIOClient;

	class KnownKeenIO {
		
		public $eventmap = array(
			'annotation/add/comment' => 'recordAnnotationComment',
			'annotation/add/like' => 'recordAnnotationLike',
			'annotation/add/mention' => 'recordAnnotationMention',
			'annotation/add/reply' => 'recordAnnotationReply',
			'annotation/add/rsvp' => 'recordAnnotationRSVP',
			'annotation/add/share' => 'recordAnnotationShare',
			'delete' => 'recordEntityDelete',
			'email/send' => 'recordSendEmail',
			'follow' => 'recordFollow',
			'login/failure' => 'recordLoginFailure',
			'login/failure/nouser' => 'recordLoginFailureNoUser',
			'login/success' => 'recordLoginSuccess',
			'logout/success' => 'recordLogOut',
			'save' => 'recordEntitySave',
			'site/newuser' => 'recordNewUser',
			'updated' => 'recordEntityUpdated'
		);
		
		public $eventDescriptions = array(
			'annotation/add/comment' => 'Record data on new comments',
			'annotation/add/like' => 'Record when content is liked',
			'annotation/add/mention' => 'Record mentions',
			'annotation/add/reply' => 'Record when replies are made to content',
			'annotation/add/rsvp' => 'Record when RSVP\'s are made',
			'annotation/add/share' => 'Record when content is shared',
			'delete' => 'Record when content is deleted',
			'email/send' => 'Record when email is sent',
			'follow' => 'Record new followings',
			'login/failure' => 'Record login failures',
			'login/failure/nouser' => 'Record login failures due to invalid handle/email',
			'login/success' => 'Record successful logins',
			'logout/success' => 'Record successful logouts',
			'save' => 'Record when content is created',
			'site/newuser' => 'Record when new users are created',
			'updated' => 'Record when content is updated'
		);

		/**
		 * get a configured client for the keen API
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
		 * @param string           $name  name of the annotation type
		 * @param \Idno\Core\Event $event the annotation event
		 * 
		 * @return void
		 */
		private function recordAnnotation($name, $event) {
			$eventdata = $event->data();
			$annotation = $eventdata['annotation'];
			$entity = $eventdata['object'];
			
			$data = array(
				'event' => array(
					'name' => $name,
					'data' => self::getAnnotationData($annotation, $entity)
				)
			);
			
			self::recordData('events', $data);
		}

		/**
		 * 
		 * @param string $collection
		 * @param array  $data
		 * 
		 * @return void
		 */
		private function recordData($collection, $data) {
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
		 * send our data to keen.
		 * @return void
		 */
		public static function sendData() {
			if (isset($GLOBALS['keenevents']) && $GLOBALS['keenevents']) {
				try {
					
					// reload any entity info from the save event
					// as it doesn't have an id at that point
					if (isset($GLOBALS['keenevents']['events'])) {
						foreach ($GLOBALS['keenevents']['events'] as $key => $save) {
							if ($save['event']['name'] != 'save') {
								continue;
							}
							$entity = \Idno\Common\Entity::getByUUID($save['event']['data']['uuid']);
							$GLOBALS['keenevents']['events'][$key]['event']['data'] = self::getEntityData($entity);
						}
					}
					
					$client = \IdnoPlugins\KnownKeen\Keen\KnownKeenIO::getClient();
					$client->addEvents($GLOBALS['keenevents']);
					unset($GLOBALS['keenevents']); // prevent duplicate data if called multiple times per thread
				} catch (Exception $exc) {
					// @TODO - anything?
				}
			}
		}

		/**
		 * Record pageview data
		 * @return void
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
		 * @param \Idno\Entities\User $user
		 * 
		 * @return array
		 */
		private function getUserData($user = null) {
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
		private function getIP() {
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

		/**
		 * 
		 * @param \Idno\Core\Event $event the event
		 * @return void
		 */
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
		
		/**
		 * 
		 * @param \Idno\Core\Event $event the event
		 * @return void
		 */
		public static function recordEntityDelete($event) {
			$eventdata = $event->data();
			$object = $eventdata['object'];
			
			$data = array(
				'event' => array(
					'name' => 'delete',
					'data' => self::getEntityData($object, true)
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * 
		 * @param \Idno\Core\Event $event the event
		 * @return void
		 */
		public static function recordEntitySave($event) {
			$eventdata = $event->data();
			$object = $eventdata['object'];
			
			$id = $object->getID();
			if ($id) {
				return; // this was previously created, save event called in duplicate
			}
			
			$data = array(
				'event' => array(
					'name' => 'save',
					'data' => self::getEntityData($object, true)
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * 
		 * @param \Idno\Core\Event $event the event
		 * @return void
		 */
		public static function recordEntityUpdated($event) {
			$eventdata = $event->data();
			$object = $eventdata['object'];
			
			$data = array(
				'event' => array(
					'name' => 'updated',
					'data' => self::getEntityData($object, true)
				)
			);
			
			self::recordData('events', $data);
		}

		/**
		 * 
		 * @staticvar array $data
		 * @return array
		 */
		private function getReferrerData() {
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

		/**
		 * 
		 * @staticvar array $data
		 * @return array
		 */
		private function getUserAgentData() {
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
		
		/**
		 * 
		 * @param array               $annotation annotation data
		 * @param \Idno\Common\Entity $entity     the entity
		 * @return array
		 */
		private function getAnnotationData($annotation, $entity) {
			
			$data = array(
				'permalink' => $annotation['permalink'],
				'owner_name' => $annotation['owner_name'],
				'content' => $annotation['content'],
				'title' => $annotation['title'],
				'entity' => self::getEntityData($entity)
			);
			
			return $data;
		}
		
		/**
		 * 
		 * @param \Idno\Common\Entity $entity     the entity
		 * @param bool                $attributes include entity details/annotations?
		 * @return array
		 */
		private function getEntityData($entity, $attributes = false) {
			if (!($entity instanceof \Idno\Common\Entity)) {
				return array();
			}
			
			$data = array(
				'id' => $entity->getID(),
				'uuid' => $entity->getUUID(),
				'access' => $entity->getAccess(true),
				'contenttype' => $entity->getContentTypeCategoryTitle(),
				'ownerID' => $entity->getOwnerID(),
				'tags' => $entity->getTags(),
				'title' => $entity->getTitle(),
				'url' => $entity->getURL(),
			);
			
			if ($attributes) {
				$data['attributes'] = $entity->getAttributes();
			}
			
			return $data;
		}
		
		/**
		 * Record reply annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationReply($event) {
			self::recordAnnotation('annotation/add/reply', $event);
		}
		
		/**
		 * Record share annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationShare($event) {
			self::recordAnnotation('annotation/add/share', $event);
		}
		
		/**
		 * Record mention annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationMention($event) {
			self::recordAnnotation('annotation/add/mention', $event);
		}
		
		/**
		 * Record like annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationLike($event) {
			self::recordAnnotation('annotation/add/like', $event);
		}
		
		/**
		 * Record RSVP annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationRSVP($event) {
			self::recordAnnotation('annotation/add/rsvp', $event);
		}
		
		/**
		 * Record comment annotations
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordAnnotationComment($event) {
			self::recordAnnotation('annotation/add/comment', $event);
		}
		
		/**
		 * Record sent emails
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordSendEmail($event) {
			$eventdata = $event->data();
			$email = $eventdata['email'];
			$to = $email->message->getTo();
			$from = $email->message->getFrom();
			
			$to_email = array_shift(array_keys($to));
			$from_email = array_shift(array_keys($from));
			
			$data = array(
				'event' => array(
					'name' => 'email/send',
					'data' => array(
						'to' => $to_email,
						'from' => $from_email,
						'subject' => $email->message->getSubject()
					)
				)
			);

			self::recordData('events', $data);
		}
		
		/**
		 * Record follow events
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordFollow($event) {
			$eventData = $event->data();
			$user = $eventData['user'];
			$following = $eventData['following'];
			
			$data = array(
				'event' => array(
					'name' => 'follow',
					'data' => array(
						'user' => self::getUserData($user),
						'following' => self::getUserData($following)
					)
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * Record when new users are created
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordNewUser($event) {
			$eventData = $event->data();
			$user = $eventData['user'];
			
			$data = array(
				'event' => array(
					'name' => 'site/newuser',
					'data' => array(
						'user' => self::getUserData($user)
					)
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * Record when someone fails to log in due to invalid email/handle
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordLoginFailureNoUser($event) {
			$eventData = $event->data();
			
			$data = array(
				'event' => array(
					'name' => 'login/failure/nouser',
					'data' => $eventData
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * Record login failures
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordLoginFailure($event) {
			$eventData = $event->data();
			$user = $eventData['user'];
			
			$data = array(
				'event' => array(
					'name' => 'login/failure',
					'data' => array(
						'user' => self::getUserData($user)
					)
				)
			);
			
			self::recordData('events', $data);
		}
		
		/**
		 * Record logout
		 * 
		 * @param \Idno\Core\Event $event
		 */
		public static function recordLogOut($event) {
			$eventData = $event->data();
			$user = $eventData['user'];
			
			$data = array(
				'event' => array(
					'name' => 'logout/success',
					'data' => array(
						'user' => self::getUserData($user)
					)
				)
			);
			
			self::recordData('events', $data);
		}
	}
}