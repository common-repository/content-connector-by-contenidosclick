<?php
/**
 * Add scripts to the plugin. CSS and JS.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CONTENT_CONNECTOR_SCRIPT' ) ) {

	final class CONTENT_CONNECTOR_SCRIPT {


		public function __construct() {

			add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts' ) );
		}


		// Enter scripts into pages
		public function backend_scripts() {

			if (isset($_GET['page']) && $_GET['page'] == 'content-connector') {

				wp_enqueue_style( 'bootstrap-css', CONTENT_CONNECTOR_CSS . 'bootstrap.min.css', array('content-connector-font'), '4.4.1', 'all' );
				wp_enqueue_style( 'content-connector-css', CONTENT_CONNECTOR_CSS . 'cc.css', array('bootstrap-css', 'content-connector-font'), '1.0', 'all' );
				wp_enqueue_style( 'content-connector-font', 'https://fonts.googleapis.com/css?family=Lato:300,400&display=swap&subset=latin-ext', array(), '1.0', 'all' );
			}
		}
	}
} ?>
