<!--
    TODO: move this code into html.inc.php and
          replace every "require('login.inc.php'); exit;"
          with something like "exit(new ovp_login_page()->get_html());"
-->
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<link rel="stylesheet" href="style.css" type="text/css">
<title>RLO Onlinevertretungplans - Login</title>
</head>
<body>
<h1>Login</h1>
<p>Um diese Seite öffnen zu können, benötigen Sie ein entsprechend autorisiertes Benutzerkonto.</p>
<form action="account.php?action=login<?php
    if (isset($_GET['continue'])) {
        echo '&continue='.$_GET['continue'];
    }
?>" method="POST">
<table>
  <tr>
    <td>Name:</td>
    <td><input type="text" name="name"></td>
  </tr>
  <tr>
    <td>Passwort:</td>
    <td><input type="password" name="pwd"</td>
  </tr>
  <tr>
    <td></td>
    <td><input type="submit" value="Login"></td>
  </tr>
</table>
</form>
</body>
</html>
