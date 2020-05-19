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

//CloudLevels User Settings

//Header + Vars:
$page_title='设置';
include 'header.php';

//Members only!
if($user_type==-1||$user_type==1){
	errorbox('您无权查看此页面。');
	include 'footer.php';
	exit(0);
}

//When there is input data
if(!empty($_POST["password_old"])){
	
	try{
		
		//Check password
		if($_POST["password_confirm"]!=$_POST["password_new"]){
			errorbox('新密码和确认密码不匹配。');
		}
		
		//Correct password
		else{
		
			//SQL Stuff
			$stmt = $db->prepare("
				SELECT username, password
				FROM cl_user
				WHERE username = ?");
			$stmt->execute(array($user_name));
			$result = $stmt->fetchAll();
			$passhash=$result[0]['password'];
			
			//Compare password hash
			if(crypt($_POST["password_old"], $passhash)==$passhash){
				
				//SQL Stuff
				$stmt = $db->prepare("
					UPDATE cl_user
					SET password = ?
					WHERE username = ?");
				$stmt->execute(array(crypt($_POST["password_new"]), $user_name));
				
				successbox('密码已修改。');
			}
			else{
				errorbox('密码错误');
			}
		}
	}
	
	//Handle errors
	catch(PDOException $ex){
		errorbox('无法修改密码。');
	}
	
}
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">修改密码</span>
				<form action="settings.php" method="post" class="col s12 m10 l8 offset-m1 offset-l2">
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="password-old" name="password_old" type="password" class="validate" required>
						<label for="password-old">旧密码</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="password-new" name="password_new" type="password" class="validate" required>
						<label for="password-new">新密码</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="password-confirm" name="password_confirm" type="password" class="validate" required>
						<label for="password-confirm">确认新密码</label>
					</div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s12" type="submit">修改密码</button>
				</form><div class="row"></div>
			</div>
		</div>
		
<?php
//Footer
include 'footer.php';
?>
