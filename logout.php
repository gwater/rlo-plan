<?php
	session_start();
	if($_SESSION['id']) {
		session_destroy();
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$page = 'index.php?logout=true';
		header("Location: http://$host$uri/$page");
		exit;
}
?>