<?php
/*
Plugin Name: SP Widgets
Plugin URI: http://blog.webhike.de/
Description: Stats-Widgets by using the data from the <a href="http://wordpress.org/extend/plugins/statpress-reloaded/" target="_blank">Statpress Plugin</a>
Version: 0.29
Author: Jan A. Manolov
Author URI: http://blog.webhike.de/
*/

	
	include "sp-widget-referrers.php";
	include "sp-widget-searchengines.php";
	

	class SP_Widgets {
		
		static function init() {
	
		  if (!function_exists('register_sidebar_widget')
			|| !function_exists('register_widget_control'))
				return;
				
			//for updates from 0.18 to 0.29
			if (get_option('sp_widgets_options')) 
				delete_option('sp_widgets_options');
			
			$widget_referrers = new SP_Widget_Referrers();
			$widget_searchengines = new SP_Widget_SearchEngines();
		}
		
							
		static function aktivate_plugin() {
			
			SP_Widget_Referrers::add_options();
			SP_Widget_SearchEngines::add_options();
		}
		
		static function deaktivate_plugin() {
		
			SP_Widget_Referrers::delete_options();
			SP_Widget_SearchEngines::delete_options();
		}		
			
	}
		
	add_action ('activate_'.plugin_basename(__FILE__), array('sp_widgets','aktivate_plugin'));
	add_action ('deactivate_'.plugin_basename(__FILE__), array('sp_widgets','deaktivate_plugin'));
	add_action ('plugins_loaded', array('sp_widgets','init'));
	
?>
