<?php

	class SP_Widget_SearchEngines {
	
		//need to aktivate
		static function add_options () { 
		
			$default_options = array (
				searchengines_title 			=> 'Top Search Engnies',
				searchengines_max 			=> 5,
				searchengines_pattern 		=> '<li>%searchengine% (%hits%)</li>',
				searchengines_terms_title 	=> 'Search Terms',
				searchengines_terms_max 	=> 15,
				searchengines_terms_filter	=> array('http://',':','-'),
				searchengines_term_pattern => '<a href="%host%/?s=%term%">%term%</a> '
			);
			
			add_option('sp_widgets_searchengines', $default_options);
		}
		
		static function delete_options () {
			delete_option('sp_widgets_searchengines', $default_options);
		}
		
	
		function SP_Widget_SearchEngines() {

			//check if widgets options are setted
			if (!get_option('sp_widgets_searchengines')) 
				$this->add_options();
		
			register_sidebar_widget ('SPW Search Engnies',array($this,'display'));
			register_widget_control ('SPW Search Engnies',array($this,'control'), 250, 350);				
		}
		

		function display ($args) {
			
			extract ($args);
			extract (get_option('sp_widgets_searchengines'));
						
			if ($searchengines_max)
				$engnies_data = $this->get_engnies_data($searchengines_max);
				
			if ($searchengines_terms_max)
				$terms_data = $this->get_terms_data ($searchengines_terms_max, $searchengines_terms_filter);

				
				
			print ($before_widget);
			
			if (isset($engnies_data)) {
			
				if ($searchengines_title) print ($before_title . $searchengines_title . $after_title);
				$this->print_lines ($engnies_data, $searchengines_pattern);
				
			}
			
			if (isset($terms_data)) {
				
				if ($searchengines_terms_title) 
					print ($before_title . $searchengines_terms_title . $after_title);
				
				isset($engnies_data) && $searchengines_terms_title=='' ? //avoid space between the lists
					$ul_params = 'style="margin-top:0;"' : '';		
				
				$this->print_lines ($terms_data, $searchengines_term_pattern, $ul_params, true);
			}
			
			print ($after_widget);
			
		}
		
		
		//generate html from data array
		function print_lines ($data, $pattern, $ul_params='', $display_in_li=false) { //print_r ($data); echo $pattern;
		
			$num=0;
			print ("<ul $ul_params>");
			
			print ($display_in_li ? '<li>':'');
			
			foreach ($data as $row) {
				
				$num++;
				$entry = $pattern;
				
				foreach ($row as $field => $value) {
					$entry = str_replace ('%'.$field.'%',$value,$entry);
				}
				
				$entry = str_replace ('%host%', get_bloginfo('url'), $entry);
				
				//erase coma from last entry
				if ($num == count($data))
					$entry = rtrim ($entry,', ');
					
				print ($entry);				
			}
			print ($display_in_li ? '</li>':'');
			print ('</ul>');
		}
		
		
		//get hot search engnies
		function get_engnies_data ($max=5) {
			
			global $wpdb;
			$engines = array();
			
			$q = "SELECT searchengine, count(*) as hits
					FROM wp_statpress
					WHERE searchengine != '' 
					GROUP BY searchengine
					ORDER BY hits DESC 
					LIMIT 0, $max";
			$raw_engines = $wpdb->get_results($q, ARRAY_A);
			
			foreach ($raw_engines as $engine) {
				array_push ($engines, $engine);
			}
			
			//return array_splice ($engines, 0, $max);
			return $engines;
		}
		
		
		//get top referrer data as an array
		function get_terms_data ($max=15, $filter) {
			
			global $wpdb;
			$terms = array();
			$words = array();
			$hits = array();

			$q = "SELECT search FROM wp_statpress 
				WHERE search != ''
				ORDER BY id DESC";
			$raw_terms = $wpdb->get_results($q, ARRAY_A);
			
			//print_r($raw_terms);

			//search equal hosts and compress them
			foreach ($raw_terms as $row) {
			
				$search = explode (' ',$row['search']);
				
				//if not in the array -> add, else increase hits
				foreach ($search as $word) {
				
					$collect = true;
					foreach ($filter as $flt) {
						if (strlen($word)<4  ||  strpos ($word, $flt) !== false) {
							$collect = false;
							break;
						}
					}
					if ($collect==false) continue; //next
				
					if (in_array ($word,$words)===false) {
						array_push($words, $word);
						array_push($hits, 1);
					}
					else $hits[array_search($word, $words)]++;
				}
			}
			
			//construct and resort
			for ($i=0; $i<count($words); $i++) {
				array_push ($terms, array ( term => $words[$i], hits => $hits[$i] ));
			}
			$cmpfunc = create_function('$a,$b', 'return ($a["hits"] == $b["hits"]) ? 0 : ($a["hits"] < $b["hits"]) ? 1 : -1;');
			usort ($terms, $cmpfunc);			
			
			return array_splice ($terms, 0, $max);
		}
		

		
		
		
		//
		// Admin sets			
		
		function control () {
		
			if ($_POST['sp_widgets_searchengines_submit']) {

				$options['searchengines_title'] 		= trim(strip_tags(stripslashes($_POST['sp_widgets_searchengines_title'])));
				$options['searchengines_max'] 		= (int) strip_tags(stripslashes($_POST['sp_widgets_searchengines_max']));
				$options['searchengines_pattern'] 	= stripslashes($_POST['sp_widgets_searchengines_pattern']);
				
				$options['searchengines_terms_title'] 	= trim(stripslashes($_POST['sp_widgets_searchengines_terms_title']));
				$options['searchengines_terms_max'] 	= (int) strip_tags(stripslashes($_POST['sp_widgets_searchengines_terms_max']));
				$options['searchengines_term_pattern'] = stripslashes($_POST['sp_widgets_searchengines_term_pattern']);
				
				$options['searchengines_terms_filter'] = array();
				$words = explode(',',strip_tags(stripslashes($_POST['sp_widgets_searchengines_terms_filter'])));	
				foreach ($words as $word) 		
					if ($word=trim($word))
						array_push ($options['searchengines_terms_filter'], $word);
				
				update_option('sp_widgets_searchengines', $options);
			}
			else 
				$options = get_option('sp_widgets_searchengines');
			
			extract ($options);
			
			?>
			<table>
			<tr>
				<td colspan="2"><b>Top Search Engines</b></td>
			</tr><tr>			
				<td colspan="2">&nbsp;</td>				
			</tr><tr>
				<td><label for="sp_widgets_searchengines_title">Title:</label></td>
				<td><input type="text" id="sp_widgets_searchengines_title" name="sp_widgets_searchengines_title" value="<?php echo $searchengines_title ?>" /></td>
			</tr><tr>
				<td><label for="sp_widgets_searchengines_max">Limit:</label></td>
				<td><input type="text" id="sp_widgets_searchengines_max" name="sp_widgets_searchengines_max" style="width:75px;" value="<?php echo $searchengines_max ?>" /> (0 = none)</td>
			</tr><tr>
				<td style="vertical-align:top; padding-top:2px;">
					<label for="sp_widgets_searchengines_pattern">Pattern:</label>
					<div style="font-size:.7em;">Vars: %search<wbr>engine%, %hits%</div></td>
				<td>
					<textarea id="sp_widgets_searchengines_pattern" name="sp_widgets_searchengines_pattern" 
						style="font-size:.7em; line-height:1.4em; height:70px; width:150px; padding:0px;"><?php echo $searchengines_pattern ?></textarea>
					
				</td>
			</tr><tr>			
			<td colspan="2">&nbsp;</td>
			</tr>
			
			<tr>
				<td colspan="2"><b>Top Terms</b></td>
			</tr><tr>
				<td colspan="2">&nbsp;</td>				
			</tr><tr>			
				<td><label for="sp_widgets_searchengines_terms_title">Title:</label></td>
				<td><input type="text" id="sp_widgets_searchengines_terms_title" name="sp_widgets_searchengines_terms_title" value="<?php echo $searchengines_terms_title ?>" /></td>
			</tr><tr>
				<td><label for="sp_widgets_searchengines_terms_max">Limit:</label></td>
				<td><input type="text" id="sp_widgets_searchengines_terms_max" name="sp_widgets_searchengines_terms_max" style="width:75px;" value="<?php echo $searchengines_terms_max ?>" /></td>
			</tr><tr>
				<td style="vertical-align:top; padding-top:2px;">
					<label for="sp_widgets_searchengines_term_pattern">Pattern:</label>
					<div style="font-size:.7em;">Vars: %term%, %hits%, %host%</div>
				</td>
				<td>
					<textarea id="sp_widgets_searchengines_term_pattern" name="sp_widgets_searchengines_term_pattern" 
						style="font-size:.7em; line-height:1.4em; height:70px; width:150px; padding:0px;"><?php echo $searchengines_term_pattern ?></textarea>
				</td>
			</tr><tr>
			<td colspan="2">&nbsp;</td>
			
			</tr><tr>				
				<td><label for="sp_widgets_referrers_badwords">Term Filter:</label></td>
				<td><input type="text" id="sp_widgets_searchengines_terms_filter" name="sp_widgets_searchengines_terms_filter" value="<?php echo implode(', ', $searchengines_terms_filter); ?>" />
					<small>Separate by coma</small>
				</td>
			</tr>
			</table>
			<input type="hidden" id="sp_widgets_searchengines_submit" name="sp_widgets_searchengines_submit" value="1" />
			<br/>
			<?php
			
		}	
	}
?>