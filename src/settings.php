<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Backend settings page class, can have settings fields or data table
 */
if ( ! class_exists( 'CONTENT_CONNECTOR_SETTINGS' ) ) {

	final class CONTENT_CONNECTOR_SETTINGS {

		public $capability;
		public $sub_menu_page;


		// Add basic actions for menu and settings
		public function __construct() {

			$this->capability = 'manage_options';

			$this->sub_menu_page = array(
									'name' => __('Content Connector', 'content-connector'),
									'heading' => __('Content Connector', 'content-connector'),
									'slug' => 'content-connector',
									'parent_slug' => 'options-general.php'
								);

			add_action( 'admin_menu', array( $this, 'sub_menu_page' ) );
		}


		//Add a sample Submenu page callback
		public function sub_menu_page() {

			if ($this->sub_menu_page) {
				add_submenu_page(
					$this->sub_menu_page['parent_slug'],
					$this->sub_menu_page['name'],
					$this->sub_menu_page['heading'],
					$this->capability,
					$this->sub_menu_page['slug'],
					array( $this, 'menu_page_callback' )
				);
			}
		}


		// Menu page callback
		public function menu_page_callback() { ?>

			<div class="container-fluid cc-back">
				<div class="container-fluid cc-back-inner">
					<div class="cc-form-container">
						<div class="row justify-content-md-center">
    					<div class="col col-md-4 cc-form-box-container cc-blue-bg cc-shadow-alt">
								<div class="cc-form-box">
      						<img src="<?php echo CONTENT_CONNECTOR_IMAGE . 'logo.jpg'; ?>" class="mx-auto d-block" width="100%" height="" />
								</div>
    					</div>
    					<div class="col col-md-4 cc-form-box-container cc-white-bg cc-shadow">
								<div class="cc-form-box text-center">
									<h2><?php _e('Nueva conexión', 'content-connector'); ?></h2>
									<?php
									$this->connect();

									$cc_key = get_option('cc_key');
									$cc_blog_ID = get_option('cc_blog_ID');
									$cc_author = get_option('cc_author');
									$cc_blog_name = get_option('cc_blog_name');
									?>
									<form method="post" action="">
										<div class="form-row">
											<input type="text" name="cc_key" class="form-control" placeholder="<?php _e( 'Pide la Clave a Contenidos Click', 'content-connector' ); ?>" value="<?php echo $cc_key; ?>" required/>
										</div>
										<div class="form-row" style="display: none;">
											<input type="text" name="cc_blog_ID" class="form-control" placeholder="<?php _e( 'Blog ID', 'content-connector' ); ?>" value="<?php echo $cc_blog_ID; ?>" />
										</div>
										<div class="form-row">
											<select name="cc_author" class="form-control" required>
												<option><?php _e( 'Seleccione un perfil con permisos de editor', 'content-connector' ); ?></option>
												<?php $editors = get_users( array('role__in' => array('editor')) ); ?>
												<?php
												foreach ($editors as $editor):
													$auth_ID = $editor->data->ID;
													$name = $editor->data->display_name;
													echo '<option value="' . $auth_ID . '" ' . selected($auth_ID, $cc_author, false) . '">' . $name . '</option>';
												endforeach; ?>
											</select>
										</div>
										<div class="form-row">
											<input type="text" name="cc_blog_name" class="form-control" placeholder="<?php _e( 'Nombre del blog', 'content-connector' ); ?>" value="<?php echo $cc_blog_name; ?>" required/>
										</div>
										<div class="form-row justify-content-md-center">
											<div class="col col-md-4">
												<input type="hidden" name="cc_connect" value="<?php echo wp_create_nonce('cc_connect'); ?>" />
												<input type="submit" name="cc_submit" class="btn btn-dark cc-btn" value="<?php _e( 'Conectar', 'content-connector' ); ?>" />
											</div>
										</div>
									</form>
									<div class="form-row" style="display: block;">
											<br>
											<h4 style="font-size: 1.45rem;">INTRUCCIONES DE CONFIGURACIÓN</h4>
											<h6>1-Pide la clave de conexión a Contenidos Click</h6>
											<h6>2-Crea o utiliza un perfil con permisos de editor</h6>
											<h6>3-Escribe el nombre para identificar el blog</h6>
											<h6>4-Conecta para finalizar</h6>
											<br>
											<h6><a href="https://youtu.be/F8c9CU6iwQI" target="_blank" >¿Dudas? Mira este Video</a></h6>
											<br>
											<h6 style="color: #ee8d26;font-size: 1.05rem;">Los contenidos se publicarán a medida que los redactemos</h6>
									</div>
								</div>
    					</div>
  					</div>
					</div>
				</div>
			</div>
		<?php
		}


		//Field explanation
		public function connect() {

			if(! current_user_can('manage_options')) return false;

			$cc_nonnce = (isset($_POST['cc_connect']) ? wp_kses_post($_POST['cc_connect']) : false);
			$verify = wp_verify_nonce($cc_nonnce, 'cc_connect');

			if ($verify) {

				$cc_key = (isset($_POST['cc_key']) ? sanitize_text_field($_POST['cc_key']) : false);
				$cc_blog_ID = (isset($_POST['cc_blog_ID']) ? sanitize_text_field($_POST['cc_blog_ID']) : false);
				$cc_author = (isset($_POST['cc_author']) ? sanitize_text_field($_POST['cc_author']) : false);
				$cc_blog_name = (isset($_POST['cc_blog_name']) ? sanitize_text_field($_POST['cc_blog_name']) : false);
				if ($cc_key && $cc_author && $cc_blog_name) {

					$connect = new CONTENT_CONNECTOR_CONNECT();
					$connect->key = $cc_key;
					$connect->blog_ID = $cc_blog_ID;
					$connect->author = $cc_author;
					$connect->blog_name = $cc_blog_name;
					$connect->build();
				}
			}
		}
	}
} ?>
