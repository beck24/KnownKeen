<?php

    namespace IdnoPlugins\KnownKeen {

        class Main extends \Idno\Common\Plugin {
			function init() {
				// load our API
				require_once __DIR__ . '/vendor/autoload.php';
				
				//@TODO - move this so it triggers only for navigable pages
				//\IdnoPlugins\KnownKeen\Keen\KnownKeenIO::recordPageView();
				
				// send all data in a single call at the end of the script
				register_shutdown_function(function() {
					\IdnoPlugins\KnownKeen\Keen\KnownKeenIO::sendData();
				});
			}
			
			function registerEventHooks() {
				$eventsmap = \IdnoPlugins\KnownKeen\Keen\KnownKeenIO::$eventmap;
				$listener = new \IdnoPlugins\KnownKeen\Keen\KnownKeenIO();
				
				foreach ($eventsmap as $name => $method) {
					\Idno\Core\site()->addEventHook($name, array($listener, $method));
				}
			}
			
            function registerPages() {
				// Administration page
                \Idno\Core\site()->addPageHandler('admin/knownkeen','\IdnoPlugins\KnownKeen\Pages\Admin');
	
                \Idno\Core\site()->template()->extendTemplate('shell/footer','keen/pageview');
				\Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/knownkeen/menu');
            }
        }

    }