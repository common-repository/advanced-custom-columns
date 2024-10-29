<?php
/*
Plugin Name: Advanced Custom Columns
Plugin URI: http://wordpress.org/plugins/Advanced 
Description: Add custom admin columns to post types to provide sorting and filtering through these extra columns
Version: 1.1.5
Tested up to: WPMU 4.1
Author: François TOURDE
Author URI: http://www.a2il.fr
License: GNU General Public License 3.0 (GPL) http://www.gnu.org/licenses/gpl.html
*/

include(plugin_dir_path( __FILE__ )."/ACC_columns.php");
include(plugin_dir_path( __FILE__ )."/ACC_settings.php");

new ACC();

?>
