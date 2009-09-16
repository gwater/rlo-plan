<?php
	session_start();

	if(!$_POST['password']) {
?>
<html>
<body>
<h1><Login</h1>
<?php
	if ($_GET['wrong'] == 'true') {
		echo '<font color="red">Falsches Passwort!</font>';
	}
?>
<form method="post" action="login.php">
Passwort: <input type="password" name="password" maxlength="16">
<input type="submit" name="submit" value="Login">
</form>
</body>
</html>
<?php
		exit;
	} else {
		$pass = sha1($_POST['password']);
		if($pass == '4f585e2a24173f61578e6ad9ff501eda9a78ba7f') {
			$_SESSION['id'] = 'lange';
			$host = $_SERVER['HTTP_HOST'];
			$uri  = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$page = 'lange.php';
			header("Location: http://$host$uri/$page");
			exit;
		} else {
			header('Location: '.$_SERVER['REQUEST_URI'].'?wrong=true');
			exit;
		}
	}
?>
