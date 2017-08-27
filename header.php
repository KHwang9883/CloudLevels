<?php
/*
* CloudLevels, an easy way to share user created level files for video games.
* Copyright (C) 2016 Alexander Aquino
*
* This program is free software: you can redistribute it and/or modify it
* under the terms of the GNU General Public License as published by the Free
* Software Foundation, either version 3 of the License, or (at your option)
* any later version.
*
* This program is distributed in the hope that it will be useful, but WITHOUT
* ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
* FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
* more details.
*
* You should have received a copy of the GNU General Public License along with
* this program.  If not, see <http://www.gnu.org/licenses/>.
*/

//CloudLevels Header HTML + Initialization

//Go to installer
if(!file_exists('configuration.php')){
	header("Refresh:0; url=install.php");
	exit(0);
}

//Configuration variables
include 'configuration.php';

//Global functions go here

//Red error box
function errorbox($string){
	echo '<br><div class="container"><div class="card hoverable red"><div class="card-content white-text"><p>ERROR: ' . $string . '</p></div></div></div>';
}

//Green success box
function successbox($string){
	echo '<br><div class="container"><div class="card hoverable green"><div class="card-content white-text"><p>SUCCESS: ' . $string . '</p></div></div></div>';
}

//File preview box
function filebox($file){
		$is_featured='';
		if($file['featured']==1)
			$is_featured="<i class=\"tiny fa fa-star\" aria-hidden=\"true\"></i> ";
		echo "
					<div class=\"col s12 m6 l3\">
						<div class=\"card hoverable\">
							<a href=\"file.php?id=" . $file[0] . "\" class=\"card-content center black-text\">
								<p class=\"card-title\" style=\"white-space:nowrap; overflow: hidden; text-overflow: ellipsis; max-width:100%\">" . $is_featured . $file['name'] . "</p>
								<br><div class=\"circle\" style=\"background: url(/data/" . $file[0] . ".png); height: 0; padding-bottom: 70%; width: 70%; background-position: center; background-size: auto 100%; margin: 0 auto;\"></div><br>
								<p>By " . $file['username'] . "</p>
								<p>" . $file[3] . "</p>
								<p><i class=\"tiny fa fa-download\" aria-hidden=\"true\"></i> " . $file['downloads'] . " <i class=\"tiny fa fa-thumbs-up\" aria-hidden=\"true\"></i> " . $file['likes'] . "</p>
							</a>
						</div>
					</div>
";
}

//Pagination
function pagination($count, $per_page, $theme){
	/*
		count: Number of items total
		per_page: Number of items per page
		theme: Pass in the $theme variable
	*/
	if($count <= $per_page) return NULL;
	$page=1;
	if(!empty($_GET["page"]))
		$page=$_GET["page"];
	$new_url=$_SERVER['REQUEST_URI'];
	if(strpos($new_url, 'page=')!==false)
		$new_url=substr($new_url, 0, strpos($new_url,'page=')-1);
	if(substr($new_url, -4)=='.php')
		$new_url=$new_url.'?';
	else if(substr($new_url, -1)=='/')
		$new_url=$new_url.'index.php?';
	else
		$new_url=$new_url.'&';
	$page_count=ceil($count/$per_page);
	$offset=0;
	if($page>3)
		$offset=min($page-3, $page_count-5);
	echo '<div class="col s12 card-action"><ul class="pagination right"><li class="waves-effect"><a href="' . $new_url . 'page=1" class="' . $theme . '-text">First</a></li>';
	for($i = 1; $i <= min(5, $page_count); $i++){
		if($i+$offset==$page)
			echo '<li class="waves-effect waves-light ' . $theme . '"><a class="white-text">' . ($i+$offset) . '</a></li>';
		else
			echo '<li class="waves-effect"><a href="' . $new_url . 'page=' . ($i+$offset) . '" class="' . $theme . '-text">' . ($i+$offset) . '</a></li>';
	}
	echo '<li class="waves-effect"><a href="' . $new_url . 'page=' . $page_count . '" class="' . $theme . '-text">Last</a></li></ul></div>';
}

//SQL modifier for pagination
function page_sql_calc($per_page){
	$page=1;
	if(!empty($_GET["page"]))
		$page=$_GET["page"];
	return " LIMIT " . (($page-1)*$per_page) . "," . $per_page;
}

//Whether the user is logged in
$logged_in=false;

//User type: -1=Guest 0=Normal, 1=Banned, 2=Admin
$user_type=-1;

//Only use this when logged in
$user_name='';

//Start up session
session_start();

//Database
$db=null;
$db_error=false;
$file_get=NULL;
try {
	
	//Connect
	$db = new PDO($db_type . ':host=' . $db_hostname . ';dbname=' . $db_database . ';charset=utf8', $db_username, $db_password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
	
	//Load user data
	if(isset($_SESSION['uid'])){
		$logged_in=true;
		$stmt = $db->prepare("
				SELECT usergroup, username
				FROM cl_user
				WHERE id = ?");
		$stmt->execute(array($_SESSION['uid']));
		$result = $stmt->fetchAll();
		$user_type=$result[0]['usergroup'];
		$user_name=$result[0]['username'];
	}
	
	//File stuff
	if($_SERVER["PHP_SELF"]=='/file.php'&&!empty($_GET["id"])) {
		$stmt = $db->prepare("
				SELECT *
				FROM cl_file
				WHERE id = ?");
		$stmt->execute(array($_GET["id"]));
		$result = $stmt->fetchAll();
		if(!empty($result)){
			$file_get=$result[0];
			$page_title=$file_get['name'];
		}
		else
			$page_title="Invalid File";
	}
}

//Handle errors
catch(PDOException $ex){
	$db_error=true;
}

//HTML Template (Continues in footer.php):
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
		<title><?php echo $site_name ?> - <?php echo $page_title ?></title>
		<link rel="shortcut icon" href="favicon.ico">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/css/materialize.min.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	</head>
	<body>
		<nav class="<?php echo $theme ?> lighten-1 z-depth-1" style="overflow:hidden;">
			<div class="container">
				<ul class="right">
					<li><a href="browse.php">Browse</a></li>
<?php if(!$logged_in){ ?>
					<li><a href="login.php">Log In</a></li>
					<li><a href="register.php">Register</a></li>
<?php } else{?>
					<li><a href="upload.php">Upload</a></li>
					<li><a class="dropdown-button" data-activates="user-menu" data-beloworigin="true"><?php echo $user_name ?> <span class="fa fa-caret-down" aria-hidden="true"></span></a></li>
<?php } ?>
				</ul>
				<a href="index.php" style="font-size: 200%;"><?php echo $site_name ?></a>
			</div>
		</nav>
		<ul id="user-menu" class="dropdown-content">
<?php if($user_type==2){ ?>
			<li><a href="admin.php">Admin</a></li>
			<li><a href="members.php">Members</a></li>
			<li><a href="comments.php">Comments</a></li>
			<li class="divider"></li>
<?php } ?>
			<li><a href="browse.php?author=<?php echo $user_name ?>">Uploads</a></li>
			<li><a href="settings.php">Settings</a></li>
			<li class="divider"></li>
			<li><a href="logout.php">Log Out</a></li>
		</ul>

<?php
//DB Error
if($db_error){
	errorbox('Failed to connect to database.');
	include 'footer.php';
	exit(0);
}

//Banned
if($user_type==1){
	errorbox('You are banned.');
	include 'footer.php';
	exit(0);
}
?>
