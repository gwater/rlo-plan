<div id="tables">
<?php
	$data = file("data.txt");
	$first = true;			// first table doesn't have to close another one
	foreach($data as $line) {
		$line = trim($line);	// get rid of the line break chars
		if ($line == "Montag" || $line == "Dienstag" || $line == "Mittwoch" || $line == "Donnerstag" || $line == "Freitag" || $line == "Samstag" || $line == "Sonntag") {
			if ($first) {
				$first = false;			// all following new tables will have to close the previous
			} else {
				echo '</tbody></table><br>';	// close the previous table
			}
?>
<div style="margin-left: 40px;"><big style="text-decoration: underline;"><?=$line?></big></div><br>
<table style="text-align: left; width: 80%;" border="1" cellpadding="2" cellspacing="2">
<tbody>
  <tr>
    <td><span style="font-weight: bold;">Uhrzeit</span></td>
    <td><span style="font-weight: bold;">Klasse</span></td>
    <td><span style="font-weight: bold;">Raum&auml;nderung/Bemerkung</span></td>
  </tr>
<?php
		} else { // $line is not a day of the week
			if ($first) {
				echo 'Fehler: Die erste Zeile muss einen Wochentag enthalten.<br>';
				break; // don't display anything else but the error
			} else {
				$cells = explode("|", $line);
				$param = $_GET["klasse"];

				// filter out non-matching lines
				if (!isset($param) || substr_compare($cells[1], $param, 0, 2) == 0) {
					echo "  <tr>\n";
					foreach($cells as $cell) {
						echo "    <td>".$cell."</td>\n";
					}
					echo "  </tr>\n";
				}
			}
		}
	}
	if (!$first) {
		echo "</tbody></table><br>\n";
	}
?>
</div>
