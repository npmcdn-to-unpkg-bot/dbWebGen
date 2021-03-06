<?
	//==========================================================================================
	abstract class dbWebGenChart {
	//==========================================================================================
		protected $page;	
		protected $type;		
		
		//--------------------------------------------------------------------------------------
		public function __construct($type, $page) {
		//--------------------------------------------------------------------------------------
			$this->page = $page;		
			$this->type = $type;
		}
		
		//--------------------------------------------------------------------------------------
		public function type() {
		//--------------------------------------------------------------------------------------
			return $this->type;
		}
		
		//--------------------------------------------------------------------------------------
		// returns a chart instance based on the chart type
		public static function create($chart_type, $page) {
		//--------------------------------------------------------------------------------------
			$class_name = 'dbWebGenChart_' . $chart_type;
			return new $class_name($chart_type, $page);
		}
		
		// returns html form for chart settings
		// form field @name must be prefixed with exact charttype followed by dash
		abstract public /*string*/ function settings_html();
		
		// override if additional scripts are needed for this type
		abstract public /*void*/ function add_required_scripts();
		
		// returns js code to fill the chart div
		abstract public /*string*/ function get_js(/*PDOStatement*/ $query_result);
		
		// returnes cached js for visualization, or false if no cache exists
		public /*string | false*/ function cache_get_js() { return false; }
		
		// store cached js of visualization; true on success, else false
		public /*bool*/ function cache_put_js($js) {  }
		
		// returns the chart code version. this is only used for caching. 
		// if this code is newer version than the cached version, the cache is emptied.	
		// default version = 1; override and increment to ignore any existing cache
		public /*int*/ function cache_get_version() { return 1; }
		
		// returns the time to live of the cache. default 1 hour. override to change.		
		public /*int*/ function cache_get_ttl() { return 3600; }

		// returns the cache directory
		public /*string*/ function cache_get_dir() { 
			global $APP;
			return $APP['cache_dir'] . '/' . $this->type;
		}		
	};
?>