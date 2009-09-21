<?php
	session_start();
	if (!$_SESSION['id']) {
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$page = 'login.php?continue=http://'.$host.$_SERVER['PHP_SELF'];
		header("Location: http://$host$uri/$page");
		exit;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Druckansicht - Online-Vertretungsplan</title>
</head><body>
<div id="tables">
<?php
$handle = fopen("data.txt", "r");
$fulldate = getdate();
$offset = $fulldate['hours']*60*60 - $fulldate['minutes']*60;
$today = time() - $offset;
if ($_GET['date']) {
	$today = strtotime($_GET['date']);
}

$tomorrow = strtotime("+1 day", $today);
$yesterday = strtotime("-1 day", $today);

$i = 0;
while (($set = fgetcsv($handle, 1000, ",")) !== FALSE) {
	$lesson = (int) $set[0];
	// only display lessons for one day
	if (($lesson > $today) && ($lesson < $tomorrow)) {
		$data[$i] = $set;
		$i++;
	}
}
if (!isset($data)) {
	echo 'Keine Eintr&auml;ge f&uuml;r diesen Tag.';
} else {

	function compare_teacher($a, $b)
	{
		// ignore genders, be politically correct
		$a = substr($a, 5);
		$b = substr($b, 5);
		// sort by teacher and time
		$ret = strnatcmp($a[1], $b[1]);
		if (!$ret) return strnatcmp($a[0], $b[0]);
		return $ret;
	}
	// sort alphabetically by name and time
	usort($data, 'compare_teacher');

	$previous = '';

	echo '<table border="1" cellpadding="2" cellspacing="0">';
	echo "<tr>
			<td>Uhrzeit</td>
			<td>Klasse</td>
			<td>Fach</td>
			<td>Dauer</td>
			<td>Vertretung durch</td>
			<td>Raum</td>
		</tr>";


	foreach ($data as $set) {
		if ( $previous != $set[1]) {
			echo '<tr>' .
					'<th align="left" colspan="6"><br>'.$set[1].'</th>' .
				'</tr>';
		}

		#$date = date('d.m.Y',(int) $set[0]);
		$time = date('G:i', (int) $set[0]);
		echo "<tr>
				<td>$time</td>
				<td>$set[4]</td>
				<td>$set[2]</td>
				<td>$set[3]</td>
				<td>$set[6], $set[7]</td>
				<td>$set[5]</td>
			</tr>";

		$previous = $set[1];

	}
	echo '</table>';
}

echo '<br><a href="?date='.date('Y-m-d', $yesterday).'">&lt; ein Tag zur&uuml;ck</a>';
echo ' | <a href="?date='.date('Y-m-d', $tomorrow).'">ein Tag weiter &gt;</a>';
?>
</div>
<br><a href="javascript:window.print()">Seite ausdrucken</a>
</body>
</html>
