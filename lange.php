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
	if (!empty($_POST)) {
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
			// TODO: calculate unix time stamp
			$line = '1034576400,"'.$teacher.'","'.$subject.'","'.$duration.'","';
			$line .= $class.'","'.$originalroom.'","'.$substitute.'","'.$change."\"\n";
			$handle = fopen('data.txt', 'a');
			fwrite($handle, $line);
			fclose($handle);
			echo '<font color="green">Der Eintrag wurde hinzugefügt.</font><br>';
		} else {
			echo '<font color="red">Bitte überprüfen Sie Ihre Angaben.</font><br>';
		}
		//file_put_contents('data.txt', stripslashes($_POST['updated_data']));
	}

	$offline_view = true;	// tell data.php that we want to see the offline view
	include('data.php');	// show the (possibly updated) table
?>
<div id="update_form">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
  <table border="1" cellpadding="2" cellspacing="0">
    <tr>
      <td>Wochentag</td>
      <td>Uhrzeit</td>
      <td>Lehrer</td>
      <td>Fach</td>
      <td>Dauer</td>
      <td>Klasse</td>
      <td>Originalraum</td>
      <td>Vertretung</td>
      <td>Änderung</td>
    </tr>
    <tr>
      <td>
        <select name="dayofweek">
          <option value="1">Montag</option>
          <option value="2">Dienstag</option>
          <option value="3">Mittwoch</option>
          <option value="4">Donnerstag</option>
          <option value="5">Freitag</option>
          <option value="6">Samstag</option>
          <option value="7">Sonntag</option>
        </select>
      </td>
      <td><input type="text" name="time" size="5"></input></td>
      <td><input type="text" name="teacher"></input></td>
      <td><input type="text" name="subject"></input></td>
      <td><input type="text" name="duration" size="3"></input></td>
      <td><input type="text" name="class"></input></td>
      <td><input type="text" name="originalroom" size="4"></input></td>
      <td><input type="text" name="substitute"></input></td>
      <td><input type="text" name="change"></input></td>
    </tr>
  </table><br>
  <input type="submit" value="Hinzufügen">
</form>
</div>
</body></html>
