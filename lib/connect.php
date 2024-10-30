<?php
/**
 * Implimentation of WordPress inbuilt API class
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'CONTENT_CONNECTOR_CONNECT' ) ) {

	final class CONTENT_CONNECTOR_CONNECT {


		public $key;
		public $blog_ID;
		public $author;
		public $blog_name;
		public $error_code;
		public $error_message;

		//Define the necessary database tables
		public function build() {

			$message = $this->call();

			switch ($message) {
				case '1':
					$this->cron_activation();
					$notice = $this->message(__('La conexión se ha realizado correctamente.', 'content-connector'), 'success');
					break;

				case '0':
					$this->cron_deactivation();
					$notice = $this->message(__('Ocurrió un error en la conexión, verifique los parámetros. Si el error continúa, contacte a ContenidosClick', 'content-connector'), 'danger');
					break;

				case '-1':
					$this->cron_deactivation();
					$notice = $this->message(__('Ocurrió un error Interno de Wordpress: ('.$this->error_code.'-'.$this->error_message.')', 'content-connector'), 'danger');
					break;
			}

			update_option('cc_key', $this->key);
			update_option('cc_blog_ID', $this->blog_ID);
			update_option('cc_author', $this->author);
			update_option('cc_blog_name', $this->blog_name);

			echo $notice;
		}


		//Call the API
		public function call() {

			$message = '0';

			$endpoint = 'https://panel.contenidosclick.es/contentconnector/ALTA/' . $this->key . '/' . $this->author . '/' . $this->blog_name;
			$args = array(
				'timeout'     => 120,
				'httpversion' => '1.1',
				'sslverify' => FALSE
			);

			$response = wp_remote_get( $endpoint, $args );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {

				$body    = $response['body'];
				$content = json_decode($body, 1);
				$message = (is_array($content) && array_key_exists('mensaje', $content) ? $content['mensaje'] : '0');
			}
//var_dump($response);
			$this->error_code="";
			$this->error_message="";
			if( is_wp_error( $response ) ) {
				$this->error_message=$response->get_error_message();
				$this->error_code=$response->get_error_code();
				$message = '-1';
			}
			return $message;
		}


		//Custom corn class, register it while connection
		public function cron_activation() {

			if ( class_exists( 'CONTENT_CONNECTOR_CRON' ) ) {
				$cron = new CONTENT_CONNECTOR_CRON();
				$schedule = $cron->schedule_task(
							array(
							'timestamp' => current_time('timestamp'),
							'recurrence' => 'hourly',
							'hook' => 'content_conector_by_contenidosclick'
						) );
			}
		}


		//Deactivate cron
		public function cron_deactivation() {

			$cron = new CONTENT_CONNECTOR_CRON();
			$hook = 'content_conector_by_contenidosclick';
			$cron->unschedule_task($hook);
		}


		//Message wraper
		public function message($body, $type) {

			$notice = '<div class="alert alert-' . $type . '" role="alert">' . $body . '</div>';
			return $notice;
		}
	}
} ?>
