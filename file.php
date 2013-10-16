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
	$errorarray = array("Info failure", "You tried to remove unallowed file or file does not exist", "You tried removing a file in different directory", "Wrong password", "Upload failed", "Disallowed file type or name", "Logged out", "No password given", "Username already exists");
	die('<a href="?">Reload</a><br>'.$errorarray[$errorcode].'</body></html>');
}
session_start();
?>
<!DOCTYPE HTML>
<html>
<head>
<title>TFU - Tek File Upload</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>
<body>
<?php
a:
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
if($_GET['exit']) {
		session_destroy();
		errmsg(6);
	 }
#adding a user
if($_POST['newum'] && $_POST['newup'] && in_array($_SESSION['user'], $info_admins)) {
	$new_username = flnmclean($_POST['newum']);
	$new_userpassword = md5($_POST['newup']);
	foreach(file($info_userinfo) as $nua) {
			$nua = explode("|", $nua);
			if($nua[0] == $new_username) errmsg(8);
		}
	$fed = fopen($info_userinfo, "a");
	$udata = "$new_username|$new_userpassword\n";
	fwrite($fed, $udata);
	fclose($fed);
	mkdir($info_location."$new_username", 0777);
	touch($info_location."$new_username"."/index.html");
	echo "New user added, username:<b> $new_username </b>";
}
#removing a user
if($_POST['rmuser'] && in_array($_SESSION['user'], $info_admins)) {
	$rmuser = $_POST['rmuser'];
	$users = file($info_userinfo);
	$fd = fopen($info_userinfo, "w");
	foreach($users as $user) {
		$ruser = explode("|", $user);
		if($ruser[0] == $rmuser)	echo "User removed";
		else 				fwrite($fd, $user);
	}
fclose($fd);
}
#changing the password
if($_POST['npassword']) {
	$npassword = md5($_POST['npassword']);
	$password_array = file($info_userinfo);
	$fed = fopen($info_userinfo, "w");
	foreach($password_array as $pa) {
		$pa = explode("|", $pa);
        	if($_SESSION['user'] == $pa[0]) {
			fwrite($fed, "$pa[0]|$npassword\n");
			echo "Password changed";
		}
		else	fwrite($fed, $pa);
	}
fclose($fed);
}
#removing a file
if($_GET['rmfn']) {
		$crmfn = $_GET['rmfn'];
		$rmfn = str_replace("../", "", $crmfn);
		if($rmfn !== $crmfn) errmsg(2);
			if($rmfn !== "." && $rmfn !== ".." && $rmfn !== "index.php" && $rmfn !== "index.html" && file_exists($info_userlocation."/".$rmfn))	unlink($info_userlocation."/".$rmfn);
			else errmsg(1);
	 }
#changing filename
if($_GET['orgflnm'] && $_GET['nflnm']) {
	$corgflnm = $_GET['orgflnm'];
	$cnflnm = $_GET['nflnm'];
	$orgflnm = str_replace("../", "", $corgflnm);
	$nflnm = str_replace("../", "", $cnflnm);
	if($nflnm !== $cnflnm) errmsg(1);
	if($orgflnm !== $corgflnm) errmsg(1);
	$nflnm = flnmclean($nflnm);
	if($orgflnm !== "." && $orgflnm !== ".." && $orgflnm !== "index.php" && $orgflnm !== "index.html" && file_exists($info_userlocation.$orgflnm) && $nflnm !== "." && $nflnm !== ".." && $nflnm !== "index.php" && $nflnm !== "index.html" && !file_exists($info_userlocation.$nflnm)) {
		$extension = end(explode(".", $nflnm));
		if(!in_array($extension, $info_disallowedexts)) rename($info_userlocation.$orgflnm, $info_userlocation.$nflnm);
		else errmsg(5);
		echo "Filename changed.";
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
#admin panel
if($_GET['admpanel'] && in_array($_SESSION['user'], $info_admins)) {
	echo '<form action"'.$info_script_location.'?admpanel=1" method="post">Username: <input name="newum">Password: <input type="password" name="newup"><input type="submit" value="Create new user"></form>';
	echo '<br><form action="'.$info_script_location.'?admpanel=1" method="post">';
	foreach(file($info_userinfo) as $user) {
		$user = explode("|", $user);
		echo '<input type="radio" name="rmuser" value="'.$user[0].'">'.$user[0].'<br>';
	}
	echo '<input type="submit" value="Remove user"></form><br><a href="'.$info_script_location.'">Back</a>';
}
#file listing
else {
	 echo '<table>'."\n";
	 foreach (scandir($info_userlocation) as $file) {
		 $file = utf8_encode(htmlentities($file));
		 if($file !== "." && $file !== ".." && $file !== "index.php" && $file !== "index.html" && !is_dir($info_location.$file."/")) {
			if(!$info_disableuserfolders) $filelink = $info_addr.$_SESSION['user']."/".$file; #a quick way to enable using own folders for users
			else $filelink = $info_addr.$file;
			echo '<tr><td><a href="'.$filelink.'" target="_blank">'.$file.'</a></td><td><form action="'.$info_script_location.'" method="get"><input type="hidden" name="rmfn" value="'.$file.'"><input type="submit" value="remove"></form></td><td><form action="'.$info_script_location.'" method="get"><input type="hidden" name="orgflnm" value="'.$file.'"><input type="text" name="nflnm"><input type="submit" value="Change filename"></form></td></tr>'."\n";
		}
	 }
	echo '</table><form action="'.$info_script_location.'" enctype="multipart/form-data" method="post"><input type="file" name="data"><input type="submit" value="Upload new file"></form>';
	echo '<form action="'.$info_script_location.'" method="post"><input name="npassword" type="password"><input type="submit" value="Change password"></form>';
	if(in_array($_SESSION['user'], $info_admins)) echo '<a href="'.$info_script_location.'?admpanel=1">Admin panel</a>';
		echo '<form action="'.$info_script_location.'" method="get"><input name="exit" type="submit" value="Logout"></form>';
	}
}
#not logged in
else {
#logging in
if($_POST['pass']) {
$pass = md5($_POST['pass']);
$username = $_POST['username'];
foreach(file($info_userinfo) as $fi) {
	$fi = explode("|", $fi);
 	if($fi[1] == " "+$pass && $username == $fi[0]) {
		echo "Welcome, ".$username."<br>";
		$_SESSION['user'] = $username;
		goto a;
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
echo '<form action="'.$info_script_location.'" method="post">
Username: <input type="text" name="username"><br>
Password: <input type="password" name="pass"><br><input type="submit" value="Login">
</form>';
}
?>
</body>
</html>
