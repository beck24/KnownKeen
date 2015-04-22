<?php

    namespace IdnoPlugins\KnownKeen {

        class Main extends \Idno\Common\Plugin {
			
			function registerEventHooks() {
				// load our API
				require_once __DIR__ . '/vendor/autoload.php';
				
				// send all data in a single call at the end of the script
				register_shutdown_function(function() {
					\IdnoPlugins\KnownKeen\Keen\KnownKeenIO::sendData();
				});
				
				// register our event listeners
				$listener = new \IdnoPlugins\KnownKeen\Keen\KnownKeenIO();

				foreach ($listener->eventmap as $event => $method) {
					$attr = 'keen_event_' . $event;
					if (\Idno\Core\site()->config()->$attr) {
						\Idno\Core\site()->addEventHook($event, array($listener, $method));	
					}
				}
			}
			
            function registerPages() {
				// Administration page
                \Idno\Core\site()->addPageHandler('admin/knownkeen','\IdnoPlugins\KnownKeen\Pages\Admin');

				\Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/knownkeen/menu');
				
				// only record pageviews if the plugin setting is active
				if (\Idno\Core\site()->config()->keen_pageviews) {
					\Idno\Core\site()->template()->extendTemplate('shell/footer','keen/pageview');
				}
            }
        }

    }