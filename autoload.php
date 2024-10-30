<?php
if ( ! defined( 'ABSPATH' ) ) exit;

//Main plugin object to define the plugin
if ( ! class_exists( 'CONTENT_CONNECTOR_BUILD' ) ) {

	final class CONTENT_CONNECTOR_BUILD {


		public function installation() {

			if (class_exists('CONTENT_CONNECTOR_INSTALL')) {

				$install = new CONTENT_CONNECTOR_INSTALL();
				$install->textDomin = 'content-connector';
				$install->phpVerAllowed = '5.4';
				$install->execute();
			}
		}


		public function db_uninstall() {

			$options = array(
								'cc_key',
								'cc_blog_ID',
								'cc_author',
								'cc_blog_name'
							);
			foreach ($options as $value) {
				delete_option($value);
			}
		}


		public function custom_cron_hook_cb() {

			add_action('content_conector_by_contenidosclick', array( $this, 'do_cron_job_function'));
		}


		public function do_cron_job_function() {

			if ( class_exists( 'CONTENT_CONNECTOR_IMPORT' ) ) new CONTENT_CONNECTOR_IMPORT();
		}


		public function cron_deactivation() {

			$cron = new CONTENT_CONNECTOR_CRON();
			$hook = 'content_conector_by_contenidosclick';
			$cron->unschedule_task($hook);
		}

		//Include scripts
		public function scripts() {

			if ( class_exists( 'CONTENT_CONNECTOR_SCRIPT' ) ) new CONTENT_CONNECTOR_SCRIPT();
		}


		//Include settings pages
		public function settings() {

			if ( class_exists( 'CONTENT_CONNECTOR_SETTINGS' ) ) new CONTENT_CONNECTOR_SETTINGS();
		}


		//Add functionality files
		public function functionality() {

			require_once ('src/install.php');
			require_once ('src/settings.php');
		}


		//Call the dependency files
		public function helpers() {

			require_once ('lib/connect.php');
			require_once ('lib/import.php');
			require_once ('lib/script.php');
			require_once ('lib/cron.php');
		}


		public function __construct() {

			$this->helpers();
			$this->functionality();

			register_uninstall_hook( CONTENT_CONNECTOR_FILE, array( 'CONTENT_CONNECTOR_BUILD', 'db_uninstall' ) );
			register_deactivation_hook( CONTENT_CONNECTOR_FILE, array($this, 'cron_deactivation' ));

			add_action('init', array($this, 'installation'));

			$this->scripts();
			$this->settings();

			add_action('init', array($this, 'custom_cron_hook_cb'));
		}
	}
} ?>
