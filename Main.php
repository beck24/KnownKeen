<?php

    namespace IdnoPlugins\KnownKeen {

        class Main extends \Idno\Common\Plugin {
            function registerPages() {
				// Administration page
                \Idno\Core\site()->addPageHandler('admin/knownkeen','\IdnoPlugins\KnownKeen\Pages\Admin');
				
                \Idno\Core\site()->template()->extendTemplate('shell/footer','KnownKeen/footer');
				\Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/knownkeen/menu');
            }
			
			function registerEventHooks() {
				// load our API
				require_once __DIR__ . '/vendor/autoload.php';
				
				// log a page view
				//$url = \Idno\Core\site()->currentPage()->currentUrl();
				
				//$client = \IdnoPlugins\KnownKeen\Keen\KnownKeenIO::getClient();
				//$client->addEvent('pageview', ['url' => $url]);
			}
        }

    }