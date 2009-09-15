<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Online-Vertretungsplan</title>
</head><body>
<div id="header">Willkommen beim Online-Vertretungsplan der Rosa-Luxemburg-Oberschule!<br><br></div>
<?php
	if ($_GET['logout'] == 'true') {
		echo '<div id="note">Sie wurden abgemeldet.<br><br></div>';
	}
?>
<div id="filters">
<?php
	// show line of links that filter the tables, currently selected filter is not a link
	echo $_GET["klasse"] == NULL ? 'Alle Klassenstufen' : '<a href="index.php">Alle Klassenstufen</a>';
	echo $_GET["klasse"] == "5." ? ' | 5.' : ' | <a href="index.php?klasse=5.">5.</a>';
	echo $_GET["klasse"] == "6." ? ' | 6.' : ' | <a href="index.php?klasse=6.">6.</a>';
	echo $_GET["klasse"] == "7." ? ' | 7.' : ' | <a href="index.php?klasse=7.">7.</a>';
	echo $_GET["klasse"] == "8." ? ' | 8.' : ' | <a href="index.php?klasse=8.">8.</a>';
	echo $_GET["klasse"] == "9." ? ' | 9.' : ' | <a href="index.php?klasse=9.">9.</a>';
	echo $_GET["klasse"] == "10" ? ' | 10.' : ' | <a href="index.php?klasse=10">10.</a>';
	echo $_GET["klasse"] == "11" ? ' | 11.' : ' | <a href="index.php?klasse=11">11.</a>';
	echo $_GET["klasse"] == "12" ? ' | 12.' : ' | <a href="index.php?klasse=12">12.</a>';
	echo $_GET["klasse"] == "13" ? ' | 13.' : ' | <a href="index.php?klasse=13">13.</a>';
?>
<br><br></div>
<?php
	// show tables filtered by GET parameter
	include('data.php');
?>
<a href="javascript:window.print()">Seite ausdrucken</a>
</body>
</html>
