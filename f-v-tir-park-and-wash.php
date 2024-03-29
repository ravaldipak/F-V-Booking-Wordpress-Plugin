<?php 
/**
 * Plugin Name: F&V TIR PARK & WASH
 * Plugin URI: https://insixus.com/
 * Description: F&V TIR PARK & WASH
 * Version: 1.0.0
 * Author: Insixus
 * Text Domain: F&V TIR PARK & WASH
 * Domain Path: /languages
 * Text Domain: FVTPW
 * Author URI: https://insixus.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 **/

 defined( 'ABSPATH' ) or die( 'Something went wrong' );

 if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/vendor/autoload.php';
 } else {
    die('Something went wrong');
 }

if (!defined('FVTPW_DIR_PATH')) {
    define('FVTPW_DIR_PATH', plugin_dir_path(__FILE__));
}
if (!defined('FVTPW_PLUGIN_BASENAME')){
    define('FVTPW_PLUGIN_BASENAME', plugin_basename(__FILE__));
}
if (!defined('FVTPW_DIR_URI')) {
    define('FVTPW_DIR_URI', plugin_dir_url(__FILE__));
}


use FVTPWplugin\FVTPWActivate;
use FVTPWplugin\FVTPWDeactivate;
use FVTPWplugin\FVTPWAdmin;
use FVTPWplugin\FVTPWBase;

register_activation_hook(__FILE__,[(new FVTPWActivate()),'FVTPW_activate']);
register_deactivation_hook(__FILE__, [(new FVTPWDeactivate()), 'FVTPW_deactivate']);

(new FVTPWAdmin());
(new FVTPWBase()); 
?>