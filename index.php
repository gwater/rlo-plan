<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Online-Vertretungsplan BETA</title>
</head><body>
<div id="header">Willkommen beim Online-Vertretungsplan BETA der Rosa-Luxemburg-Oberschule!<br><br>
Liebe Tester,<br>
Was Ihr vor Euch seht ist der aktuelle Stand in Sachen Vertretungsplan.
Kein schickes Design und keine echten Ausfallstunden.
Daf&uuml;r voll funktionsf&auml;hig.
Um Das zu demonstrieren, d&uuml;rft Ihr Euch hier voll austoben:<br>
Bearbeitet <a href=lange.php>den Vertretungsplan</a>.<br>
Druckt euch die <a href=offline.php>Offline Version</a> f&uuml;rs Schulhaus aus.<br>
Oder seht euch direkt auf dieser Seite die Online Version an, die Euch bald t&auml;glich mit wertvollen Informationen versorgen wird.<br><br>
Wie gesagt, ist dies eine Test- und Probierausgabe des Online Vertretungsplans. Wir haben die meisten Sicherheitsfunktionen deaktiviert, um das System transparent zu machen. Das bedeutet nicht, dass wir die Datenschutzversprechen und Kompromisse aus den Verhandlungen des letzten Jahres vergessen oder ignoriert haben.<br><br>
&Uuml;brigens, dieses Projekt wurde initiert und begleitet durch <a href=http://es-geht-um-euch.de/>Peter Kuscher und sein Team.</a><br>
Wir finden Peters Bem&uuml;hungen f&uuml;r die Sch&uuml;lerschaft beispiellos und hoffen mit ihm auf eine erfolgreiche Wahl am Freitag.<br>

<br><br></div>
<?php
	if ($_GET['logout'] == 'true') {
		echo '<font color="green">Sie wurden abgemeldet.<br><br></font>';
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
<a href="javascript:window.print()">Seite ausdrucken</a><br><br>
Der Online-Vertretungsplan wird entwickelt von Tillmann Karras und Josua Grawitter. Daher<br>
(C) 2009 Tillmann Karras und Josua Grawitter<br>
Der Quellcode ist frei verf&uuml;gbar unter http://github.com/gwater/rlo-plan.git .<br>
Wer mithelfen m&ouml;chte, Fehler gefunden oder gute Vorschl&auml;ge hat, mailt bitte an grewater [&uuml;t&uuml;r&uuml;t&uuml;&uuml;] googlemail.com .<br><br>
Wir brauchen ganz besonders ein paar talentierte Webdesigner, die diesem funktionalen System ein wenig k&uuml;nstlerischen Glanz verpassen k&ouml;nnen.
</body>
</html>
