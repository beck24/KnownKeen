<?php

    /**
     * KnownKeen administration
     */

    namespace IdnoPlugins\KnownKeen\Pages {

        /**
         * Default class to serve keen.io settings in administration
         */
        class Admin extends \Idno\Common\Page
        {

            function getContent()
            {
                $this->adminGatekeeper(); // Admins only
                $t = \Idno\Core\site()->template();
                $body = $t->draw('admin/knownkeen');
                $t->__(array('title' => 'Keen.io', 'body' => $body))->drawPage();
            }

            function postContent() {
                $this->adminGatekeeper(); // Admins only
				
                $project_id = $this->getInput('keen_project_id');
                \Idno\Core\site()->config->config['keen_project_id'] = $project_id;
				
				$read_key = $this->getInput('keen_read_api_key');
                \Idno\Core\site()->config->config['keen_read_api_key'] = $read_key;
				
				$write_key = $this->getInput('keen_write_api_key');
                \Idno\Core\site()->config->config['keen_write_api_key'] = $write_key;
				
				$project_id_test = $this->getInput('keen_project_id_test');
                \Idno\Core\site()->config->config['keen_project_id_test'] = $project_id_test;
				
				$read_key_test = $this->getInput('keen_read_api_key_test');
                \Idno\Core\site()->config->config['keen_read_api_key_test'] = $read_key_test;
				
				$write_key_test = $this->getInput('keen_write_api_key_test');
                \Idno\Core\site()->config->config['keen_write_api_key_test'] = $write_key_test;
				
				$environment = $this->getInput('keen_environment');
                \Idno\Core\site()->config->config['keen_environment'] = $environment;
				
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Your Keen.io settings have been saved');
                $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/knownkeen/');
            }

        }

    }