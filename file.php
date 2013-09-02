<?php
function  flnmclean($filename) {
	$filename = str_replace("ä", "a", $filename);
	$filename = str_replace("Ä", "A", $filename);
	$filename = str_replace("ö", "o", $filename);
	$filename = str_replace("Ö", "O", $filename);
	$filename = str_replace("å", "a", $filename);
	$filename = str_replace("Å", "A", $filename);
	return ($filename);
}
a:
session_start();
$settings_location = "../data/info.php"; #should never be in web-directory
?>
<!DOCTYPE HTML>
<html>
<head>
<title>File Upload 1 alpha</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
</head>
<body>
<?php
include($settings_location);
$pass = $_POST['pass'];
$username = $_POST['username'];
#let's check data from info.php
if(!$info_addr) die("No script address set</body></html>");
if(!$info_location) die("No file save location set</body></html>");
if(!$info_script_location) die("No script address set</body></html>");
#logged in-------------------------------#
if($_SESSION['log'] == 1) {
#logging out
	if($_GET['exit']) {
		session_destroy();
		echo "Logged out";
		goto a;
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
		if($rmfn !== $brmfn) die("No modifying files in other directories</body></html>");
		if($rmfn !== "." && $rmfn !== ".." && $rmfn !== "index.php" && file_exists($info_location."/".$rmfn)) {
		unlink($info_location."/".$rmfn);
		}
		else echo "You may not remove index.php / file does not exist";
	 }
#changing name
	if($_GET['orgflnm'] && $_GET['nflnm']) {
	$borgflnm = $_GET['orgflnm'];
	$bnflnm = $_GET['nflnm'];
	$orgflnm = str_replace("../", "", $borgflnm);
	$nflnm = str_replace("../", "", $bnflnm);
	if($nflnm !== $bnflnm) die("No modifying files in other directories</body></html>");
	if($orgflnm !== $borgflnm) die("No modifying files in other directories</body></html>");
	$nflnm = flnmclean($nflnm);
	if($orgflnm !== "." && $orgflnm !== ".." && $orgflnm !== "index.php" && file_exists($info_location."/".$orgflnm) && $nflnm !== "." && $nflnm !== ".." && $nflnm !== "index.php" && !file_exists($info_location."/".$nflnm)) {
		$extension = end(explode(".", $nflnm)); #let's check for file name thingy
		if(!in_array($extension, $info_disallowedexts)) {
			rename($info_location."/".$orgflnm, $info_location."/".$nflnm);
			}
		else die("Disallowed file type</body></html>");
	echo "File name changed.";
		}
		else echo "File with this name is already there! / You tried to change files that are not allowed";
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
				else die("Upload failed</body></html>");
					}
		else die("Disallowed file type</body></html>");
		}
		else die("Error occured during upload</body></html>");
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
die("Wrong password</body></html>");
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
