<?php
/**
 Plugin Name: Content Connector by ContenidosClick
 Plugin URI: www.contenidosclick.es/contentconnector
 Description: Conecta la plataforma de Contenidos Click con el blog del cliente, facilitando la publicación o puesta en borrador de los contenidos realizados. Nota: Solo funciona siendo cliente activo de Contenidos Click.
 Version: 2.2
 Author: ContenidosClick
 Author URI: www.contenidosclick.es
 Text Domain: content-connector
 Domain Path: /asset/ln
 License: GPLv3
 License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) exit;

//Plugin Version
defined('CONTENT_CONNECTOR_VERSION') or define('CONTENT_CONNECTOR_VERSION', '2.2');

//Define basic names
defined('CONTENT_CONNECTOR_DEBUG') or define('CONTENT_CONNECTOR_DEBUG', false);

defined('CONTENT_CONNECTOR_PATH') or define('CONTENT_CONNECTOR_PATH', plugin_dir_path(__FILE__));
defined('CONTENT_CONNECTOR_FILE') or define('CONTENT_CONNECTOR_FILE', plugin_basename(__FILE__));

defined('CONTENT_CONNECTOR_EXECUTE') or define('CONTENT_CONNECTOR_EXECUTE', plugin_dir_path(__FILE__).'src/');
defined('CONTENT_CONNECTOR_HELPER') or define('CONTENT_CONNECTOR_HELPER', plugin_dir_path(__FILE__).'helper/');
defined('CONTENT_CONNECTOR_TRANSLATE') or define('CONTENT_CONNECTOR_TRANSLATE', plugin_basename( plugin_dir_path(__FILE__).'asset/ln/'));

defined('CONTENT_CONNECTOR_JS') or define('CONTENT_CONNECTOR_JS', plugins_url('/asset/js/', __FILE__));
defined('CONTENT_CONNECTOR_CSS') or define('CONTENT_CONNECTOR_CSS', plugins_url('/asset/css/', __FILE__));
defined('CONTENT_CONNECTOR_IMAGE') or define('CONTENT_CONNECTOR_IMAGE', plugins_url('/asset/img/', __FILE__));

//The Plugin
require_once('autoload.php');
if ( class_exists( 'CONTENT_CONNECTOR_BUILD' ) ) new CONTENT_CONNECTOR_BUILD(); ?>
