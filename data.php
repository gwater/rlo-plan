<div id="tables">
<?php
	$handle = fopen('data.txt', 'r');
	$first = true;						// first table doesn't have to close another one
	$last_day = NULL;
	$count = 0;
	while (($fields = fgetcsv($handle)) !== FALSE) {
		$count++;
		// FIXME: somehow setlocale() doesn't work
		$daynames = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag");
		$day = $daynames[strftime('%w', $fields[0])];

		$filter = $_GET["klasse"];
		/*
			a line is displayed IF:
			- we are in admin mode OR
			- there has been a change AND (there is no class filter OR the line matches the filter)
		*/
		if (isset($offline_view) || ($fields[7] != '-' && (!isset($filter) || substr_compare($fields[4], $filter, 0, 2) == 0))) {
			if ((isset($offline_view) && $first) || ($last_day != $day && !isset($offline_view))) {
				$last_day = $day;
				if ($first) {
					$first = false;			// all following new tables will have to close the previous
				} else {
					echo '</tbody></table><br>';	// close the previous table
				}
				if (!isset($offline_view)) {
					echo '<div style="margin-left: 20px; text-decoration: underline;">'.$day.'</div><br>';
				}
?>
<table style="text-align: left;" border="1" cellpadding="2" cellspacing="0">
<tbody>
  <tr>
<?php
				if (isset($offline_view)) {
?>
    <th>Wochentag</th>
    <th>Uhrzeit</th>
    <th>Lehrer</th>
    <th>Fach/Kurs</th>
    <th>Dauer</th>
    <th>Klasse</th>
    <th>Originalraum</th>
    <th>Vertretung</th>
    <th>&Auml;nderung</th>
    <th>Eintrag</th>
<?php
				} else {
?>
    <th>Uhrzeit</th>
    <th>Fach/Kurs</th>
    <th>Klasse</th>
    <th>Originalraum</th>
    <th>&Auml;nderung</th>
<?php
				}
				echo '  </tr>';
			}
			echo "  <tr>\n";
			if (isset($offline_view)) {
				echo_cell($day);
				echo_cell(strftime('%H.%M', $fields[0]));
				echo_cell($fields[1]);
				echo_cell($fields[2]);
				echo_cell($fields[3]);
				echo_cell($fields[4]);
				echo_cell($fields[5]);
				echo_cell($fields[6]);
				echo_cell($fields[7]);
				echo_cell('
<form action="lange.php" method="post">
  <input type="hidden" name="entry" value="'.$count.'"></input>
  <input type="submit" name="delete" value="L&ouml;schen"></input>
</form>');
			} else {
				echo_cell(strftime('%H.%M', $fields[0]));
				echo_cell($fields[2]);
				echo_cell($fields[4]); // $field[3] == teacher
				echo_cell($fields[5]);
				echo_cell($fields[7]); // $field[6] == substitute
			}
			echo "  </tr>\n";
		}
	} // while
	if (!$first && !isset($offline_view)) {
		echo "</tbody></table><br>\n";
	}

	function echo_cell($str) {
		echo '    <td>'.$str."</td>\n";
	}
?>
</div>
