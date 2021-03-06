<?
	/*
		This is the main application engine that renders the user interface and 
		delegates the processing of every major action.
	*/
	mb_internal_encoding('UTF-8');
	mb_http_output('UTF-8');
	
	// append scripts using add_javascript() and add_stylesheet() 
	$META_INCLUDES = array();
	
	if(!defined('ENGINE_PATH'))
		die('This is the engine, you put your app into another directory and define ENGINE_PATH to point here. Note: ENGINE_PATH must end with a slash.');	
	
	require_once ENGINE_PATH . 'inc/constants.php';
	require_once 'settings.php';
	require_once ENGINE_PATH . 'inc/helper.php';
	require_once ENGINE_PATH . 'inc/container.php';
	require_once ENGINE_PATH . 'inc/login.php';	
	require_once ENGINE_PATH . 'inc/page.php';
	
	// initialize session and plugins and stuff 
	{
		if(isset($APP['timezone']))
			date_default_timezone_set($APP['timezone']);
		
		if(isset($APP['plugins']))
			foreach(array_values($APP['plugins']) as $plugin)
				require_once $plugin; // we want to load plugins in global scope
		
		session_start();
		if(isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 3600)) {
			session_unset();
			session_destroy();
			session_start();
		}
		$_SESSION['LAST_ACTIVITY'] = time();		
		
		if(!isset($_SESSION['msg']))
			$_SESSION['msg'] = array();
		
		if(isset($LOGIN['initializer_proc']) && $LOGIN['initializer_proc'] != '')
			call_user_func($LOGIN['initializer_proc']); // allow the app to do some initialization
	}
	
	// any special processing?
	if(is_logged_in()) switch(safehash($_GET, 'mode', '')) {
		case MODE_DELETE:
			require_once ENGINE_PATH . 'inc/delete.php';	
			if(!process_delete())				
				render_messages();
			exit;
			
		case MODE_CREATE_DONE:
			require_once ENGINE_PATH . 'inc/create_new_done.php';
			process_create_new_done();			
			exit;
			
		case MODE_LOGOUT:			
			session_logout();			
			exit;
			
		case MODE_FUNC:
			require_once ENGINE_PATH . 'inc/func.php';
			process_func();
			exit;
	}
	
	ob_start();		
?>
<!DOCTYPE html>
<head>
  <title><?= $APP['title'] ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">  
  <link rel="stylesheet" href="<?= bootstrap_css() ?>">  
  <link rel="icon" href="<?= page_icon() ?>">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.0/jquery.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/css/select2.min.css" rel="stylesheet">
  <link href="https://select2.github.io/select2-bootstrap-theme/css/select2-bootstrap.css" rel="stylesheet"><!-- Bootstrap theme from https://github.com/select2/select2-bootstrap-theme -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.2/js/select2.min.js"></script>  
  <link href="<?= ENGINE_PATH ?>inc/styles.css" rel="stylesheet">  
  <script src="<?= ENGINE_PATH ?>inc/dbweb.js"></script>
  <!--META_INCLUDES_GO_HERE-->
</head>
<body>

<? render_navigation_bar(); ?>

<div id='main-container' class="container-fluid">	
<?
	$page_head = ob_get_contents();
	ob_end_clean();
	
	ob_start();
	render_main_container();		
	$page_body = ob_get_contents();
	ob_end_clean();
	
	if(process_redirect())
		exit;
	
	echo str_replace(
		'<!--META_INCLUDES_GO_HERE-->', 
		implode("\n", $META_INCLUDES), 
		$page_head);
	
	render_messages();
	if(isset($page_body) && $page_body != null)
		echo $page_body;
?>
</div>
</body>
</html>