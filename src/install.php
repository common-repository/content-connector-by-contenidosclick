<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Implimentation of WordPress inbuilt functions for plugin activation.
 */
if ( ! class_exists( 'CONTENT_CONNECTOR_INSTALL' ) ) {

	final class CONTENT_CONNECTOR_INSTALL {

		public $textDomin;
		public $phpVerAllowed;


		public function execute() {

			add_action( 'plugins_loaded', array( $this, 'text_domain_cb' ) );
			add_action( 'admin_notices', array( $this, 'php_ver_incompatible' ) );
		}


		//Load plugin textdomain
		public function text_domain_cb() {

			$locale = is_admin() && function_exists('get_user_locale') ? get_user_locale() : get_locale();
			$locale = apply_filters('plugin_locale', $locale, $this->textDomin);

			unload_textdomain($this->textDomin);
			load_textdomain($this->textDomin, CONTENT_CONNECTOR_TRANSLATE . 'content-connector-' . $locale . '.mo');
			load_plugin_textdomain( $this->textDomin, false, CONTENT_CONNECTOR_TRANSLATE );
		}


		//Define low php verson errors
		public function php_ver_incompatible() {

			if ( version_compare( phpversion(), $this->phpVerAllowed, '<' ) ) :
				$text = __( 'The Plugin can\'t be activated because your PHP version', 'textdomain' );
				$text_last = __( 'is less than required '.$this->phpVerAllowed.'. See more information', 'textdomain' );
				$text_link = 'php.net/eol.php'; ?>

				<div id="message" class="updated notice notice-success is-dismissible"><p><?php echo $text . ' ' . phpversion() . ' ' . $text_last . ': '; ?><a href="http://php.net/eol.php/" target="_blank"><?php echo $text_link; ?></a></p></div>
			<?php endif; return;
		}
	}
} ?>
