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
<div style="margin-left: 40px;"><big style="text-decoration: underline;"><?=$day?></big></div><br>
<table style="text-align: left; width: 80%;" border="1" cellpadding="2" cellspacing="2">
<tbody>
  <tr>
<?php
			if (isset($offline_data)) {
				echo '
    <td><span style="font-weight: bold;">Uhrzeit</span></td>
    <td><span style="font-weight: bold;">Lehrer</span></td>
    <td><span style="font-weight: bold;">Fach/Kurs</span></td>
    <td><span style="font-weight: bold;">Klasse</span></td>
    <td><span style="font-weight: bold;">Vertretung</span></td>
    <td><span style="font-weight: bold;">&Auml;nderung</span></td>';
			} else {
				echo '
    <td><span style="font-weight: bold;">Uhrzeit</span></td>
    <td><span style="font-weight: bold;">Fach/Kurs</span></td>
    <td><span style="font-weight: bold;">Klasse</span></td>
    <td><span style="font-weight: bold;">Originalraum</span></td>
    <td><span style="font-weight: bold;">&Auml;nderung</span></td>';
			}
			echo '  </tr>';
		}
		$param = $_GET["klasse"];

		// filter out non-matching lines
		if (!isset($param) || substr_compare($fields[3], $param, 0, 2) == 0) {
			echo "  <tr>\n";
			if (isset($offline_data)) {
				echo '    <td>'.strftime('%H.%M', $fields[0])."</td>\n";
				echo '    <td>'.$fields[1]."</td>\n";
				echo '    <td>'.$fields[2]."</td>\n";
				echo '    <td>'.$fields[3]."</td>\n";
				echo '    <td>'.$fields[5]."</td>\n";
				echo '    <td>'.$fields[6]."</td>\n";
			} else {
				echo '    <td>'.strftime('%H.%M', $fields[0])."</td>\n";
				echo '    <td>'.$fields[2]."</td>\n";
				echo '    <td>'.$fields[3]."</td>\n";
				echo '    <td>'.$fields[4]."</td>\n";
				echo '    <td>'.$fields[6]."</td>\n";
			}
			echo "  </tr>\n";
		}
	}
	if (!$first) {
		echo "</tbody></table><br>\n";
	}
?>
</div>
