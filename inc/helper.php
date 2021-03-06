<?
	//------------------------------------------------------------------------------------------
	function process_redirect($flush_ob = false) {
	//------------------------------------------------------------------------------------------
		if(isset($_SESSION['redirect'])) {
			header("Location: {$_SESSION['redirect']}");
			unset($_SESSION['redirect']);
			
			if($flush_ob) {
				ob_flush();
				ob_end_clean();
			}
			
			return true;			
		}
		
		if($flush_ob) {
			ob_flush();
			ob_end_clean();
		}

		return false;
	}
	
	//------------------------------------------------------------------------------------------
	function starts_with($prefix, $text) {
	//------------------------------------------------------------------------------------------
		return mb_substr($text, 0, mb_strlen($prefix)) === $prefix;
	}
	
	//------------------------------------------------------------------------------------------
	function get_help_popup($title, $text) {
	//------------------------------------------------------------------------------------------
		return "<a href='javascript:void(0)' title='$title' data-purpose='help' data-toggle='popover' data-placement='bottom' data-content='" . 
			htmlentities($text, ENT_QUOTES) .
			"'><span class='glyphicon glyphicon-info-sign'></span></a>\n";		
	}
	
	//------------------------------------------------------------------------------------------
	function add_javascript($src) {
	//------------------------------------------------------------------------------------------
		global $META_INCLUDES;
		$META_INCLUDES[] = "<script type='text/javascript' src='$src'></script>";
	}
	
	//------------------------------------------------------------------------------------------
	function add_stylesheet($src) {
	//------------------------------------------------------------------------------------------
		global $META_INCLUDES;
		$META_INCLUDES[] = "<link rel='stylesheet' href='$src' />";
	}	
	
	//------------------------------------------------------------------------------------------
	function is_inline() {
	//------------------------------------------------------------------------------------------
		return isset($_GET['inline']);
	}
	
	//------------------------------------------------------------------------------------------
	function get_field_label(/*const*/ &$field, /*const*/ &$record) {
	//------------------------------------------------------------------------------------------
		if(!isset($field['conditional_form_label']))
			return $field['label'];
		
		$conrolling_field_value = $record[$field['conditional_form_label']['controlled_by']];
		if(!isset($field['conditional_form_label']['mapping'][$conrolling_field_value]))
			return $field['label'];
		
		return $field['conditional_form_label']['mapping'][$conrolling_field_value];
	}
	
	//------------------------------------------------------------------------------------------
	function is_popup() {
	//------------------------------------------------------------------------------------------
		return isset($_GET['popup']);
	}
	
	//------------------------------------------------------------------------------------------
	function is_password_change_allowed() { // default: true
	//------------------------------------------------------------------------------------------
		global $LOGIN;
		return !isset($LOGIN['allow_change_password']) || $LOGIN['allow_change_password'] === true;
	}
		
	//------------------------------------------------------------------------------------------
	function first($a) {
	//------------------------------------------------------------------------------------------
		return $a[0];
	}
	
	//------------------------------------------------------------------------------------------
	function get_mincolwidth_max() {
	//------------------------------------------------------------------------------------------
		global $APP;
		return isset($APP['list_mincolwidth_max']) ? $APP['list_mincolwidth_max'] : 300;
	}
	
	//------------------------------------------------------------------------------------------
	function get_mincolwidth_pxperchar() {
	//------------------------------------------------------------------------------------------
		global $APP;
		return isset($APP['list_mincolwidth_pxperchar']) ? $APP['list_mincolwidth_pxperchar'] : 6;
	}
	
	//------------------------------------------------------------------------------------------
	function __arr_str(&$a, $indent = 0) {
	//------------------------------------------------------------------------------------------
		$s = str_repeat(' ', $indent) . "[\n";
		if(is_array($a)) foreach($a as $k => $v) {
			
			$s .= str_repeat(' ', $indent + 2) . "'{$k}' => ";
			if(is_array($v))
				$s .= "\n" . __arr_str($v, $indent + 2) . ",\n";
			else if(is_string($v))
				$s .= "'$v',\n";
			else
				$s .= "$v,\n";			
		}	
		$s .= str_repeat(' ', $indent) . "]\n";
		return $s;
	}
	
	//------------------------------------------------------------------------------------------
	function arr_str(&$a) {
	//------------------------------------------------------------------------------------------
		$s = "<pre>\n";
		$s .= __arr_str($a, 0);
		$s .= "</pre>\n";
		return $s;
	}
	
	//------------------------------------------------------------------------------------------
	function build_search_term($table, $table_alias) {
	//------------------------------------------------------------------------------------------
		global $APP;
		global $TABLES;
		
		$term = array('sql' => '', 'params' => array());		
		$fields = $table['fields'];		
		$search_field = null;
		$search_query = null;
		$search_option = SEARCH_ANY;		
		
		foreach($_GET as $p => $v) {
			switch($p) {
				case SEARCH_PARAM_OPTION:
					$search_option = $v; break;
					
				case SEARCH_PARAM_QUERY:
					$search_query = strtolower($v); break;
					
				case SEARCH_PARAM_FIELD:
					$search_field = $v; break;
					
				case SEARCH_PARAM_LOOKUP:
					$search_lookup = $v; break;
				
				default:
					break;
			}
		}
		
		if($search_query === null || $search_query == '')
			return null;
		
		if($search_field === null || !isset($fields[$search_field]))
			return null;
		
		$term['params'][] = $search_query;
		$pre_term_op = '';
		$post_term_op = '';		
		
		switch($search_option) {
			case SEARCH_EXACT:				
				$op = '=';			
				break;
			
			case SEARCH_ANY:				
				$op = 'like';				
				$pre_term_op = '%';
				$post_term_op = '%';
				break;
				
			case SEARCH_START:				
				$op = 'like';
				$post_term_op = '%';
				break;
			
			case SEARCH_END:				
				$op = 'like';		
				$pre_term_op = '%';
				break;
				
			case SEARCH_WORD:				
				$op = '~*';
				$pre_term_op = '\m';
				$post_term_op = '\M';
				break;
			
			default:
				return null;
		}
		
		$string_trafo = '%s';
		if(isset($APP['search_string_transformation']) && $APP['search_string_transformation'] != '') {			
			$string_trafo = $APP['search_string_transformation'];
			if(strstr($string_trafo, '%s') === false)
				proc_error('$APP[search_string_transformation] does not include a placeholder for the value, i.e. %s');
		}
		
		// for sprintf() we need to escape any %
		$pre_term_op = str_replace('%', '%%', $pre_term_op);
		$post_term_op = str_replace('%', '%%', $post_term_op);
		if($pre_term_op != '') $pre_term_op = "'$pre_term_op' || ";
		if($post_term_op != '') $post_term_op = " || '$post_term_op'";		
		$query_trafo_without_ops = str_replace('%s', '?', $string_trafo);
		$query_trafo = '(' . $pre_term_op . $query_trafo_without_ops . $post_term_op . ')';
		#debug_log($query_trafo);
		
		if($APP['search_lookup_resolve'] && $fields[$search_field]['type'] == T_LOOKUP && $fields[$search_field]['lookup']['cardinality'] == CARDINALITY_SINGLE) {
			$lookup = $fields[$search_field]['lookup'];
		
			$field_trafo = str_replace('%s', '%s::text', $string_trafo);
			
			$term['sql'] = sprintf("$field_trafo %s $query_trafo or (select $field_trafo from %s other where other.%s = %s.%s) %s $query_trafo", 
				db_esc($search_field), $op,
				resolve_display_expression($lookup['display']),
				db_esc($lookup['table']), db_esc($lookup['field']), db_esc($table_alias), db_esc($search_field), $op);
				
			$term['params'][]= $term['params'][count($term['params'])-1];
		}
		else if($APP['search_lookup_resolve'] && $fields[$search_field]['type'] == T_LOOKUP && $fields[$search_field]['lookup']['cardinality'] == CARDINALITY_MULTIPLE) {
			$field = $fields[$search_field];
			
			$field_trafo = str_replace('%s', "array_to_string(array_agg(%s), ' ')", $string_trafo);
			
			$term['sql'] = sprintf("(select $field_trafo FROM %s other, %s link WHERE link.%s = %s.%s AND other.%s = link.%s) %s $query_trafo", 
				resolve_display_expression($field['lookup']['display'], 'other'),
				db_esc($field['lookup']['table']), db_esc($field['linkage']['table']),
				db_esc($field['linkage']['fk_self']), $table_alias, db_esc($table['primary_key']['columns'][0]),
				db_esc($field['lookup']['field']), db_esc($field['linkage']['fk_other']), $op);
						
			// for SEARCH_ANY & SEARCH_WORD queries (~ contains) we also want to look whether the provided query value matches any of the multiple foreign keys (not only the lookup values), expecting those key values to be integers (but also works with others)
			if($search_option === SEARCH_ANY || $search_option === SEARCH_WORD) {
				$field_trafo = sprintf("array_agg(%s)", $string_trafo);
				$or_term = sprintf("(select $field_trafo from %s link where link.%s = %s.%s) @> array[$query_trafo_without_ops]",
					db_esc($field['linkage']['fk_other']), db_esc($field['linkage']['table']),
					db_esc($field['linkage']['fk_self']), $table_alias, db_esc($table['primary_key']['columns'][0]));
					
				$term['sql'] = "({$term['sql']} OR {$or_term})";
				$term['params'][] = $search_query;
			}
		}
		else {
			if($fields[$search_field]['type'] == T_POSTGIS_GEOM)
				$field_trafo = str_replace('%s', 'ST_AsText(%s)', $string_trafo);
			else
				$field_trafo = str_replace('%s', '%s::text', $string_trafo);
			
			$term['sql'] = sprintf("$field_trafo %s $query_trafo", db_esc($search_field), $op);		
		}
		
		#debug_log('term: ', $term);
		return $term;
	}
	
	//------------------------------------------------------------------------------------------
	function debug_log(/* variable argument list */) {
	//------------------------------------------------------------------------------------------
		$msg = '';
		
		foreach(func_get_args() as $arg)
			$msg .= is_array($arg) ? arr_str($arg) : strval($arg);
			
		$_SESSION['msg'][] = "<div class='alert alert-info'>$msg</div>";
	}
	
	//------------------------------------------------------------------------------------------
	function is_allowed(&$table, $mode) {
	//------------------------------------------------------------------------------------------
		 return !isset($table['actions']) || in_array($mode, $table['actions']);
	}
	
	//------------------------------------------------------------------------------------------
	function get_primary_key_column($table_name) {
	//------------------------------------------------------------------------------------------
		 global $TABLES;
		 return $TABLES[$table_name]['primary_key']['columns'][0];
	}
	
	//------------------------------------------------------------------------------------------
	function db_connect() {
	//------------------------------------------------------------------------------------------
		global $DB;
		
		try {
			return new PDO(
				"pgsql:dbname={$DB['db']};host={$DB['host']};port={$DB['port']};options='--client_encoding=UTF8'", 
				$DB['user'], 
				$DB['pass']);
		} 
		catch(PDOException $e) {
			return FALSE;
		}
	}
	
	//------------------------------------------------------------------------------------------
	function get_default($def) {
	//------------------------------------------------------------------------------------------
		if(!isset($_SESSION['user_id']))
			return $def;
		
		$def = str_replace(REPLACE_DYNAMIC_SESSION_USER, $_SESSION['user_id'], $def);
		return $def;
	}
		
	//------------------------------------------------------------------------------------------
	function safehash(&$hash, $key, $default = null) {
	//------------------------------------------------------------------------------------------
		return isset($hash[$key]) ? $hash[$key] : $default;
	}
	
	//------------------------------------------------------------------------------------------
	function sort_tables_new($a, $b) {
	//------------------------------------------------------------------------------------------
		return strcmp($a['item_name'], $b['item_name']);
	}
	
	//------------------------------------------------------------------------------------------
	function sort_tables_list($a, $b) {
	//------------------------------------------------------------------------------------------
		return strcmp($a['display_name'], $b['display_name']);
	}
	
	//------------------------------------------------------------------------------------------
	function unquote($text) {
	//------------------------------------------------------------------------------------------
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
	
	//------------------------------------------------------------------------------------------
	function has_additional_editable_fields($linkage) {
	//------------------------------------------------------------------------------------------
		global $TABLES;
		
		if(isset($TABLES[$linkage['table']])) {
			// check whether the linkage table has additional fields
			$linkage_table = $TABLES[$linkage['table']];								
			foreach($linkage_table['fields'] as $lf_name => $lf_info) {
				if($lf_name != $linkage['fk_self']
					&& $lf_name != $linkage['fk_other']
					&& is_field_editable($lf_info))
				{
					return true;					
				}
			}
		}
		
		return false;
	}
	
	//------------------------------------------------------------------------------------------
	function bootstrap_css() {
	//------------------------------------------------------------------------------------------
		global $APP;
		return isset($APP['bootstrap_css']) ? $APP['bootstrap_css'] :
			'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css';
	}
	
	//------------------------------------------------------------------------------------------
	function page_icon() {
	//------------------------------------------------------------------------------------------
		global $APP;
		return isset($APP['page_icon']) ? $APP['page_icon'] : '';
	}
	
	//------------------------------------------------------------------------------------------
	function format_lookup_item_label($raw_label, $lookup_table, $lookup_id_field, $id_value) {
	//------------------------------------------------------------------------------------------
		global $TABLES;
		
		if(isset($TABLES[$lookup_table]) 
			&& isset($TABLES[$lookup_table]['fields'][$lookup_id_field]) 
			&& isset($TABLES[$lookup_table]['fields'][$lookup_id_field]['label'])) 
		{
			$lookup_id_field = $TABLES[$lookup_table]['fields'][$lookup_id_field]['label'];
		}		
		
		return sprintf('%s&nbsp;&nbsp;(%s = %s)', html($raw_label), $lookup_id_field, html($id_value));
	}
	
	//------------------------------------------------------------------------------------------
	// CAREFUL: this function can be called via MODE_FUNC
	//------------------------------------------------------------------------------------------
	function get_linked_item_html(
		/*string*/  $parent_form,
		/*array*/	&$table,
		/*string*/ 	$table_name, 
		/*string*/ 	$field_name, 
		/*bool*/	$can_edit, 
		/*string*/	$fk_other_value, 
		/*string*/	$fk_other_text, 
		/*string*/	$fk_self_value) 
	{
	//------------------------------------------------------------------------------------------
		global $TABLES;
		
		if(!isset($table['fields'][$field_name]))
			return proc_error('Invalid field');
		$field = $table['fields'][$field_name];
		
		$detail_data_span = '';
		if($can_edit) {
			$inline_url = sprintf('?inline=%s&amp;parent_form=%s&amp;lookup_field=%s&amp;table=%s&amp;mode=%s&amp;%s=%s&amp;%s=%s',
				$table_name, $parent_form, $field_name, $field['linkage']['table'], MODE_EDIT,
				$field['linkage']['fk_other'], $fk_other_value, 
				$field['linkage']['fk_self'], $fk_self_value); 
				
			$popup_title = html($TABLES[$field['linkage']['table']]['item_name'] . ' Details');
			
			$detail_data_span = "<a role='button' onclick='linkage_details_click(this)' class='space-left multiple-select-details-edit' data-id-other='{$fk_other_value}' data-details-title='{$popup_title}' data-details-url='{$inline_url}' id='{$field_name}_details_{$fk_other_value}' title='Edit the details of this association'><span class='glyphicon glyphicon-th-list'></span></a>";
		}
		
		$field_id = isset($TABLES[$field['lookup']['table']]) ? $TABLES[$field['lookup']['table']]['fields'][$field['lookup']['field']]['label'] : $field['lookup']['field'];
		
		return '<div class="multiple-select-item">' .
			'<a role="button" onclick="remove_linked_item(this)" data-label="'. unquote($fk_other_text) .'" data-field="'. unquote($field_name) .'" data-id="' . unquote($fk_other_value) .'"><span class="glyphicon glyphicon-trash"></span></a>' .
			$detail_data_span .
			'<span class="multiple-select-text">' . format_lookup_item_label($fk_other_text, $field['lookup']['table'], $field_id, $fk_other_value) . '</span></div>';
	}
	
	//------------------------------------------------------------------------------------------
	function html_linked_records(&$linked_records, $max_chars = 0) {
	//------------------------------------------------------------------------------------------		
		$html = '';
		$cunt = count($linked_records);
		$raw_len = 0;
		$clip_opened = false;
		
		for($i = 0; $i < $cunt; $i++) {
			$rec = $linked_records[$i];
			$raw_len += mb_strlen(strval($rec['raw_val'])) + mb_strlen(MULTIPLE_RECORDS_SEPARATOR);
			
			if(!$clip_opened // clipping hasn't started yet				
				&& $max_chars > 0 // there must be a restriction
				&& $i > 0 // at least one must have been shown already
				&& $raw_len > $max_chars // the length exceeds the configured maximum				
				&& $i < $cunt - 1 // we're not at the last record
			) {
				$rest = $cunt - $i;
				$html .= "<a role='button' title='Text clipped due to length. Click to show clipped text' class='clipped_text'>[$rest more]</a><span class='clipped_text'>";
				$clip_opened = true;
			}
			
			$html .= get_lookup_display_html($rec['class'], $rec['title'], $rec['href'], $rec['html_val']);
			
			if($i < $cunt - 1)
				$html .= MULTIPLE_RECORDS_SEPARATOR;
		}
		
		if($clip_opened)
			$html .= '</span>';
		
		return $html;
	}
	
	//------------------------------------------------------------------------------------------
	function html($text, $max_chars = 0, $expandable = false, $html_linebreaks = false) {
	//------------------------------------------------------------------------------------------	
		if($text === null)
			return '';
		
		$text = strval($text);
		$len = mb_strlen($text);
		
		if($max_chars > 0 && $len > $max_chars) {
			$ret = htmlspecialchars(mb_substr($text, 0, $max_chars), ENT_COMPAT | ENT_HTML401);
			
			if($expandable)
				$ret .= "<a role='button' title='Text clipped due to length. Click to show clipped text' class='clipped_text'>[...]</a><span class='clipped_text'>" .
				htmlspecialchars(mb_substr($text, $max_chars), ENT_COMPAT | ENT_HTML401) .
				"</span>";
			else
				$ret .= '...';
		}
		
		else
			$ret = htmlspecialchars($text, ENT_COMPAT | ENT_HTML401);
		
		if($html_linebreaks)
			$ret = nl2br($ret);
		
		global $APP;
		if(isset($APP['preprocess_html_func']))
			$ret = $APP['preprocess_html_func']($ret);
		
		return $ret;
	}
	
	//------------------------------------------------------------------------------------------
	function get_lookup_display_html($class, $title, $href, $label_html) {
	//------------------------------------------------------------------------------------------
		if($href === null) {
			if($class == '' && $title == '')
				return $label_html;
			
			return "<span class='$class' title='$title'>$label_html</span>";
		}
		
		return "<a class='$class' title='$title' href=\"?$href\">$label_html</a>";
	}
	
	
	//------------------------------------------------------------------------------------------
	function prepare_field_display_val(&$table, &$record, &$field, $col, $val) {
	//------------------------------------------------------------------------------------------
		global $TABLES;
		global $APP;
		
		if($field['type'] == T_ENUM && $val !== NULL) {
			$val = html($field['values'][$val]);
		}
		else if($field['type'] == T_NUMBER && $val !== NULL) {			
			if(isset($field['max_decimals'])) {
				if(is_array($field['max_decimals'])) {
					if(isset($field['max_decimals'][$_GET['mode']]))
						$val = (float) number_format($val, $field['max_decimals'][$_GET['mode']], '.', '');
					else
						$val = (float) $val;
				}
				else
					$val = (float) number_format($val, $field['max_decimals'], '.', '');
			}
			else
				$val = (float) $val;
		}
		else if($field['type'] == T_PASSWORD) {
			$val = '●●●●●';
		}		
		else if($field['type'] == T_UPLOAD) {
			$val = "<a href='". get_file_url($val, $field) ."'>$val</a>";
		}
		else if($field['type'] == T_LOOKUP && $field['lookup']['cardinality'] == CARDINALITY_SINGLE) {
			$href = isset($TABLES[$field['lookup']['table']]) && in_array(MODE_VIEW, $TABLES[$field['lookup']['table']]['actions']) ? 
			http_build_query(array(
				'table' => $field['lookup']['table'],
				'mode' => MODE_VIEW,
				$field['lookup']['field'] => isset($record[db_postfix_fieldname($col, FK_FIELD_POSTFIX, false)]) ? $record[db_postfix_fieldname($col, FK_FIELD_POSTFIX, false)] : $val
			)) : null;
			
			$html_val = html($val);
			$title = ''; $class = '';
			if($html_val == '') {
				$title = 'There is no display value for this referenced record, so its identifier is displayed here';
				$html_val = html($record[db_postfix_fieldname($col, FK_FIELD_POSTFIX, false)]);
				$class = 'dotted';
			}
		
			$val = get_lookup_display_html($class, $title, $href, $html_val);			
		}
		else if($field['type'] == T_LOOKUP && $field['lookup']['cardinality'] == CARDINALITY_MULTIPLE) {
			if($val !== null && trim($val) != '') {
				#debug_log($val);
				//postgre 9.4+ >> [must go hand in hand with build_query function]
				/*
				$id_display_map = json_decode($val);
				*/ 
				//<< postgre 9.4+
				
				// postgre 9.2				
				$temp_arr = json_decode($val);
				$id_display_map = array();
				for($i=0; $i<count($temp_arr[0]); $i++)
					$id_display_map[$temp_arr[0][$i]] = $temp_arr[1][$i];
				//<< postgre 9.2
				
				$linked_records = array();			
				foreach($id_display_map as $id_val => $display_val) {
					$linked_rec = array();
					$linked_rec['raw_val'] = $display_val;
					
					$params = array(
						'mode' => MODE_VIEW
					);
					
					if($field['lookup']['cardinality'] == CARDINALITY_MULTIPLE 
						&& isset($TABLES[$field['linkage']['table']]) 
						&& is_allowed($TABLES[$field['linkage']['table']], MODE_VIEW)) 
					{
						// display link to n:m table view (typically MODE_VIEW will be allowed when there are additional attributes to the n:m table, otherwise not)
						$params['table'] = $field['linkage']['table'];
						$params[$field['linkage']['fk_other']] = $id_val;
						$params[$field['linkage']['fk_self']] = $record[$table['primary_key']['columns'][0]];
					}
					else {
						// display link to linked item
						$params['table'] = $field['lookup']['table'];
						$params[$TABLES[$field['lookup']['table']]['primary_key']['columns'][0]] = $id_val;
					}
					
					$linked_rec['href'] = isset($TABLES[$params['table']]) && in_array(MODE_VIEW, $TABLES[$params['table']]['actions']) ? http_build_query($params) : null;
					$linked_rec['html_val'] = html($display_val);
					$linked_rec['title'] = ''; 
					$linked_rec['class'] = '';
					if($linked_rec['html_val'] == '') {
						$linked_rec['title'] = 'There is no display value for this referenced record, so its identifier is displayed here';
						$linked_rec['html_val'] = html($linked_rec['raw_val'] = $id_val);
						$linked_rec['class'] = 'dotted';
					}
				
					$linked_records[] = $linked_rec;					
				}				
				$val = html_linked_records($linked_records, $_GET['mode'] == MODE_LIST ? $APP['max_text_len'] : 0);
			}
			else
				$val = '';
		}		
		else {		
			if($_GET['mode'] == MODE_VIEW)
				$val = html($val, 0, false, true);
			else // MODE_LIST: limit chars to display
				$val = html($val, $APP['max_text_len'], true);
		}
		
		return $val;
	}
	
	//------------------------------------------------------------------------------------------
	function html_val($field_name, $default = '') {
	//------------------------------------------------------------------------------------------
		if(!isset($_POST[$field_name]))	{		
		#	if(isset($_GET["pre:{$field_name}"]))
		#		return html($_GET["pre:{$field_name}"]);
			
			return $default;
		}
				
		return html($_POST[$field_name]);
	}
	
	//------------------------------------------------------------------------------------------
	function post_val($name, $default = '') {
	//------------------------------------------------------------------------------------------
		return isset($_POST[$name]) ? $_POST[$name] : $default;
	}
	
	/*
	// NOTE THIS FUNCTION DOES NOT WORK WITH ALL SERVER CONFIGURATIONS. DO NOT USE IT
	// ------------------------------------------------------------------------------------------
	function get_script_url($with_params) {
	//------------------------------------------------------------------------------------------
		$port = $_SERVER['SERVER_PORT'] != '80' ? ":{$_SERVER['SERVER_PORT']}" : '';
		$script = $_SERVER['SCRIPT_NAME'];
		if(substr($script, -9) == 'index.php')
			$script = substr($script, 0, -9);
		
		$url = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['SERVER_NAME']}{$port}/{$script}";
		
		if($with_params)
			$url .= build_get_params();
		
		return $url;
	}*/
	
	//------------------------------------------------------------------------------------------
	// params in $arr_additional override those in $_GET !!
	function build_get_params($arr_additional = array(), $clean = false) {
	//------------------------------------------------------------------------------------------
		$u = array();
		
		if(!$clean) {
			foreach($_GET as $p => $v) {
				if(isset($arr_additional[$p])) {
					$v = $arr_additional[$p];				
					unset($arr_additional[$p]);
				}
				$u[$p] = $v;
			}
		}
		
		foreach($arr_additional as $p => $v)
			$u[$p] = $v;
			
		return '?' . http_build_query($u);
	}
	
	//------------------------------------------------------------------------------------------
	function is_positive_int($v) {
	//------------------------------------------------------------------------------------------
		return preg_match('/^[1-9]+[0-9]*$/', strval($v));
	}
	
	//------------------------------------------------------------------------------------------
	function in_range($val, $bound_lo, $bound_hi, $inclusive = true) {
	//------------------------------------------------------------------------------------------
		return $inclusive ? ($val >= $bound_lo && $val <= $bound_hi) : ($val > $bound_lo && $val < $bound_hi);
	}
	
	//------------------------------------------------------------------------------------------
	function get_session($what, $default = '') {
	//------------------------------------------------------------------------------------------
		if(!isset($_SESSION[$what]))
			return $default;
		
		return $_SESSION[$what];
	}
	
	//------------------------------------------------------------------------------------------
	function render_messages() {
	//------------------------------------------------------------------------------------------
		foreach($_SESSION['msg'] as $msg) {
			echo $msg;
		}
		
		$_SESSION['msg'] = array();
	}
	
	//------------------------------------------------------------------------------------------
	function check_table_name($n) {
	//------------------------------------------------------------------------------------------
		return preg_match('/^[a-zA-Z0-9_]+$/i', $n);
	}
	
	//------------------------------------------------------------------------------------------
	function is_own_user_record(/* bool */ $check_if_password_change_allowed = true) {
	//------------------------------------------------------------------------------------------
		global $LOGIN;
		
		return is_logged_in()
			&& (!$check_if_password_change_allowed || ($check_if_password_change_allowed && is_password_change_allowed()))
			&& $_GET['table'] === $LOGIN['users_table']
			&& isset($_GET[$LOGIN['primary_key']])
			&& $_GET[$LOGIN['primary_key']] == strval($_SESSION['user_id']);
	}
	
	//------------------------------------------------------------------------------------------
	function proc_error($txt, $db = null, $clear_msg_buffer = false) {
	//------------------------------------------------------------------------------------------
		if($clear_msg_buffer)
			$_SESSION['msg'] = array();
		
		$msg = '<div class="alert alert-danger"><b>Error</b>: ' . $txt;
		if(is_object($db)) {			
			$e = $db->errorInfo();
			$msg .= "<ul>\n<li>". str_replace("\n", '</li><li>', html($e[2])) . "</li>\n";
			$msg .= "<li>Error Codes: SQLSTATE {$e[0]}, Driver {$e[1]}</li>\n";
			$msg .= "</ul>\n";
		}
		$msg .= "</div>\n";
		$_SESSION['msg'][] = $msg;		
		
		#debug_log(debug_backtrace());
		
		return false;
	}
	
	//------------------------------------------------------------------------------------------
	function proc_success($txt) {
	//------------------------------------------------------------------------------------------
		$_SESSION['msg'][] = '<div class="alert alert-success"><b>Success</b>: ' . html($txt) . "</div>\n";
		return true;
	}
	
	//------------------------------------------------------------------------------------------
	function proc_info($txt, $db = null) {
	//------------------------------------------------------------------------------------------
		$msg = '<div class="alert alert-info"><b>Information</b>: ' . $txt;
		if(is_object($db)) {			
			$e = $db->errorInfo();
			$msg .= "<ul>\n<li>". str_replace("\n", '</li><li>', html($e[2])) . "</li>\n";
			$msg .= "<li>Error Codes: SQLSTATE {$e[0]}, Driver {$e[1]}</li>\n";
			$msg .= "</ul>\n";
		}
		$msg .= "</div>\n";
		$_SESSION['msg'][] = $msg;
		return true;
	}
	
	//------------------------------------------------------------------------------------------
	function get_inline_fieldname_fk_other() {
	//------------------------------------------------------------------------------------------
		global $TABLES;
		return $TABLES[$_GET['inline']]['fields'][$_GET['lookup_field']]['linkage']['fk_other'];
	}
	
	//------------------------------------------------------------------------------------------
	// returns assoc array with key=>value pairs; false otherwise
	function get_inline_linkage_details($form_id, $field_name, $linked_id,
		/*in assoc array*/ $fk_self_hash = null) {
	//------------------------------------------------------------------------------------------
		$linked_id = strval($linked_id);
		
		if(!isset($_SESSION[$form_id])
			|| !isset($_SESSION[$form_id][$field_name])
			|| !isset($_SESSION[$form_id][$field_name][$linked_id]))
		{
			return false;
		}
		
		if($fk_self_hash !== null && is_array($fk_self_hash)) {
			$fk_self_name = first(array_keys($fk_self_hash));			
			// set fk_self in case it was not there when the inline details were edited (ie new parent record)
			for($i=0; $i<count($_SESSION[$form_id][$field_name][$linked_id]['columns']); $i++) {
				if($_SESSION[$form_id][$field_name][$linked_id]['columns'][$i] === $fk_self_name)
					$_SESSION[$form_id][$field_name][$linked_id]['params'][$i] = $fk_self_hash[$fk_self_name];
			}
		}
		
		return $_SESSION[$form_id][$field_name][$linked_id];
	}
	
	//------------------------------------------------------------------------------------------
	function set_inline_linkage_details($form_id, $field_name, $linked_id, 
		/*const*/ &$arr_details, /*const*/ &$arr_columns, /*const*/ &$arr_params) 
	{
	//------------------------------------------------------------------------------------------
		$linked_id = strval($linked_id);
		
		if(!isset($_SESSION[$form_id]))
			$_SESSION[$form_id] = array();
		
		if(!isset($_SESSION[$form_id][$field_name]))
			$_SESSION[$form_id][$field_name] = array();
		
		if(!isset($_SESSION[$form_id][$field_name]))
			$_SESSION[$form_id][$field_name][$linked_id] = array();
			
		$_SESSION[$form_id][$field_name][$linked_id]['details'] = array_merge($arr_details, array());
		$_SESSION[$form_id][$field_name][$linked_id]['columns'] = array_merge($arr_columns, array());;
		$_SESSION[$form_id][$field_name][$linked_id]['params'] = array_merge($arr_params, array());
		#$_SESSION[$form_id][$field_name][$linked_id]['empty_key_indexes'] = array_merge($empty_key_indexes, array());
	}
	
	//------------------------------------------------------------------------------------------
	function get_file_url($file_name, $field_info) {
	//------------------------------------------------------------------------------------------
		$url = $field_info['location'] . '/' . $file_name;
		return str_replace('//', '/', $url);
	}
	
	//------------------------------------------------------------------------------------------
	function get_field_sort_expression(&$field) {
	//------------------------------------------------------------------------------------------
		if(!isset($field['sort_expr']))
			return '%s';
		
		return $field['sort_expr'];
	}
	
	//------------------------------------------------------------------------------------------
	function is_field_hidden_in_list(&$field) {
	//------------------------------------------------------------------------------------------
		return isset($field['list_hide']) && $field['list_hide'] === true;
	}
	
	//------------------------------------------------------------------------------------------
	function is_field_editable(&$field) {
	//------------------------------------------------------------------------------------------
		return !isset($field['editable']) || $field['editable'] === true;
	}
	
	//------------------------------------------------------------------------------------------
	function is_field_trim(&$field) {
	//------------------------------------------------------------------------------------------
		return !isset($field['trim']) || $field['trim'] === true;
	}
	
	//------------------------------------------------------------------------------------------
	function is_field_reset(&$field) {
	//------------------------------------------------------------------------------------------
		return isset($field['reset']) && $field['reset'] === true;
	}

	//------------------------------------------------------------------------------------------
	function is_allowed_create_new(&$field) {
	// default is true
	//------------------------------------------------------------------------------------------
		if(isset($field['allow-create']) && $field['allow-create'] === false)
			return false;
		
		return true;
	}
	
	
	//------------------------------------------------------------------------------------------
	function is_field_required(&$field_info) {
	//------------------------------------------------------------------------------------------
		return isset($field_info['required']) && $field_info['required'] === true;
		//return isset($field_info['required']) && $field_info['required'] === 'false';
	}
	
	//------------------------------------------------------------------------------------------
	function is_field_setnull($field_name, &$field_info) {
	//------------------------------------------------------------------------------------------
		return 
			(isset($_POST["{$field_name}__null__"]) && $_POST["{$field_name}__null__"] === 'true') 
			
			||
			
			(isset($_POST[$field_name]) && $_POST[$field_name] === NULL_OPTION && 
			 ($field_info['type'] == T_ENUM || $field_info['type'] == T_LOOKUP));
	}
	
	//------------------------------------------------------------------------------------------
	function get_the_primary_key_value_from_url($table, $default_if_missing) {
	//------------------------------------------------------------------------------------------
		$pk_name = $table['primary_key']['columns'][0];
		
		if(!isset($_GET[$pk_name]))
			return $default_if_missing;
		
		return $_GET[$pk_name];
	}
		
	//------------------------------------------------------------------------------------------
	function get_primary_key_values_from_url($table) {
	//------------------------------------------------------------------------------------------
		$pk_vals = array();
		
		foreach($table['primary_key']['columns'] as $pk) {
			if(!isset($_GET[$pk]))				
				return proc_error("Key '$pk' of object to edit not provided");
			
			$pk_vals[$pk] = $_GET[$pk];
		}
		
		return $pk_vals;
	}
	
	//------------------------------------------------------------------------------------------
	function db_esc($name, $qualifier = null) {
	//------------------------------------------------------------------------------------------
		global $DB;
		
		switch($DB['type']) {
			case DB_POSTGRESQL:
				$escape_char = '"';
				$separator_char = '.';
				break; 
				
			default:
				return proc_error('Invalid database type specified in config/settings.php'); 
		}
		
		if($name[0] == $escape_char)
			return $name; // already escaped
		
		if($qualifier !== null)
			return $escape_char . $qualifier . $escape_char . $separator_char . $escape_char . $name . $escape_char;
		else
			return $escape_char . $name . $escape_char;
	}
	
	//------------------------------------------------------------------------------------------
	// $return_escaped:
	//   if NULL, it will return escaped only of $fieldname is already escaped, otherwise not
	//   if TRUE/FALSE, it will/will not escape the postfixed fieldname
	function db_postfix_fieldname($fieldname, $postfix, $return_escaped) {
	//------------------------------------------------------------------------------------------
		global $DB;
		
		switch($DB['type']) {
			case DB_POSTGRESQL:
				$escape_char = '"';
				break; 
				
			default:
				return proc_error('Invalid database type specified in config/settings.php'); 
		}
		
		$fieldname_unescaped = trim($fieldname, $escape_char);
		$was_escaped = ($fieldname_unescaped == $fieldname);
		$do_escape = ($return_escaped === TRUE || ($return_escaped === NULL && $was_escaped === TRUE));
		
		if(!$do_escape)
			$escape_char = '';
		
		return "{$escape_char}{$fieldname}{$postfix}{$escape_char}";
	}
	
	//------------------------------------------------------------------------------------------
	function db_get_single_val($sql, $params, &$retrieved_value) {
	//------------------------------------------------------------------------------------------
		$db = db_connect();
		if($db === false)
			return proc_error('Cannot connect to DB.');
		
		$stmt = $db->prepare($sql);
		if($stmt === false)
			return proc_error('Preparing SQL statement failed', $db);
		
		if(false === $stmt->execute($params))
			return proc_error('Executing SQL statement failed', $db);
		
		$retrieved_value = $stmt->fetchColumn();
		return true;
	}
	
	//------------------------------------------------------------------------------------------
	function db_get_single_row($sql, $params, &$row) {
	//------------------------------------------------------------------------------------------
		$db = db_connect();
		if($db === false)
			return proc_error('Cannot connect to DB.');
		
		$stmt = $db->prepare($sql);
		if($stmt === false)
			return proc_error('Preparing SQL statement failed', $db);
		
		if(false === $stmt->execute($params))
			return proc_error('Executing SQL statement failed', $db);
		
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		return true;
	}
	
	//------------------------------------------------------------------------------------------
	function get_file_upload_error_msg($code) {
	//------------------------------------------------------------------------------------------
    
        switch ($code) { 
            case UPLOAD_ERR_INI_SIZE: 
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
                break; 
            case UPLOAD_ERR_FORM_SIZE: 
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"; 
                break; 
            case UPLOAD_ERR_PARTIAL: 
                $message = "The uploaded file was only partially uploaded"; 
                break; 
            case UPLOAD_ERR_NO_FILE: 
                $message = "No file was uploaded"; 
                break; 
            case UPLOAD_ERR_NO_TMP_DIR: 
                $message = "Missing a temporary folder"; 
                break; 
            case UPLOAD_ERR_CANT_WRITE: 
                $message = "Failed to write file to disk"; 
                break; 
            case UPLOAD_ERR_EXTENSION: 
                $message = "File upload stopped by extension"; 
                break; 
            default: 
                $message = "Unknown upload error"; 
                break; 
        } 
        return $message; 
    } 
	
	//------------------------------------------------------------------------------------------
	function resolve_display_expression($display, $table_qualifier = '') {
	//------------------------------------------------------------------------------------------
		if($table_qualifier != '')
			$table_qualifier = db_esc($table_qualifier) . '.';
		
		if(!is_array($display)) // simple field name string
			return $table_qualifier . db_esc($display);
			
		// here we have something like 
		// 'display' => [ 'columns' => ['firstname', 'lastname'], 'expression' => "concat_ws(' ', %1 %2)" ]
		if(!isset($display['columns']) || !is_array($display['columns']) || !isset($display['expression']))
			proc_error('Invalid display expression');
		
		$expr = $display['expression'];
		for($i = 1; /* loop until nothin is replaced any more */; $i++) {
			// keep replacing as long as there is something to replace
			if(strpos($expr, "%{$i}") === FALSE)
				return $expr;
			
			$expr = str_replace("%{$i}", $table_qualifier . db_esc($display['columns'][$i - 1]), $expr);
		}
		
		return proc_error('Something is totally wrong here. Contact your therapist.');
	}
	
	//------------------------------------------------------------------------------------------	
	function build_query($table_name, $table, $offset, $mode, $more_params, &$out_params) {
	//------------------------------------------------------------------------------------------		
		global $APP;
		global $TABLES;
		
		$out_params = array();
		
		if($mode != MODE_EDIT && $mode != MODE_LIST && $mode != MODE_VIEW)
			return proc_error('Unknown page mode.');
		
		$q = 'SELECT ';
		
		$cols = '';
		foreach($table['fields'] as $field_name => $field) {
			if($cols != '') 
				$cols .= ', ';
			
			// geometry
			if($field['type'] == T_POSTGIS_GEOM) {
				$cols .= sprintf('ST_AsText(%s) %s',  db_esc($field_name), db_esc($field_name));
				continue;
			}
			
			// lookup single field
			if($field['type'] == T_LOOKUP && $field['lookup']['cardinality'] == CARDINALITY_SINGLE) {
				if($mode == MODE_LIST || $mode == MODE_VIEW) {
					$cols .= sprintf('(SELECT %s FROM %s WHERE %s = t.%s) %s, t.%s %s',
						resolve_display_expression($field['lookup']['display']),
						db_esc($field['lookup']['table']), db_esc($field['lookup']['field']),
						db_esc($field_name), db_esc($field_name), db_esc($field_name), 
						db_postfix_fieldname($field_name, FK_FIELD_POSTFIX, true)); 
				}				
				else {
					$cols .= db_esc($field_name);
				}
				
				continue;
			}
						
			// lookup multiple records 
			//TODO: WORK WITH COMPOSITE FK_SELF AND FK_OTHER
			if($field['type'] == T_LOOKUP && $field['lookup']['cardinality'] == CARDINALITY_MULTIPLE) {
				if($mode == MODE_LIST || $mode == MODE_VIEW) {
					// postgres 9.4+ >> (must go hand in hand with prepare_field_display_val function)
					/*$cols .= sprintf(
						"(SELECT json_object_agg(%s,%s) " .
						'FROM %s other, %s link WHERE link.%s = t.%s AND other.%s = link.%s) %s',
						db_esc($TABLES[$field['lookup']['table']]['primary_key']['columns'][0]), resolve_display_expression($field['lookup']['display'], 'other'),
						db_esc($field['lookup']['table']), db_esc($field['linkage']['table']),
						db_esc($field['linkage']['fk_self']), db_esc($table['primary_key']['columns'][0]),
						db_esc($field['lookup']['field']), db_esc($field['linkage']['fk_other']), db_esc($field_name));
					*/
					//<< postgres 9.4+
					
					// postgres 9.2 >>
					$cols .= sprintf(
						"(SELECT '[' || array_to_json(array_agg(%s)) || ',' || array_to_json(array_agg(%s)) || ']' " .
						'FROM %s other, %s link WHERE link.%s = t.%s AND other.%s = link.%s) %s',
						db_esc($field['lookup']['field'], 'other'), resolve_display_expression($field['lookup']['display'], 'other'),
						db_esc($field['lookup']['table']), db_esc($field['linkage']['table']),
						db_esc($field['linkage']['fk_self']), db_esc($table['primary_key']['columns'][0]),
						db_esc($field['lookup']['field']), db_esc($field['linkage']['fk_other']), db_esc($field_name));
					//<< postgres 9.2
				}
				else { // MODE_EDIT					
					$cols .= sprintf("(SELECT array_to_json(array_agg(link.%s)) ".
							 "FROM %s link WHERE link.%s = ?) %s",
							 db_esc($field['linkage']['fk_other']), 
							 db_esc($field['linkage']['table']), db_esc($field['linkage']['fk_self']),
							 db_esc($field_name));
					
					$vals = array_values($offset);
					$out_params[] = $vals[0];
				}					
					
				continue;
			}
			
			// normal field!
			$cols .= db_esc($field_name);			
		}
		
		// now add any keys that are lookup values as "raw" fields, to properly create the link for list view
		if($mode == MODE_LIST || $mode == MODE_VIEW) {
			$pk_fields = '';
			foreach($table['primary_key']['columns'] as $pk)
				$pk_fields .= sprintf(', %s %s', db_esc($pk), db_postfix_fieldname($pk, FK_FIELD_POSTFIX, true));
			
			$cols .= $pk_fields;
		}
		
		$q .= sprintf('%s FROM %s t', $cols, db_esc($table_name)); 
		
		if($mode == MODE_EDIT || $mode == MODE_VIEW) {
			//TODO: WORK WITH COMPOSITE FK_SELF AND FK_OTHER
			$where = '';
			foreach($offset as $col => $val) {
				$where .= ($where != ''? ' AND ' : ' ') . db_esc($col) . ' = ?';
				$out_params[] = $val;
			}
			$q .= " WHERE $where";
		}
		
		if($mode == MODE_LIST) {
			$search = build_search_term($table, 't');
			
			if($search !== null) { // search is on
				$q .= ' WHERE ' . $search['sql'];
				
				foreach($search['params'] as $param)
					$out_params[] = $param;
			}
			
			$order_by = array();
			
			if(isset($_GET['sort']) && isset($table['fields'][$_GET['sort']])) {
				$dir = isset($_GET['dir']) ? $_GET['dir'] : 'asc';
				if($dir != 'asc' && $dir != 'desc')
					$dir = 'asc';				
				
				$order_by[] = sprintf(get_field_sort_expression($table['fields'][$_GET['sort']]), db_esc($_GET['sort'])) . " $dir";
			}
			
			if(count($order_by) == 0 && isset($table['sort']) && is_array($table['sort']) && count($table['sort']) > 0 ) {
				foreach($table['sort'] as $field_name => $dir) {
					$order_by[] = sprintf(get_field_sort_expression($table['fields'][$field_name]), db_esc($field_name)) . " $dir";						
					
					// fake the $_GET for later
					$_GET['sort'] = $field_name;
					$_GET['dir'] = $dir;
				}
			}
			
			if(count($order_by) > 0)
				$q .= ' ORDER BY ' . implode(', ', $order_by);
			
			$q .= " LIMIT ". $APP['page_size'] . " OFFSET $offset";
		}
		
#		debug_log($q);
		return $q;
	}
	
	//------------------------------------------------------------------------------------------
	// this function by Scott on http://stackoverflow.com/a/13733588/5529515
	function crypto_rand_secure($min, $max) {
	//------------------------------------------------------------------------------------------	
		$range = $max - $min;
		if ($range < 1) return $min; // not so random...
		$log = ceil(log($range, 2));
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd >= $range);
		return $min + $rnd;
	}

	//------------------------------------------------------------------------------------------
	// this function by Scott on http://stackoverflow.com/a/13733588/5529515
	function get_random_token($length) {
	//------------------------------------------------------------------------------------------	
		$token = '';
		$codeAlphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$codeAlphabet.= 'abcdefghijklmnopqrstuvwxyz';
		$codeAlphabet.= '0123456789';
		$max = strlen($codeAlphabet) - 1;
		for ($i=0; $i < $length; $i++) {
			$token .= $codeAlphabet[crypto_rand_secure(0, $max)];
		}
		return $token;
	}	
	
	//------------------------------------------------------------------------------------------
	function enable_delete() {
	//------------------------------------------------------------------------------------------
	echo <<<END
			<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<!--<div class="modal-header">                
						</div>-->
						<div class="modal-body">
							<h4>Confirm Delete</h4>
							Please confirm that you want to delete this record. This action cannot be undone. Note the deletion will only work if the record is not referenced by some other record.				
						</div>			
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
							<a class="btn btn-danger btn-ok">Delete</a>
						</div>
					</div>
				</div>
			</div>

			<script>
			$('#confirm-delete').on('click', '.btn-ok', function(e) {				
				$.get($(this).data('href'), function(data) {
					$('#confirm-delete').modal('hide');
					
					if(data == 'SUCCESS')
						location.reload();
					else
						$('#main-container').prepend( $(data) );
				});
			});
			$('#confirm-delete').on('show.bs.modal', function(e) {
			  var data = $(e.relatedTarget).data();  
			  $('.btn-ok', this).data('href', data.href);
			});
			</script>
END;
	}
?>