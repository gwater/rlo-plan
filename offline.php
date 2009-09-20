<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Online-Vertretungsplan</title>
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

$tomorrow = $today + 24*60*60;
$yesterday = $today - 24*60*60;


$i = 0;
while (($set = fgetcsv($handle, 1000, ",")) !== FALSE) {
	$lesson = (int) $set[0];
	// only display lessons for today
	if (($lesson > $today) && ($lesson < $tomorrow)) {
		$data[$i] = $set;
		$i++;
	}
}

//FIXME: day is ignored - all entries from data.txt will be used.
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
// sort alphabetically by name
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


	if ($previous != $set[1]) {
		$previous = $set[1];
	}

}
echo '</table>';

echo '<a href="?date='.date('Y-m-d', $yesterday).'">Gehe einen Tag zurück.</a><br>';
echo '<a href="?date='.date('Y-m-d', $tomorrow).'">Gehe einen Tag weiter.</a>';
?>
</div>
</body>
</html>