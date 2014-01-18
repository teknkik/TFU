 <?php
$settings_location = "info.php"; #should never be in web-directory
include($settings_location);
function  flnmclean($filename) {
	$filename = str_replace("ä", "a", $filename);
	$filename = str_replace("Ä", "A", $filename);
	$filename = str_replace("ö", "o", $filename);
	$filename = str_replace("Ö", "O", $filename);
	$filename = str_replace("å", "a", $filename);
	$filename = str_replace("Å", "A", $filename);
	$filename = preg_replace('/[^A-Za-z0-9 _\.\-\+\&]/','',$filename);
	return ($filename);
}
function errmsg($errorcode) {
	$errorarray = array("Info failure", "You tried to remove unallowed file or file does not exist", "You tried removing a file in different directory", "Wrong password", "Upload failed", "Disallowed file type or name", "Logged out", "No password given", "Username already exists", "You tried to edit an unallowed file", "You tried to remove your own account");
	die('<div class="alert alert-danger die">'.$errorarray[$errorcode].', redirecting... <a href="?">reload page</a><script type="text/javascript">window.setTimeout(function(){ document.location.reload(true); }, 6000);</script></div></body></html>');
}
session_start();
?>
<!DOCTYPE HTML>
<html>
<head>
<title>TFU - Tek File Upload</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/file.css" rel="stylesheet">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>
<body>
<nav class="navbar navbar-inverse navbar-fixed-top"><div class="navbar-header"><a class="navbar-brand" href="?">TFU</a><ul class="nav navbar-nav">
<?php
if(in_array($_SESSION['user'], $info_admins)) {
if($_GET['p'] == "adm") $admactv = ' class="active"';
echo '<li'.$admactv.'><a href="?p=adm">Admin panel</a></li>';
}
if($_SESSION['user']){
if($_GET['p'] == "stngs") $stngs = ' class="active"';
if($_GET['p'] == "abt") $abtactv = ' class="active"';
echo '<li'.$stngs.'><a href="?p=stngs">Settings</a></li>';
echo '<li'.$abtactv.'><a href="?p=abt">About</a></li>';
echo '<form action="?" method="post" class="navbar-form navbar-left"><input name="exit" class="btn btn-default"  type="submit" value="Logout"></form>';
}
?>
</ul></div></nav>
<?php
#checking data from info.php
if(!$info_addr) errmsg(0);
if(!$info_location) errmsg(0);
if(!$info_script_location) errmsg(0);
#logged in
if($_SESSION['user']) {
#userfolders
if(!$info_disableuserfolders) $info_userlocation = $info_location.$_SESSION['user']."/";
else $info_userlocation = $info_location;
#logging out
if($_POST['exit']) {
		session_destroy();
		errmsg(6);
	 }
#adding a user
if($_POST['newum'] && $_POST['newup'] && in_array($_SESSION['user'], $info_admins)) {
	$new_username = flnmclean($_POST['newum']);
	$new_userpassword = md5($_POST['newup'].$_POST['newum']);
	foreach(file($info_userinfo) as $nua) {
			$nua = explode("|", $nua);
			if($nua[0] == $new_username) errmsg(8);
		}
	$fed = fopen($info_userinfo, "a");
	$udata = "$new_username|$new_userpassword\n";
	fwrite($fed, $udata);
	fclose($fed);
	if(!info_disableuserfolders) {
	mkdir($info_location."$new_username", 0777);
	touch($info_location."$new_username"."/index.html");
	}
	echo '<div class="alert alert-success">New user added, username:<b> '.$new_username.' </b></div>';
}
#removing a user
if($_POST['rmuser'] && in_array($_SESSION['user'], $info_admins)) {
	$rmuser = $_POST['rmuser'];
	if($rmuser == $_SESSION['user']) errmsg(10);
	$users = file($info_userinfo);
	$fd = fopen($info_userinfo, "w");
	foreach($users as $user) {
		$ruser = explode("|", $user);
		if($ruser[0] == $rmuser)	echo '<div class="alert alert-success">User <b>'.$rmuser.'</b> succesfully removed</div>';
		else 				fwrite($fd, $user);
	}
fclose($fd);
}
#changing the password
if($_POST['npassword']) {
	$opassword = md5($_POST['opassword'].$_SESSION['user']);
	$npassword = md5($_POST['npassword'].$_SESSION['user']);
	$password_array = file($info_userinfo);
	if($_POST['npassword2'] == $_POST['npassword']) {
		$fed = fopen($info_userinfo, "w");
		foreach($password_array as $p) {
			$pa = explode("|", $p);
	        	if($_SESSION['user'] == $pa[0]) {
				if($opassword."\n" == $pa[1]) {
					fwrite($fed, "$pa[0]|$npassword\n");
					echo '<div class="alert alert-success">Password changed</div>';
				}
				else	 {
					echo '<div class="alert alert-danger">Incorrect old password</div>';
					fwrite($fed, $p);
				}
			}
			else	fwrite($fed, $p);
		}
	 fclose($fed);
	}
else echo '<div class="alert alert-danger">The passwords do not match</div>';
}
#removing a file
if($_POST['rmfn']) {
		$crmfn = $_POST['rmfn'];
		$rmfn = str_replace("../", "", $crmfn);
		if($rmfn !== $crmfn) errmsg(2);
			if($rmfn !== "." && $rmfn !== ".." && $rmfn !== "index.php" && $rmfn !== "index.html" && file_exists($info_userlocation."/".$rmfn))	unlink($info_userlocation."/".$rmfn);
			else errmsg(1);
		echo '<div class="alert alert-success">File succesfully removed</div>';
	 }
#editing a file
if($_POST['fledtnm'] && $_POST['fcont']) {
		$ceditflnm = $_POST['fledtnm'];
                $editflnm = str_replace("../", "", $ceditflnm);
		if($ceditflnm !== $editflnm) errmsg(2);
			if($editflnm !== "." && $editflnm !== ".." && $editflnm !== "index.php" && $editflnm !== "index.html" && file_exists($info_userlocation."/".$editflnm)) file_put_contents($info_userlocation."/".$editflnm, $_POST['fcont']);
			else errmsg(9);
		echo '<div class="alert alert-success">Changes saved</div>';
}
#changing filename
if($_POST['orgflnm'] && $_POST['nflnm']) {
	$corgflnm = $_POST['orgflnm'];
	$cnflnm = $_POST['nflnm'];
	$orgflnm = str_replace("../", "", $corgflnm);
	$nflnm = str_replace("../", "", $cnflnm);
	if($nflnm !== $cnflnm) errmsg(1);
	if($orgflnm !== $corgflnm) errmsg(1);
	$nflnm = flnmclean($nflnm);
	if($orgflnm !== "." && $orgflnm !== ".." && $orgflnm !== "index.php" && $orgflnm !== "index.html" && file_exists($info_userlocation.$orgflnm) && $nflnm !== "." && $nflnm !== ".." && $nflnm !== "index.php" && $nflnm !== "index.html" && !file_exists($info_userlocation.$nflnm)) {
		$extension = end(explode(".", $nflnm));
		if(!in_array($extension, $info_disallowedexts)) rename($info_userlocation.$orgflnm, $info_userlocation.$nflnm);
		else errmsg(5);
		echo "Filename changed";
		}
	else errmsg(5);
	}
#uploading
if($_FILES) {
	if(!$_FILES['data']['error']) {
		$extension = end(explode(".", $_FILES['data']['name']));
		if(!in_array($extension, $info_disallowedexts)) {
			$name = flnmclean($_FILES['data']['name']);
			if(move_uploaded_file($_FILES['data']['tmp_name'], $info_userlocation.$name))	echo "Upload successful";
			else errmsg(4);
			}
		else errmsg(4);
		}
	else errmsg(4);
	}
#!!!!!!!!!!!!!!PAGES!!START!!HERE!!!!!!!!!!!!!!!!!#
#admin panel
if($_GET['p'] == "adm" && in_array($_SESSION['user'], $info_admins)) {
	echo '<div class="row"><div class="col-xs-6 col-sm-2"><h4>Add a new user</h4><form action"?p=adm" method="post"><input name="newum" class="form-control" placeholder="New username"><input type="password" class="form-control" name="newup" placeholder="Password"><input type="submit" class="btn btn-default" value="Create new user"></form>';
	echo '<br><form action="?p=adm" method="post"></div><div class="col-xs-6 col-sm-2"><h4>Remove a user</h4><div class="col-lg-6">';
	foreach(file($info_userinfo) as $user) {
		$user = explode("|", $user);
		echo '<div class="input-group"><span class="input-group-addon"><input type="radio" name="rmuser" value="'.$user[0].'"></span><span class="form-control">'.$user[0].'</span></div>';
	}
	echo '<input class="btn btn-danger" type="submit" value="Remove user"></form></div></div>';
}
#printing out the edit page
elseif($_POST['edit']) {
                $ceditflnm = $_POST['edit'];
                $editflnm = str_replace("../", "", $ceditflnm);
                if($ceditflnm !== $editflnm) errmsg(2);
                        if($editflnm !== "." && $editflnm !== ".." && $editflnm !== "index.php" && $editflnm !== "index.html" && file_exists($info_userlocation."/".$editflnm)) echo '<form action="?" method="post" ><textarea name="fcont" rows="25" cols="100">'.file_get_contents($info_userlocation."/".$editflnm).'</textarea><input type="hidden" name="fledtnm" value="'.$editflnm.'"><br><input type="submit" value="Save file"></form><br>';
                        else errmsg(9);
}
#about-page
elseif($_GET['p'] == "abt") {
echo $info_aboutpage;
}
#so called settings
elseif($_GET['p'] == "stngs") {
echo '<div class="formbox"><h4>Changing your password</h4><form action="?p=stngs" method="post"><input name="opassword" class="form-control" placeholder="Old password" type="password"><input name="npassword" class="form-control" placeholder="New password" type="password"><input name="npassword2" class="form-control" placeholder="Retype new password" type="password"><input type="submit" class="btn btn-default" value="Change password"></form></div>';
}
#file listing
else {
	 echo '<div class="col-xs-8"><table>'."\n";
	 foreach (scandir($info_userlocation) as $file) {
		 $file = utf8_encode(htmlentities($file));
		 if($file !== "." && $file !== ".." && $file !== "index.php" && $file !== "index.html" && !is_dir($info_location.$file."/")) {
			if(!$info_disableuserfolders) $filelink = $info_addr.$_SESSION['user']."/".$file; #a quick way to enable using own folders for users
			else $filelink = $info_addr.$file;
			echo '<tr><td><a class="" href="'.$filelink.'" target="_blank">'.$file.'</a></td><td><form action="?" method="post"><input type="hidden" name="rmfn" value="'.$file.'"><input type="submit" class="btn btn-danger" value="Remove"></form></td><td><form action="?" method="post"><input type="hidden" name="edit" value="'.$file.'"><input type="submit" class="btn btn-default" value="Edit"></form></td><td><form action="?" method="post"><input type="hidden" name="orgflnm" value="'.$file.'"><input type="text" class="" name="nflnm"><input type="submit" class="btn btn-default" value="Change filename"></form></td></tr>'."\n";
		}
	 }
	echo '</table></div><div class="col-md-4"><form action="?" enctype="multipart/form-data" method="post"><input  type="file" class="btn btn-default" name="data"><input type="submit" class="btn btn-default" value="Upload new file"></form></div>';
	}
}
#not logged in
else {
#logging in
if($_POST['pass']) {
$pass = md5($_POST['pass'].$_POST['username']);
$username = $_POST['username'];
foreach(file($info_userinfo) as $fi) {
	$fi = explode("|", $fi);
 	if($fi[1] == " "+$pass && $username == $fi[0]) {
		$_SESSION['user'] = $username;
		die('<div class="alert alert-success die">Login succesful, redirecting... <a href="?">reload page</a></div><script type="text/javascript">window.setTimeout(function(){ document.location.reload(true); }, 1500);</script></body></html>');
		}
	}
}
if($pass) errmsg(3);
#public file list
if($info_filelist) {
	 echo '<table>'."\n";
	 foreach (scandir($info_location) as $file) {
		 $file = utf8_encode(htmlentities($file));
		 if($file !== "." && $file !== ".." && $file !== "index.php" && $file !== "index.html" && !is_dir($info_location.$file."/"))	echo '<tr><td><a href="'.$info_addr.$file.'">'.$file.'</a></td></tr>'."\n";
	}
echo "</table><br>\n";
}
echo '<div class="login"><h3>Tek File Upload</h3><form action="?" method="post">
<input type="text" class="form-control" placeholder="Username" name="username" required autofocus>
<input type="password" class="form-control" placeholder="Password"  name="pass" required>
<input type="submit" class="btn btn-lg btn-primary btn-block" value="Sign in">
</form>';
}
?>
</body>
</html>
