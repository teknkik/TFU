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
	return ($filename);
}
function errmsg($errorcode) { #for failures
	$errorarray = array("Info failure", "You tried to remove unallowed file or file does not exist", "You tried removing a file in different directory", "Wrong password", "Upload failed", "Disallowed file type or name", "Logged out");
	echo '<a href="?">Reload</a><br>';
	die($errorarray[$errorcode]."</body></html>");
}
session_start();
?>
<!DOCTYPE HTML>
<html>
<head>
<title>TFU Upload</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>
<body>
<?php
a:
$pass = $_POST['pass'];
$username = $_POST['username'];
#let's check data from info.php
if(!$info_addr) errmsg(0);
if(!$info_location) errmsg(0);
if(!$info_script_location) errmsg(0);
#logged in-------------------------------#
if($_SESSION['log'] == 1) {
#logging out
	if($_GET['exit']) {
		session_destroy();
		errmsg(6);
	 }
#changing password
if($_POST['npassword']) {
	$password_array = file($info_userinfo);
	$fed = fopen($info_userinfo, "w");
	foreach($password_array as $pa) {
		$p = explode("|", $pa);
	        if($_SESSION['user'] == $p[0]) {
			$upass = md5($_POST['npassword']);
			$udata = "$p[0]|$upass\n";
			fwrite($fed, $udata);
			echo "Password changed";
		}
		else    {
			fwrite($fed, $pa);
        	}
	}
#FOREACH ENDS
fclose($fed);
}
#removing file-----------------------------------------------#
	if($_GET['rmfn']) {
		$brmfn = $_GET['rmfn'];
		$rmfn = str_replace("../", "", $brmfn);
		if($rmfn !== $brmfn) errmsg(2);
		if($rmfn !== "." && $rmfn !== ".." && $rmfn !== "index.php" && file_exists($info_location."/".$rmfn)) {
		unlink($info_location."/".$rmfn);
		}
		else errmsg(1);
	 }
#changing name
	if($_GET['orgflnm'] && $_GET['nflnm']) {
	$borgflnm = $_GET['orgflnm'];
	$bnflnm = $_GET['nflnm'];
	$orgflnm = str_replace("../", "", $borgflnm);
	$nflnm = str_replace("../", "", $bnflnm);
	if($nflnm !== $bnflnm) errmsg(1);
	if($orgflnm !== $borgflnm) errmsg(1);
	$nflnm = flnmclean($nflnm);
	if($orgflnm !== "." && $orgflnm !== ".." && $orgflnm !== "index.php" && file_exists($info_location."/".$orgflnm) && $nflnm !== "." && $nflnm !== ".." && $nflnm !== "index.php" && !file_exists($info_location."/".$nflnm)) {
		$extension = end(explode(".", $nflnm)); #let's check for file name thingy
		if(!in_array($extension, $info_disallowedexts)) {
			rename($info_location."/".$orgflnm, $info_location."/".$nflnm);
			}
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
				$name = $_FILES['data']['name'];
				$name = flnmclean($name);
				if(move_uploaded_file($_FILES['data']['tmp_name'], $info_location."/".$name)) {
					echo "Upload successful";
						}
				else errmsg(4);
					}
		else errmsg(4);
		}
		else errmsg(4);
	}
#file listing
	 echo '<table>'."\n";
	 $files = scandir($info_location);
	 foreach ($files as $file) {
	 $file = htmlentities($file);
	 $file = utf8_encode($file);
	 if($file !== "." && $file !== ".." && $file !== "index.php") {
	 echo '<tr><td><a href="'.$info_addr.$file.'" target="_blank">'.$file.'</a></td><td><form action="'.$info_script_location.'" method="get"><input type="hidden" name="rmfn" value="'.$file.'"><input type="submit" value="remove"></form></td><td><form action="'.$info_script_location.'" method="get"><input type="hidden" name="orgflnm" value="'.$file.'"><input type="text" name="nflnm"><input type="submit" value="Change filename"></form></td></tr>'."\n";
		}
	 }
	 echo '</table>';
#----------------------------------------#
	echo '<form action="'.$info_script_location.'" enctype="multipart/form-data" method="post"><input type="file" name="data"><input type="submit" value="Upload new file"></form>';
	echo '<form action="'.$info_script_location.'" method="post"><input name="npassword" type="password"><input type="submit" value="Change password"></form>';
	echo '<form action="'.$info_script_location.'" method="get"><input name="exit" type="submit" value="Logout"></form>';
}
#when not logged in----------------------#
else {
#logging in
if($pass) {
$md5pass = md5($pass);
$files_array = file($info_userinfo);
foreach($files_array as $fi) {
	$f = explode("|", $fi);
 	if($f[1] == " "+$md5pass && $username == $f[0]) {
		echo "Welcome, ".$username."<br>";
		$_SESSION['log'] = 1;
		$_SESSION['user'] = $username;
		goto a;
		}
	}
}
if($pass) {
errmsg(3);
}
#public fiel list if allowed
if($info_filelist == true) {
	 echo '<table>'."\n";
	 $files = scandir($info_location);
	 foreach ($files as $file) {
	 $file = htmlentities($file);
	 $file = utf8_encode($file);
	 if($file !== "." && $file !== ".." && $file !== "index.php") {
		echo '<tr><td><a href="'.$info_addr.$file.'">'.$file.'</a></td></tr>'."\n";
		}
	 }
	 echo "</table><br>\n";
	}
if(!$pass) { echo '<form action="'.$info_script_location.'" method="post">
Username: <input type="text" name="username"><br>
Password: <input type="password" name="pass"><br><input type="submit" value="Login">
</form>'; }
}
#----------------------------------------#
?>

</body>
</html>
