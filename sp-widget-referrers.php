<?php

	class SP_Widget_Referrers {
	
		//need to aktivate
		static function add_options () { 
		
			$default_options = array (
				referrers_top_title 		=> 'Hot Referrers',
				referrers_top_max 		=> 5,
				referrers_top_counts 	=> 1,
				referrers_last_title 	=> 'Last Referrers',
				referrers_last_max 		=> 5,
				referrers_badwords 		=> array('boln.cn'),
				referrers_searchengines => 0
			);
			
			add_option('sp_widgets_referrers', $default_options);
		}		
	
		static function delete_options () {
			delete_option('sp_widgets_referrers', $default_options);
		}
	
		function SP_Widget_Referrers() {
		
			register_sidebar_widget ('SPW Refererrers',array($this,'display'));
			register_widget_control ('SPW Refererrers',array($this,'control'), 200, 350);	
		}
		

		function display ($args) {
			
			extract ($args);
			extract (get_option('sp_widgets_referrers'));
			
			if ($referrers_top_max)
				$top_referrers = $this->get_top_referrers_data($referrers_top_max, $referrers_badwords, $referrers_searchengines);
			if ($referrers_last_max)
				$last_referrers = $this->get_last_referrers_data($referrers_last_max, $referrers_badwords, $referrers_searchengines);

				
			print ($before_widget); 
			
			if (isset($last_referrers)) {
				if ($referrers_last_title) print ($before_title . $referrers_last_title . $after_title);
				$this->print_referrers ($last_referrers, false);
			}
			
			if (isset($top_referrers)) {
				
				if ($referrers_top_title) print ($before_title . $referrers_top_title . $after_title);
				
				isset($last_referrers) && $referrers_top_title=='' ? //avoid space between the lists
					$ul_params = 'style="margin-top:0;"' : '';				
				$this->print_referrers ($top_referrers, $referrers_top_counts, $ul_params);
			}
			
			print ($after_widget);
		}
		
		
		//generate html from data array >> name, host, totale
		function print_referrers ($data, $counts=true, $params='') {
		
			print ("<ul $params>");
			foreach ($data as $ref) {
				print ('<li>');
				print ("<a href='$ref[host]'>$ref[name]</a>");
				print ((isset($ref['totale']) && $counts) ? " ($ref[totale])</li>" : '</li>');
			}
			print ('</ul>');
		}
		
		
		//get last referrer data as an array
		function get_last_referrers_data ($max=5, $evadehosts=null, $include_searchengines=false) {
			
			global $wpdb;
			$referrers = array();
			
			$qwhere = !$include_searchengines ? " AND searchengine=''" : '';
			if (is_array($evadehosts)) foreach ($evadehosts as $blackhost) 
				$qwhere .= " AND referrer not like '%$blackhost%' ";
			
			$q = "SELECT *
					FROM wp_statpress
					WHERE spider='' AND referrer != '' $qwhere
					ORDER BY id DESC 
					LIMIT 0, 100";
			$row_referrers = $wpdb->get_results($q, OBJECT);
			
			//search equal hosts and compress them
			foreach ($row_referrers as $ref) {
				
				$arr_url = parse_url($ref->referrer);
				$host = $ref->referrer;

				$name = stripos($arr_url['host'],'www.') === 0 ? 
					substr_replace($arr_url['host'],'',0,4) : $arr_url['host'];
				
				for ($i=0; $i<count($referrers); $i++) {
					if ($referrers[$i]['name'] == $name) break;				
				}
				
				if ($i==count($referrers))
					array_push ($referrers, array (
						host 		=> $host, 
						name 		=> $name, 
						totale 	=> $ref->totale 
					));
			}
			
			return array_splice ($referrers, 0, $max);
		}
		
		
		//get top referrer data as an array
		function get_top_referrers_data ($max=5, $evadehosts=null, $include_searchengines=false) {
			
			global $wpdb;
			$referrers = array();
			
			$qwhere = !$include_searchengines ? " AND searchengine=''" : '';
			if (is_array($evadehosts)) foreach ($evadehosts as $blackhost) 
				$qwhere .= " AND referrer not like '%$blackhost%' ";
			
			$q = "SELECT referrer,count(*) as totale
					FROM wp_statpress
					WHERE spider='' AND referrer != '' $qwhere
					GROUP BY referrer
					ORDER BY totale DESC
					LIMIT 0, 100";
			$row_referrers = $wpdb->get_results($q, OBJECT);
		
			//search equal hosts and compress them
			foreach ($row_referrers as $ref) {
			
				$arr_url = parse_url($ref->referrer);
				$host = $arr_url['scheme'] .'://'. $arr_url['host'];
				$name = stripos($arr_url['host'],'www.') === 0 ? 
					substr_replace($arr_url['host'],'',0,4) : $arr_url['host'];
				
				for ($i=0; $i<count($referrers); $i++) {
					if ($referrers[$i]['host'] == $host) {
						$referrers[$i]['totale'] += $ref->totale;
						break;
					}
				}
				
				if ($i==count($referrers))
					array_push ($referrers, array (
						host 		=> $host, 
						name 		=> $name, 
						totale 	=> $ref->totale 
					));
			}
			
			//resort
			$cmpfunc = create_function('$a,$b', 'return ($a["totale"] == $b["totale"]) ? 0 : ($a["totale"] < $b["totale"]) ? 1 : -1;');
			usort ($referrers, $cmpfunc);			
				
			return array_splice ($referrers, 0, $max);
		}
		

		
		
		
		//
		// Admin sets			
		
		function control () {
			
			//setup options
			if ($_POST['sp_widgets_referrers_submit']) {

				$options['referrers_top_title'] 		= trim(strip_tags(stripslashes($_POST['sp_widgets_referrers_top_title'])));
				$options['referrers_top_max'] 		= (int) strip_tags(stripslashes($_POST['sp_widgets_referrers_top_max']));
				$options['referrers_top_counts'] 	= $_POST['sp_widgets_referrers_top_counts']=='on' ? true : false;
				
				$options['referrers_last_title'] 	= trim(strip_tags(stripslashes($_POST['sp_widgets_referrers_last_title'])));
				$options['referrers_last_max'] 		= (int) strip_tags(stripslashes($_POST['sp_widgets_referrers_last_max']));
				
				$options['referrers_searchengines'] = $_POST['sp_widgets_referrers_searchengines']=='on' ? true : false;
				$options['referrers_badwords'] 		= array();
				$words 										= explode(',',strip_tags(stripslashes($_POST['sp_widgets_referrers_badwords'])));	
				foreach ($words as $word) 		
					if ($word=trim($word))
						array_push ($options['referrers_badwords'], $word);
				
				update_option('sp_widgets_referrers', $options);
			}
			else 
				$options = get_option('sp_widgets_referrers');
			
			?>
			<table>
			<tr>
				<td colspan="2"><b>Last Referrers</b></td>
			</tr><tr>
				<td><label for="sp_widgets_referrers_last_title">Title:</label></td>
				<td><input type="text" id="sp_widgets_referrers_last_title" name="sp_widgets_referrers_last_title" value="<?php echo $options['referrers_last_title'] ?>" /></td>
			</tr><tr>
				<td><label for="sp_widgets_referrers_last_max">Limit:</label></td>
				<td><input type="text" id="sp_widgets_referrers_last_max" name="sp_widgets_referrers_last_max" style="width:75px;" value="<?php echo $options['referrers_last_max'] ?>" /> (0 = none)</td>
			</tr><tr>
			<td colspan="2">&nbsp;</td>
			
			</tr><tr>	
				<td colspan="2"><b>Top Referrers</b></td>
			</tr><tr>
				<td><label for="sp_widgets_referrers_top_title">Title:</label></td>
				<td><input type="text" id="sp_widgets_referrers_top_title" name="sp_widgets_referrers_top_title" value="<?php echo $options['referrers_top_title'] ?>" /></td>
			</tr><tr>
				<td><label for="sp_widgets_referrers_top_max">Limit:</label></td>
				<td>
					<input type="text" id="sp_widgets_referrers_top_max" name="sp_widgets_referrers_top_max" style="width:75px;" value="<?php echo $options['referrers_top_max'] ?>" />
					<input type="checkbox" id="sp_widgets_referrers_top_counts" name="sp_widgets_referrers_top_counts" <?php if ($options['referrers_top_counts']) echo 'checked="checked"' ?> />
					<label for="sp_widgets_referrers_top_counts">counts</label>
				</td>
			</tr><tr>	
			<td colspan="2">&nbsp;</td>
			
			</tr><tr>	
				<td colspan="2">
					<input type="checkbox" id="sp_widgets_referrers_searchengines" name="sp_widgets_referrers_searchengines" <?php if ($options['referrers_searchengines']) echo 'checked="checked"' ?>  />
					<label for="sp_widgets_referrers_searchengines">List search engines too</label>
				</td>
			</tr><tr>	
			<td colspan="2">&nbsp;</td>
			
			</tr><tr>				
				<td><label for="sp_widgets_referrers_badwords">Filter hosts:</label></td>
				<td><input type="text" id="sp_widgets_referrers_badwords" name="sp_widgets_referrers_badwords" value="<?php echo implode(', ', $options['referrers_badwords']); ?>" />
					<small>Separate by coma</small>
				</td>
			</tr>
			</table>
			<input type="hidden" id="sp_widgets_referrers_submit" name="sp_widgets_referrers_submit" value="1" />
			<br/>
			<?php
			
		}	
	}
?>