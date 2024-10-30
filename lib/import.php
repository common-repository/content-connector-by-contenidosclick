<?php
/**
 *
 * Add cron task callback
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'CONTENT_CONNECTOR_IMPORT' ) ) {

	final class CONTENT_CONNECTOR_IMPORT {

		public $key;

		public function __construct() {

			$this->key = get_option('cc_key');

			$data = $this->call();

			if ($data) {
				$ready_to_import_data = $this->prepare($data);
				$this->import($ready_to_import_data);
			}
		}

		//Import the data
		public function import($data) {

			$author = get_option('cc_author');

			if ($author) {

				// Search for image on title
				$image_data_src=$this->extract_first_image($data['title']);
				$data['title']=$this->remove_first_image($data['title']);

				if(!$image_data_src) {

					// Search for image on content when not found on title
					$image_data_src=$this->extract_first_image($data['content']);
					$data['content']=$this->remove_first_image($data['content']);
				}

				// upload rest of images to wordpress
				$data['title']=$this->upload_all_images($data['title'],$data['title']);
				$data['content']=$this->upload_all_images($data['content'],$data['title']);

				// wpml check
				global $sitepress;
				if(isset($sitepress) && method_exists($sitepress, 'get_default_language')) {
					$deflang=$sitepress->get_default_language();
					do_action( 'wpml_switch_language', $deflang );
				}

				$article_info = array(
					'post_title'  => $data['title'],
					'post_content' => $data['content'],
					'post_type'   => $data['post_type'],
					'post_author' => $author,
					'post_category' => $data['category'],
					'tags_input' => $data['tags'],
					'post_status' => $data['status'],
					'post_name' => $data['post_name_slug']
				);

				// Modify allowed HTML through custom filter
				add_filter( 'wp_kses_allowed_html', 'wpse_kses_allowed_html', 10, 2 );
				kses_remove_filters();

				//Create Post
				$insert_post_id = wp_insert_post($article_info);

				//Save post created
				$this->call_cc_save_post($insert_post_id,$data['connector_dato_id']);

				// Remove custom filter
				remove_filter( 'wp_kses_allowed_html', 'wpse_kses_allowed_html', 10 );
				kses_init_filters();

				$file_name=sanitize_file_name($data['title'].".png");
				if($image_data_src) {

					$image_data=$this->get_content_from_src($image_data_src);

					// Image File
					$ext = pathinfo($image_data_src, PATHINFO_EXTENSION);
					$file_name = basename($image_data_src,".".$ext).".png";

					$attach_id=$this->guarda_imagen_wp($image_data,$file_name,$data['title'],$insert_post_id);
					set_post_thumbnail($insert_post_id, $attach_id);
				}

				if (!is_wp_error($insert_post_id)) {

					update_post_meta($insert_post_id, '_yoast_wpseo_focuskw', $data['tags']);	 // Frase clave objetivo 'keyword1 keyword2'
					update_post_meta($insert_post_id, '_yoast_wpseo_title', $data['yoast_keyphrase'] ); 	// SEO Title v1.3
					update_post_meta($insert_post_id, '_yoast_wpseo_metadesc', $data['yoast_meta_desc'] );  // SEO Meta Descr v1.3
				}
			}
		}

		// load all url and base64 to Wordpress images
		public function upload_all_images($content,$title) {

			preg_match_all('/<img.*?src=[\'"](.*?)[\'"].*?>/i',$content, $img_scr); // array[0] lista de tag <img  > , array[1] lista contenidos src
			$i=0;
			$imgs = $img_scr[0];
			foreach ($imgs as $key => $img) {
				$image_src = $img_scr[1][$i];
				if ($image_src) {
					// Get content image from src
					$image_data=$this->get_content_from_src($image_src);

					// Image File
					$ext = pathinfo($image_src, PATHINFO_EXTENSION);
					$file_name = basename($image_src,".".$ext).".png";

					$attach_id=$this->guarda_imagen_wp($image_data,$file_name,$title,null);
					if (!empty($attach_id)) {
						$image_src_wp=wp_get_attachment_url($attach_id);
						$content=str_replace($image_src,$image_src_wp,$content);
					}
					$i++;
				}
			}
			return $content;

		}

		// return fist image from the source code
		public function extract_first_image($content) {
			$image_data_src=null;
			preg_match('/<img[^>]+\>/i',$content, $img);
			if (isset($img[0]) && $img[0] )  {
				preg_match( '@src="([^"]+)"@' , $img[0], $match );  // Search src to get base64
				$src = array_pop($match);
				if ($src) {
					$image_data_src=$src;
				}
			}
			return $image_data_src;
		}

		// return content from url
		public function get_content_from_src($src) {
			if (strtolower(substr($src, 0, 5)=="data:")) { // base64 image
				list($type, $src) = explode(';', $src);
				list(, $src)      = explode(',', $src);
				$image_data = base64_decode($src);
			} else { // URL image
				if( ini_get('allow_url_fopen') ) {
					// allow_url_fopen is enabled. file_get_contents should work well
					$image_data = file_get_contents($src);

				} else {
					// allow_url_fopen is disabled. file_get_contents would not work
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL,$src);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
					$image_data = curl_exec($ch);
					curl_close ($ch);

				}
			}
			return $image_data;

		}

		// remove first image from the source code
		public function remove_first_image($content) {

			$html=preg_replace('/<img[^>]+\>/i','',$content,1); /*Nuevo codigo sin la primera imagen */
			return $html;
		}

		// save image on wordpress and attach it to the post
		public function guarda_imagen_wp($image_data,$filename,$title,$post_id) {

			$upload_dir = wp_upload_dir();
			if (empty($upload_dir)) { // NOT FOLDER SETUP

				return false;
			}

		  	$file = (wp_mkdir_p($upload_dir['path']) ? $upload_dir['path'] : $upload_dir['basedir']) . '/' . $filename;

			$result = file_put_contents($file, $image_data);

			if (empty($result)) { // NOT FOLDER PERMISSION IN

				return false;
			}

			// Check image file type
			$wp_filetype = wp_check_filetype($filename, null);

			// Set attachment data
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => $title,
				'post_content' => '',
				'post_status' => 'inherit',
			);

			// Create the attachment
			$attach_id = wp_insert_attachment($attachment, $file, $post_id);
			if ($attach_id == 0) {
				//"NOT INSERT ATTACHMENT
				return false;
			}

			// Include image.php
			require_once(ABSPATH . 'wp-admin/includes/image.php');

			// Define attachment metadata
			$attach_data = wp_generate_attachment_metadata($attach_id, $file);

			// Assign metadata to attachment
			wp_update_attachment_metadata($attach_id, $attach_data);

			// Change ALT Text
			update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_file_name($title));

			return $attach_id;

		}


		//Allow HTNL non modification
		public function wpse_kses_allowed_html() {

			if( 'post' !== $context ) {
					return $allowed;
			}

			$allowed['img']['src'] = true;

			return $allowed;
		}


		//Format the data
		public function prepare($data) {

			$output = array();

			$general = (is_array($data) && array_key_exists('general', $data) ? $data['general'] : false);
			$data_info = (is_array($data) && array_key_exists('data_info', $data) ? $data['data_info'] : false);

			if(false != $general) {

				$code = (is_array($general) && array_key_exists('code', $general) ? $general['code'] : false);
				if ($code == 'NEW_POST') {

					if ($data_info && is_array($data_info)) {

						$output['title'] = (array_key_exists('input_title', $data_info) ? $data_info['input_title'] : false);
						$get_content = (array_key_exists('input_text_content', $data_info) ? $data_info['input_text_content'] : false);
						$output['content'] = $this->format_content($get_content);
						$output['tags'] = (array_key_exists('input_tags', $data_info) ? $data_info['input_tags']['tag1'] : false); /* Post: tags + yaost: _yoast_wpseo_focuskw v1.3 */
						$categories = (array_key_exists('input_category', $data_info) ? array_values($data_info['input_category']) : false);
						$output['category'] = $this->insert_categories($categories);
						$output['connector_dato_id'] = (array_key_exists('connector_dato_id', $data_info) ? $data_info['connector_dato_id'] : false);
						$output['post_type'] = (array_key_exists('input_post_type', $data_info) ? $data_info['input_post_type'] : "post"); /* v2.1 */

						$status = (array_key_exists('input_mode', $data_info) ? $data_info['input_mode'] : false);
						switch ($status) {
							case 'DRAFT':
								$output['status'] = 'draft';
								break;

							case 'PUBLISHED':
								$output['status'] = 'publish';
								break;
						}

						$output['yoast_keyphrase'] = (array_key_exists('input_phrase', $data_info) ? $data_info['input_phrase'] : false); /* yaost: _yoast_wpseo_title */
						$output['yoast_meta_desc'] = (array_key_exists('input_meta_desc', $data_info) ? $data_info['input_meta_desc'] : false); /* _yoast_wpseo_metadesc v1.3  */
						$output['post_name_slug'] = (array_key_exists('input_slug', $data_info) ? $data_info['input_slug'] : false); /* Post: 'post_name' v1.3  */

					}
				}
			}

			return $output;
		}


		//Format content for images
		//Currently method not used
		public function format_content($content) {

			$output = '';
			if ($content && $content != '') {

				preg_match_all('/<img src="(.*?)"/', $content, $matches);
				$images = (is_array($matches) && array_key_exists(1, $matches) ? $matches[1] : array());
				foreach ($images as $image) {
					if(!filter_var($image, FILTER_VALIDATE_URL)) {
						$image_data = substr($image, 0, 5);
						if ($image_data != 'data:') {
							$new_image_data = 'data:' . $image;
							$content = str_replace($image, $new_image_data, $content);
						}
					}
				}

				$output = $content;
			}

			return $output;
		}


		//Author Format
		public function insert_author($author) {

			//$get =
			return $author;
		}


		//Category Format
		public function insert_categories($categories) {

			$output = array();

			if (!function_exists('wp_insert_category')) {
				if (file_exists (ABSPATH.'/wp-admin/includes/taxonomy.php')) {
				  require_once (ABSPATH.'/wp-admin/includes/taxonomy.php');
				}
			}

			foreach ($categories as $category) {

				$get = get_term_by('name', $category, 'category');
				if (false != $get) {

					$output[] = $get->term_id;
				} else {

					$cat_ID = wp_insert_category( array(
						'cat_name'        => $category,
						'taxonomy'        => 'category'
					));
					if (!is_wp_error($cat_ID)) {

						$output[] = $cat_ID;
					}
				}

			}

			return $output;
		}


		//Call the API
		public function call() {

			$content = false;
			$info = $this->getWpInfo();

			$endpoint = 'https://panel.contenidosclick.es/contentconnector/contenido/' . $this->key;

			$args = array(
				'timeout'     => 120,
				'httpversion' => '1.1',
				'sslverify' => FALSE,
				'body' => $info
			);

			$response = wp_remote_get( $endpoint, $args );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {

				$body    = $response['body'];
				$content = json_decode($body, 1);
			}

			return $content;
		}

		// Returns an arrya with url_site,wp_version,content_plugin_version,categories to send as post URL
		public function getWpInfo() {
			global $wp_version;

			$categories = get_categories();
			$categoriesComaSepa="";
			foreach($categories as $category) {
				$categoriesComaSepa.=($categoriesComaSepa?",":"").$category->name;
			}

			$info = array(
				'url_site' => site_url(),
				'wp_version' => $wp_version,
				'content_plugin_version' => CONTENT_CONNECTOR_VERSION,
				'wp_categories' => $categoriesComaSepa
		   );

		   return $info;
		}

		//Save post information
		public function call_cc_save_post($post_id,$connector_dato_id) {

			$content = false;
			$post_url=site_url().'/?p='.$post_id;
			$post_name=get_post_field('post_name', $post_id);

			$info = array(
				'wp_post_url' => $post_url,
				'wp_name' => $post_name,
				'connector_dato_id' => $connector_dato_id
		   );

			$endpoint = 'https://panel.contenidosclick.es/contentconnector/contenido_guardado/' . $this->key;

			$args = array(
				'timeout'     => 120,
				'httpversion' => '1.1',
				'sslverify' => FALSE,
				'body' => $info
			);

			$response = wp_remote_get( $endpoint, $args );
			if ( is_array( $response ) && ! is_wp_error( $response ) ) {

				$body    = $response['body'];
				$content = json_decode($body, 1);
			}

			return $content;
		}
  }
}
