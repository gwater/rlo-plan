<div id="tables">
<?php
	$handle = fopen('data.txt', 'r');
	$first = true;						// first table doesn't have to close another one
	$last_day = NULL;
	while (($fields = fgetcsv($handle)) !== FALSE) {

		// FIXME: somehow setlocale() doesn't work
		$daynames = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
		$day = $daynames[strftime('%w', $fields[0])];

		if ($last_day != $day) {
			$last_day = $day;
			if ($first) {
				$first = false;			// all following new tables will have to close the previous
			} else {
				echo '</tbody></table><br>';	// close the previous table
			}
?>
<div style="margin-left: 20px; text-decoration: underline;"><?=$day?></div><br>
<table style="text-align: left;" border="1" cellpadding="2" cellspacing="0">
<tbody>
  <tr>
<?php
			if (isset($offline_view)) {
				echo '
    <th>Uhrzeit</th>
    <th>Lehrer</th>
    <th>Fach/Kurs</th>
    <th>Dauer</th>
    <th>Klasse</th>
    <th>Vertretung</th>
    <th>&Auml;nderung</th>
';
			} else {
				echo '
    <th>Uhrzeit</th>
    <th>Fach/Kurs</th>
    <th>Klasse</th>
    <th>Originalraum</th>
    <th>&Auml;nderung</th>
';
			}
			echo '  </tr>';
		}
		$param = $_GET["klasse"];

		// filter out non-matching lines
		if (!isset($param) || substr_compare($fields[4], $param, 0, 2) == 0) {
			echo "  <tr>\n";
			if (isset($offline_view)) {
				echo_cell(strftime('%H.%M', $fields[0]));
				echo_cell($fields[1]);
				echo_cell($fields[2]);
				echo_cell($fields[3]);
				echo_cell($fields[4]);
				echo_cell($fields[6]);
				echo_cell($fields[7]);
			} else {
				echo_cell(strftime('%H.%M', $fields[0]));
				echo_cell($fields[2]);
				echo_cell($fields[4]);
				echo_cell($fields[5]);
				echo_cell($fields[7]);
			}
			echo "  </tr>\n";
		}
	}
	if (!$first) {
		echo "</tbody></table><br>\n";
	}
	
	function echo_cell($str) {
		echo '    <td>'.$str."</td>\n";
	}
?>
</div>
