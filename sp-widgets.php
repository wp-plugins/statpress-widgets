<?php
/*
Plugin Name: SP Widgets
Plugin URI: http://blog.hooda.de/
Description: Stats-Widgets by using the data from the <a href="http://wordpress.org/extend/plugins/statpress-reloaded/" target="_blank">Statpress Plugin</a>
Version: 0.18
Author: Jan A. Manolov
Author URI: http://blog.hooda.de/
*/


	include "sp-widget-referrers.php";
	

	class SP_Widgets {
		
		static function init() {

		  if (!function_exists('register_sidebar_widget')
			|| !function_exists('register_widget_control'))
				return;
			
			$widget_referrers = new SP_Widget_Referrers();
		}
		
							
		static function aktivate_plugin() {
		
			$options = array (
				referrers_top_title 		=> 'Hot Referrers',
				referrers_top_max 		=> 5,
				referrers_top_counts 	=> 1,
				referrers_last_title 	=> 'Last Referrers',
				referrers_last_max 		=> 5,
				referrers_badwords 		=> array('boln.cn'),
				referrers_searchengines => 0
			);
			
			add_option('sp_widgets_options', $options);
		}
		
		static function deaktivate_plugin() {
			delete_option('sp_widgets_options');
		}		
			
	}
		
	add_action ('activate_'.plugin_basename(__FILE__), array('sp_widgets','aktivate_plugin'));
	add_action ('deactivate_'.plugin_basename(__FILE__), array('sp_widgets','deaktivate_plugin'));
	add_action ('plugins_loaded', array('sp_widgets','init'));
	
?>
