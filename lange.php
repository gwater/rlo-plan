<?php
	session_start();
	if (!$_SESSION['id']) {
		$host = $_SERVER['HTTP_HOST'];
		$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$page = 'login.php';
		header("Location: http://$host$uri/$page");
		exit;
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html><head>
<meta content="text/html; charset=ISO-8859-1" http-equiv="content-type">
<title>Online-Vertretungsplan</title>
</head><body>
<div id="header">Willkommen beim Online-Vertretungsplan der Rosa-Luxemburg-Oberschule! <a href="logout.php">Abmelden</a><br><br></div>
<?php
	// save updated data received from the form below
	// FIXME: This method should convert all umlauts to html code. (like &uuml;)
	if (isset($_POST['add'])) {
		$dayofweek	= $_POST['dayofweek'];
		$time		= $_POST['time'];
		$teacher	= $_POST['teacher'];
		$subject	= $_POST['subject'];
		$duration	= $_POST['duration'];
		$class		= $_POST['class'];
		$originalroom	= $_POST['originalroom'];
		$substitute	= $_POST['substitute'];
		$change		= $_POST['change'];
		if ($dayofweek && $time && $teacher && $subject && $duration &&
		    $class && $originalroom && $substitute && $change) {

			// What follows is an ugly hack to get a timstamp for a lesson next week.
			$tm_array = strptime($time, '%H.%M');
			$now_array = getdate();
			$day = $now_array['mday'] + (($dayofweek - $now_array['wday'] + 7) % 7);
			$rawtime = mktime($tm_array['tm_hour'], $tm_array['tm_min'], 0, $now_array['mon'], $day, $now_array['year']);

			$newline = $rawtime.',"'.$teacher.'","'.$subject.'","'.$duration.'","';
			$newline .= $class.'","'.$originalroom.'","'.$substitute.'","'.$change."\"\n";
			$data = file('data.txt');
			$data[] = $newline;
			sort($data); // FIXME: sorts by $rawtime, (should be sorted by $dayofweek then by $teacher)
			$fh = fopen('data.txt', 'w');
			foreach ($data as $line) {
				fwrite($fh, $line);
			}
			fclose($fh);
			echo '<font color="green">Der Eintrag wurde hinzugefi&uuml;gt.</font><br><br>';
		} else {
			echo '<font color="red">Bitte &uuml;berpr&uuml;fen Sie Ihre Angaben.</font><br><br>';
			define('ERROR', 'TRUE');
		}
	} else if (isset($_POST['delete'])){
		$entry = $_POST['entry'];
		$data = file('data.txt');
		unset($data[$entry - 1]);
		$fh = fopen('data.txt', 'w');
		foreach ($data as $line) {
			fwrite($fh, $line);
		}
		fclose($fh);
		echo '<font color="green">Der '.$entry.'. Eintrag wurde gel&ouml;scht.</font><br><br>';
	}

	$offline_view = true;	// tell data.php that we want to see the offline view
	include('data.php');	// show the (possibly updated) table
	
	function selected($option) {
		if (defined('ERROR') && $_POST['dayofweek'] == $option) {
			return ' selected';
		}
	}
?>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post">
    <tr>
      <td>
        <select name="dayofweek">
          <option value="1"<?=selected(1)?>>Montag</option>
          <option value="2"<?=selected(2)?>>Dienstag</option>
          <option value="3"<?=selected(3)?>>Mittwoch</option>
          <option value="4"<?=selected(4)?>>Donnerstag</option>
          <option value="5"<?=selected(5)?>>Freitag</option>
          <option value="6"<?=selected(6)?>>Samstag</option>
          <option value="7"<?=selected(7)?>>Sonntag</option>
        </select>
      </td>
<?php
	if (defined('ERROR')) {
?>
      <td><input type="text" name="time" size="5" value="<?=$_POST['time']?>"></input></td>
      <td><input type="text" name="teacher" value="<?=$_POST['teacher']?>"></input></td>
      <td><input type="text" name="subject" value="<?=$_POST['subject']?>"></input></td>
      <td><input type="text" name="duration" size="3" value="<?=$_POST['duration']?>"></input></td>
      <td><input type="text" name="class" size="3" value="<?=$_POST['class']?>"></input></td>
      <td><input type="text" name="originalroom" size="4" value="<?=$_POST['originalroom']?>"></input></td>
      <td><input type="text" name="substitute" value="<?=$_POST['substitute']?>"></input></td>
      <td><input type="text" name="change" value="<?=$_POST['change']?>"></input></td>
<?php
	} else {
?>
      <td><input type="text" name="time" size="5"></input></td>
      <td><input type="text" name="teacher"></input></td>
      <td><input type="text" name="subject"></input></td>
      <td><input type="text" name="duration" size="3"></input></td>
      <td><input type="text" name="class" size="3"></input></td>
      <td><input type="text" name="originalroom" size="4"></input></td>
      <td><input type="text" name="substitute"></input></td>
      <td><input type="text" name="change"></input></td>
<?php
	}
?>
      <td><input type="submit" name="add" value="Hinzuf&uuml;gen"></input></td>
    </tr>
    </form>
  </table>
</body></html>
