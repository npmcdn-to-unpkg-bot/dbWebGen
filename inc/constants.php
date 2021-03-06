<?
	//
	// DO NOT EDIT THESE CONSTANTS UNLESS YOU KNOW WHAT YOU ARE DOING!!!
	//
	
	// supported databases
	define('DB_POSTGRESQL', 'postgresql');	
	
	// input types for different DB fields:
	define('T_TEXT_LINE', 'T_TextLine');	
	define('T_NUMBER', 'T_Number');	
	define('T_TEXT_AREA', 'T_TextArea');
	define('T_ENUM', 'T_Enum'); // can also emulate boolean	
	define('T_LOOKUP', 'T_ForeignKeyLookup'); // foreign key relationships
	define('T_PASSWORD', 'T_Password');
	define('T_UPLOAD', 'T_FileUpload');
	define('T_POSTGIS_GEOM', 'T_PostgisGeometry'); // postgis only!
	
	// m:n and 1:n relationships for T_LOOKUP types
	define('CARDINALITY_SINGLE', 'CARDINALITY_SINGLE');
	define('CARDINALITY_MULTIPLE', 'CARDINALITY_MULTIPLE');
	
	// null value indicator for non-required dropdowns
	define('NULL_OPTION', '__NULL__');
	
	// field name postfix for raw foreign key values (should create reasonably unique field name, i.e. no human assigned field should end with the same string)
	define('FK_FIELD_POSTFIX', '__3ffz3h_k031n');
	
	// where to store uploaded filesize. can be binary or'ed
	define('STORE_FOLDER', 0x1);
	define('STORE_DB', 0x2); // TODO: not implemented yet
	
	// temporary form identifier
	define('TEMP_FORM_ID', '__temp-id__');	
	
	// special function names called via MODE_FUNC
	define('LINKED_ITEM_HTML', 'get_linked_item_html');
	define('GET_SHAREABLE_QUERY_LINK', 'get_shareable_query_link');
	define('VISJS_NETWORK_CACHE_POSITIONS', 'visjs_network_cache_positions');
	
	// search options
	define('SEARCH_ANY', 'any');
	define('SEARCH_START', 'start');
	define('SEARCH_END', 'end');
	define('SEARCH_EXACT', 'exact');
	define('SEARCH_WORD', 'word'); // similar to SEARCH_ANY, but search string must occur as a whole word

	define('SEARCH_PARAM_FIELD', 'field');
	define('SEARCH_PARAM_QUERY', 'q');
	define('SEARCH_PARAM_OPTION', 'match');
	define('SEARCH_PARAM_LOOKUP', 'lookup');
	
	// params for MODE_PLUGIN
	define('PLUGIN_PARAM_FUNC', 'func');
	define('PLUGIN_PARAM_NAVBAR', 'navbar');
	define('PLUGIN_NAVBAR_ON', 'on');
	define('PLUGIN_NAVBAR_OFF', 'off');
	
	// params for MODE_QUERY
	define('QUERY_PARAM_VIEW', 'view');
	define('QUERY_PARAM_ID', 'id');
	define('QUERY_VIEW_FULL', 'full'); // default
	define('QUERY_VIEW_RESULT', 'result'); // result only
	
	// length of identifier for stored queries
	define('STORED_QUERY_ID_LENGTH', 12);
	
	// dynamic runtime replacements for default field values
	define('REPLACE_DYNAMIC_SESSION_USER', '%SESSION_USER%');
	
	// prefix for url parameters that should be used as prefill values in MODE_NEW
	define('PREFILL_PREFIX', 'pre:');
	
	// separator character(s) for inline display of multiple records
	define('MULTIPLE_RECORDS_SEPARATOR', ' ● ');
	
	// viewing modes (reflected in URL parameter mode=XXX)
	define('MODE_NEW', 'new');
	define('MODE_EDIT', 'edit');
	define('MODE_LIST', 'list');
	define('MODE_VIEW', 'view');
	define('MODE_QUERY', 'query');
	
	// pseudo modes
	define('MODE_LINK', 'link'); // providing a link for rendering the actual object, not the record
	define('MODE_DELETE', 'delete');
	define('MODE_CREATE_DONE', 'create_done');
	define('MODE_LOGOUT', 'logout');	
	define('MODE_FUNC', 'func');
	define('MODE_PLUGIN', 'plugin'); // used to build the main page content in an external function
?>