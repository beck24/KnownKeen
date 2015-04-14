<?php

namespace IdnoPlugins\KnownKeen\Keen {

	use KeenIO\Client\KeenIOClient;

	class KnownKeenIO {

		public static function getClient() {
			$client = KeenIOClient::factory([
						'projectId' => \Idno\Core\site()->config()->keen_project_id,
						'writeKey' => \Idno\Core\site()->config()->keen_write_api_key,
						'readKey' => \Idno\Core\site()->config()->keen_read_api_key
			]);
			
			return $client;
		}

	}

}