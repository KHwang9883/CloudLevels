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

//CloudLevels Installer

//When there is input data
if(!empty($_POST["username"])){
	
	//Check if confirm password matches password field
	if($_POST["password"]!=$_POST["password_confirm"]){
		echo '<div class="card hoverable red"><div class="card-content white-text"><p>错误：您输入的密码不匹配。</p></div></div>';
		exit(0);
	}

	//Create configuration file:

	//Open file
	$configfile = fopen("configuration.php", "w") or die('<div class="card hoverable red"><div class="card-content white-text"><p>错误: 无法将文件 <strong>configuration.php</strong> 写入服务器。</p></div></div>');
	fwrite($configfile, "<?php\n");

	//Write database values
	fwrite($configfile, "\$db_type='" . addslashes($_POST["db_type"]) . "';\n");
	fwrite($configfile, "\$db_hostname='" . addslashes($_POST["db_hostname"]) . "';\n");
	fwrite($configfile, "\$db_username='" . addslashes($_POST["db_username"]) . "';\n");
	fwrite($configfile, "\$db_password='" . addslashes($_POST["db_password"]) . "';\n");
	fwrite($configfile, "\$db_database='" . addslashes($_POST["db_database"]) . "';\n");
	
	//Write default configuration stuff
	fwrite($configfile, "\$site_name='在此处填写网站名称';\n");
	fwrite($configfile, "\$site_desc='在此处填写网站说明';\n");
	fwrite($configfile, "\$game_url='#';\n");
	fwrite($configfile, "\$file_size_limit='1000000';\n");
	fwrite($configfile, "\$tags='每个\n标签\n为\n一行';\n");
	fwrite($configfile, "\$theme='light-blue';\n");
	fwrite($configfile, "\$reg_question='1 + 8 = ?';\n");
	fwrite($configfile, "\$reg_answer='9';\n");
	
	//Close file
	fwrite($configfile, "?>\n");
	fclose($configfile);

	//Create database:
	
	//Configuration variables
	include 'configuration.php';

	//Create tables
	try {
		
		//Connect to database
		$db = new PDO($db_type . ':host=' . $db_hostname . ';dbname=' . $db_database . ';charset=utf8', $db_username, $db_password, array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		
		//Begin
		$db->beginTransaction();
		
		//Users table
		$db->exec('CREATE TABLE cl_user(
		id INTEGER AUTO_INCREMENT,
		usergroup TINYINT DEFAULT 0,
		username TINYTEXT,
		password TINYTEXT,
		date TINYTEXT,
		ip TINYTEXT,
		uploads INTEGER DEFAULT 0,
		PRIMARY KEY (id)
		)');
		//usergroup: 0=Normal, 1=Banned, 2=Admin
		
		//Files table
		$db->exec('CREATE TABLE cl_file(
		id INTEGER AUTO_INCREMENT,
		name TINYTEXT,
		author INTEGER,
		date TINYTEXT,
		ip TINYTEXT,
		downloads INTEGER DEFAULT 0,
		likes INTEGER DEFAULT 0,
		featured TINYINT DEFAULT 0,
		description TEXT,
		PRIMARY KEY (id),
		FOREIGN KEY (author) REFERENCES cl_user (id)
		)');
		
		//Comments table
		$db->exec('CREATE TABLE cl_comment(
		id INTEGER AUTO_INCREMENT,
		author INTEGER,
		file INTEGER,
		date TINYTEXT,
		ip TINYTEXT,
		comment TEXT,
		PRIMARY KEY (id),
		FOREIGN KEY (author) REFERENCES cl_user (id)
		)');
		
		//Likes table
		$db->exec('CREATE TABLE cl_like(
		author INTEGER,
		file INTEGER,
		PRIMARY KEY (author, file),
		FOREIGN KEY (author) REFERENCES cl_user (id),
		FOREIGN KEY (file) REFERENCES cl_file (id)
		)');
		
		//Tags table
		$db->exec('CREATE TABLE cl_tag(
		file INTEGER,
		tag VARCHAR (255),
		PRIMARY KEY (file, tag),
		FOREIGN KEY (file) REFERENCES cl_file (id)
		)');
		
		//Create first admin user
		date_default_timezone_set('Asia/Shanghai');
		$stmt = $db->prepare("
			INSERT INTO cl_user(usergroup, username, password, date, ip)
			VALUES(2,?,?,?,?)");
		$stmt->execute(array($_POST["username"], crypt($_POST["password"]), date("Y-m-d"), $_SERVER['REMOTE_ADDR']));
		
		//End
		$db->commit();
		
	}
	
	//Handle errors
	catch(PDOException $ex){
		
		//$db->rollBack();
		echo '<div class="card hoverable red"><div class="card-content white-text"><p>错误：' . $ex->getMessage() . '</p></div></div>';
		exit(0);
		
	}

}

//Get available database types
$supported_db_types = PDO::getAvailableDrivers();

//HTML Template:
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0"/>
		<title>CloudLevels 安装器</title>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/css/materialize.min.css" type="text/css" rel="stylesheet" media="screen,projection"/>
	</head>
	<body>
		<div class="container">
			<?php
			if(empty($supported_db_types)){
			?>
			<div class="card hoverable red">
				<div class="card-content white-text">
					<p>错误：未检测到支持的数据库类型。</p>
				</div>
			</div>
			<?php
			}
			else if(!empty($_POST["username"])){
			?>
			<div class="card hoverable green">
				<div class="card-content white-text">
					<p>安装完成！请从服务器中删除 <strong>install.php</strong>。</p>
				</div>
			</div>
			<?php
			}
			?>
			<div class="row card hoverable">
				<span class="col s12 card-title light-blue white-text center" style="font-size: 200%;">安装</span>
				<form action="install.php" method="post" class="col s12 m10 l8 offset-m1 offset-l2">
					<div class="input-field col s12">
						<i class="fa fa-user prefix" aria-hidden="true"></i>
						<input id="username" name="username" type="text" class="validate" required>
						<label for="username">用户名</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="password" name="password" type="password" class="validate" required>
						<label for="password">密码</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="password-confirm" name="password_confirm" type="password" class="validate" required>
						<label for="password-confirm">确认密码</label>
					</div>
					<i class="fa fa-database small col s1" aria-hidden="true"></i> 
					<div class="input-field col s11">
						<select id="db_type" name="db_type">
							<?php 
								foreach($supported_db_types as $db_driver)
									echo '<option value="' . $db_driver . '">' . $db_driver . '</option>';
							?>

						</select>
						<label for="db_type">数据库类型</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-database prefix" aria-hidden="true"></i>
						<input id="db-hostname" name="db_hostname" type="text" class="validate" required>
						<label for="db-hostname">数据库主机名</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-database prefix" aria-hidden="true"></i>
						<input id="db-username" name="db_username" type="text" class="validate" required>
						<label for="db-username">数据库用户名</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-database prefix" aria-hidden="true"></i>
						<input id="db-password" name="db_password" type="password" class="validate" required>
						<label for="db-password">数据库密码</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-database prefix" aria-hidden="true"></i>
						<input id="db-database" name="db_database" type="text" class="validate" required>
						<label for="db-database">数据库名称</label>
					</div>
					<button class="btn waves-effect waves-light light-blue col s12" type="submit">安装</button>
				</form><div class="row"></div>
			</div>
		</div>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.4/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/0.97.6/js/materialize.min.js"></script>
		<script>$(document).ready(function() {$('select').material_select();$("form").submit(function(){$("button").attr("disabled", true);return true;});});</script>
	</body>
</html>
