<?
	/*
		Functions to render and process a list of records within a table.		
	*/
	
	//------------------------------------------------------------------------------------------
	function get_pagination($count, &$num_pages, &$cur_page) {
	//------------------------------------------------------------------------------------------
		global $APP;
		
		$cur_page = 1;
		$num_pages = ceil($count / $APP['page_size']);
		if($num_pages < 2)
			return '';
		
		if(isset($_GET['page']) && is_positive_int($_GET['page']) && in_range($_GET['page'], 1, $num_pages))
			$cur_page = intval($_GET['page']);
		
		$prev_disabled = ($cur_page <= 1 ? 'disabled' : '');
		$next_disabled = ($cur_page >= $num_pages ? 'disabled' : '');
		
		$prev_href = build_get_params(array('page' => $cur_page - 1));
		$next_href = build_get_params(array('page' => $cur_page + 1));
		
		$div = "<p><div class='btn-group'>\n";				
		$div .= "<a href='$prev_href' class='btn btn-sm btn-default $prev_disabled'><span class='glyphicon glyphicon-triangle-left'></span></a>\n";
		$div .= "<a href='$next_href' class='btn btn-sm btn-default $next_disabled'><span class='glyphicon glyphicon-triangle-right'></span></a>\n";
		$div .= '</div><span class="page-jumper">Jump to page: ';
		
		$before_skip = ($cur_page >= 3 + $APP['pages_prevnext']);
		$after_skip = ($num_pages - $cur_page >= 3 + $APP['pages_prevnext']);
		
		$lower_bound = max($cur_page - $APP['pages_prevnext'], 1);
		$upper_bound = min($cur_page + $APP['pages_prevnext'], $num_pages);
		$before_skipped = false;
		$after_skipped = false;
		
		for($p = 1; $p <= $num_pages; $p++) {
			$style = ($p == $cur_page ? 'btn-primary disabled' : 'btn-default');
			
			if($p == 1 || $p == $num_pages || ($p >= $lower_bound && $p <= $upper_bound))
				$div .= "<a href='".build_get_params(array('page' => $p))."' class='btn btn-sm $style'>$p</a>";
			else if($p < $lower_bound && !$before_skipped) {
				$before_skipped = true;
				$div .= " • • • ";
			}
			else if($p > $upper_bound && !$after_skipped) {
				$after_skipped = true;
				$div .= " • • • ";
			}
		}
		
		$div .= "\n";
		$div .= "</span></p>\n";
		return $div;
	}
	
	//------------------------------------------------------------------------------------------
	function initialize_search_popover() {
	//------------------------------------------------------------------------------------------
		echo "<script>var search_popover_template = \"<form class='form-inline' action='".build_get_params()."' method='get'><div class='form-group'>" .
			"<select id='searchoption' class='form-control input-sm space-right' name='".SEARCH_PARAM_OPTION."'>".
			"<option value='".SEARCH_ANY."'>Field contains</option>".
			"<option value='".SEARCH_WORD."'>Field contains word</option>".			
			"<option value='".SEARCH_EXACT."'>Field is exactly</option>".
			"<option value='".SEARCH_START."'>Field starts with</option>".
			"<option value='".SEARCH_END."'>Field ends with</option>".
			"</select>".
			"<input id='searchtext' type='text' class='form-control input-sm space-right' name='".SEARCH_PARAM_QUERY."' placeholder='Enter search text' autofocus>".
			"<input type='hidden' name='table' value='{$_GET['table']}'>".
			"<input type='hidden' name='mode' value='list'>".
			"<input type='hidden' name='".SEARCH_PARAM_FIELD."' value='%FIELDNAME%'>". // the %FIELDNAME% is replaced during runtime in dbweb.js
			//"<input class='btn btn-sm btn-primary' type='submit' value='Search'/></div></form>\";</script>\n";
			"<button class='btn btn-sm btn-primary' type='submit'><span class='glyphicon glyphicon-search'></span></button></div></form>\";</script>\n";
		
		echo "<div id='search-popover'></div>\n"; //needed for CSS styling of the popover
	}
	
	//------------------------------------------------------------------------------------------
	function render_search_sort($field_name) {
	//------------------------------------------------------------------------------------------		
		$sort_field = isset($_GET['sort']) ? $_GET['sort'] : '';
		$sort_dir = isset($_GET['dir']) ? $_GET['dir'] : 'asc';			
		
		$t = "<div class='sort-search'>";
		
		if($field_name == $sort_field && $sort_dir == 'asc')
			$t .= "<span class='glyphicon glyphicon-arrow-up'></span>";
		else
			$t .= "<a href='". build_get_params(array('sort'=>$field_name,'dir'=>'asc')) ."' title='Sort Ascending'><span class='glyphicon glyphicon-arrow-up'></span></a>";
		
		if($field_name == $sort_field && $sort_dir == 'desc')
			$t .= "<span class='glyphicon glyphicon-arrow-down'></span>";
		else
			$t .= "<a href='". build_get_params(array('sort'=>$field_name,'dir'=>'desc')) ."' title='Sort Descending'><span class='glyphicon glyphicon-arrow-down'></span></a>";
		
		$search_val = (isset($_GET[SEARCH_PARAM_QUERY]) && isset($_GET[SEARCH_PARAM_FIELD]) && $_GET[SEARCH_PARAM_FIELD] == $field_name ? unquote($_GET[SEARCH_PARAM_QUERY]) : '');
		
		$search_option = isset($_GET[SEARCH_PARAM_OPTION]) ? $_GET[SEARCH_PARAM_OPTION] : SEARCH_ANY;
		
		$t .= " <a href='javascript:void(0)' title='Search' data-value='{$search_val}' data-field='{$field_name}' data-option='{$search_option}' data-purpose='search' data-toggle='popover' data-container='body' data-placement='top'><span class='glyphicon glyphicon-search'></span></a>";
		
		$t .= "</div>";
		
		return $t;
	}
	
	//------------------------------------------------------------------------------------------
	function render_list() {
	//------------------------------------------------------------------------------------------
		global $TABLES;
		global $APP;
		
		$table_name = $_GET['table'];
		if(!isset($TABLES[$table_name]))
			return proc_error('Invalid table name or table not configured.');
		
		$table = $TABLES[$table_name];
		
		if(!is_allowed($table, $_GET['mode']))
			return proc_error('You are not allowed to perform this action.');
		
		$fields = $table['fields'];
		
		echo "<h1>{$table['display_name']}</h1>\n";
		echo "<p>{$table['description']}</p>\n";
		
		$search = build_search_term($table, 'd');
		
		$db = db_connect();	
		if($db === false)
			return proc_error('Cannot connect to DB.');
		
		$sql = 'SELECT COUNT(*) FROM ' . db_esc($table_name) . ' d' . ($search !== null ? " WHERE {$search['sql']}" : '');
		#debug_log($sql);
			
		$res = $db->prepare($sql);
		$res->execute($search !== null? $search['params'] : array());
		
		$num_records = intval($res->fetchColumn());
		
		$total_in_table = -1;
		if($search !== null) {			
			$res = $db->prepare('SELECT COUNT(*) FROM ' . db_esc($table_name));
			$res->execute();
			$total_in_table = intval($res->fetchColumn());
		}
		
		$pag = get_pagination($num_records, $num_pages, $cur_page);				
		$offset = ($cur_page - 1) * $APP['page_size'];
		$start_record = $offset + 1;
		$end_record = min($start_record + $APP['page_size'] - 1, $num_records);
		
		initialize_search_popover();
		
		echo "<div class='col-sm-12'>\n";
		
		if(is_allowed($table, MODE_NEW))
			echo "<p><a href='?table={$table_name}&amp;mode=".MODE_NEW."' class='btn btn-default'><span class='glyphicon glyphicon-plus'></span> New {$table['item_name']}</a></p>\n";
		
		if($search !== null) {
			$search_type = 'contains';
			if(isset($_GET[SEARCH_PARAM_OPTION])) switch($_GET[SEARCH_PARAM_OPTION]) {
				case SEARCH_END: $search_type = 'ends with'; break;
				case SEARCH_START: $search_type = 'starts with'; break;
				case SEARCH_EXACT: $search_type = 'matches'; break;
				case SEARCH_WORD: $search_type = 'contains word'; break;
			}
			echo "<p class='text-info'>Searching all records where <b>".html($fields[$_GET[SEARCH_PARAM_FIELD]]['label'])."</b> {$search_type} <span class='bg-success'><strong>".html($_GET[SEARCH_PARAM_QUERY])."</strong></span> ".
			"<a class='btn btn-default space-left' href='?".http_build_query(array('table'=>$table_name, 'mode'=>MODE_LIST))."'><span class='glyphicon glyphicon-remove-circle'></span> Clear search</a></p>\n";
		}
		
		if($num_records == 0) {
			echo "<p class='text-info'>No records found.</p>";
		}
		else {
			$what = ($search !== null ? 'search results' : 'records');
			$total_ind = ($total_in_table > 0 ? "  (total in table: <b>$total_in_table</b>)" : '');
			
			echo "<p class='text-info'>Displaying {$what} <b>{$start_record}</b>&ndash;<b>{$end_record}</b> of <b>{$num_records}</b>{$total_ind}</p>";
			$query = build_query($table_name, $table, $offset, MODE_LIST, null, $params_arr);
			
			#debug_log($query);
			#debug_log($params_arr);
			
			$res = $db->prepare($query);
			if($res === FALSE)
				return proc_error('List query preparation went wrong.', $db);
			
			if($res->execute($params_arr) === FALSE)
				return proc_error('List query execution went wrong.', $db);
		
			echo $pag;
			
			$table_body = "<tbody>\n";
			$col_longest_content = array();
						
			while($record = $res->fetch(PDO::FETCH_ASSOC)) {				
				#debug_log($record);
				$id_str = '';				
				
				foreach($table['primary_key']['columns'] as $pk) {					
					// field with postfixed name contains the raw value (not lookup display value) of referenced primary keys
					$id_str .= "&amp;{$pk}=" . urlencode($record[db_postfix_fieldname($pk, FK_FIELD_POSTFIX, false)]);
				}
								
				$table_body .= "<tr><td class='fit'>\n";
				$action_icons = array();
				
				if(isset($table['render_links']) && is_allowed($table, MODE_LINK)) {
					foreach($table['render_links'] as $render_link) {
						$action_icons[] = "<a href='" .
							sprintf($render_link['href_format'], $record[$render_link['field']]) .
							"'><span title='{$render_link['title']}' class='glyphicon glyphicon-{$render_link['icon']}'></span></a>";
					}
				}
				
				if(is_allowed($table, MODE_VIEW)) {
					$action_icons[] = "<a href='?table={$table_name}&amp;mode=".MODE_VIEW."{$id_str}'><span title='View this record' class='glyphicon glyphicon-zoom-in'></span></a>";					
				}
				
				if(is_allowed($table, MODE_EDIT))
					$action_icons[] = "<a href='?table={$table_name}&amp;mode=".MODE_EDIT."{$id_str}'><span title='Edit this record' class='glyphicon glyphicon-edit'></span></a>";
					
				if(is_allowed($table, MODE_DELETE))
					$action_icons[] = "<a role='button' data-href='?table={$table_name}{$id_str}&amp;mode=".MODE_DELETE."' data-toggle='modal' data-target='#confirm-delete'><span title='Delete this record' class='glyphicon glyphicon-trash'></span></a>";
				
				if(isset($table['custom_actions'])) {
					foreach($table['custom_actions'] as $custom_action) {
						if($custom_action['mode'] == $_GET['mode']) {
							// call custom action handler
							$action_icons[] = $custom_action['handler']($table_name, $table, $record, $custom_action);
						}
					}
				}
				
				$table_body .= implode('&nbsp;&nbsp;', $action_icons) . "&nbsp;&nbsp;&nbsp</td>\n";
				
				$col_no = 0;
				foreach($record as $col => $val) {
					if(!isset($fields[$col]) || is_field_hidden_in_list($fields[$col]))
						continue;
					
					$css = '';
					if(isset($_GET[SEARCH_PARAM_FIELD]) && $_GET[SEARCH_PARAM_FIELD] === $col)
						$css = 'class="bg-success"';
					
					$val = prepare_field_display_val($table, $record, $fields[$col], $col, $val);					
					$table_body .= "<td $css>{$val}</td>\n";
					
					// determine max cell len
					$textlen = mb_strlen(strip_tags($val));
					if(!isset($col_longest_content[$col_no]) || $textlen > $col_longest_content[$col_no])
						$col_longest_content[$col_no] = $textlen;					
					
					$col_no++;
				}
				
				$table_body .= "</tr>\n";
			}			
			$table_body .= "</tbody></table>\n";
			
			$table_head = "<table class='table table-hover table-striped table-condensed'>\n";
			$table_head .= "<thead><tr class='info'><th class='fit'></th>\n";
			
			$col_no = 0;
			for($i=0; $i<$res->columnCount(); $i++) {
				$meta = $res->getColumnMeta($i);
				$col = $meta['name'];
				if(!isset($fields[$col]) || is_field_hidden_in_list($fields[$col]))
					continue;
				
				$minwidth = '';
				if(isset($col_longest_content[$col_no])) {
					$mw = min(get_mincolwidth_max(), $col_longest_content[$col_no] * get_mincolwidth_pxperchar());
					$minwidth = "style='min-width:{$mw}px'";
				}
				$col_no++;
				
				$table_head .= "<th $minwidth>{$fields[$col]['label']}<br />" . render_search_sort($col /*, $fields[$col]*/) . "</th>";
			}
			$table_head .= "</tr></thead>\n";
			
			echo "<div class='panel panel-default'><div class='table-responsive'>";
			echo $table_head;
			echo $table_body;
			echo "</div></div>";
		}
		
		echo $pag;
		echo "</div>";
		
		if(is_allowed($table, MODE_DELETE))
			enable_delete();
	}
?>