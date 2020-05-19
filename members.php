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

//CloudLevels Member Management

//Header + Vars:
$page_title='用户管理';
include 'header.php';

//Admins only!
if($user_type!=2){
	errorbox('您无权查看此页面。');
	include 'footer.php';
	exit(0);
}

$result=null;
$num_rows=0;
try{
	
	//Modify members if specified
	if(!empty($_GET["user"])){
		
		if($_GET["user"]==$user_name){
			errorbox('您无法取消自己的管理员。');
		}
		
		else{
			
			//SQL Stuff
			$stmt = $db->prepare("
				UPDATE cl_user
				SET usergroup = ?
				WHERE username = ?");
			$stmt->execute(array($_GET["update"], $_GET["user"]));
			
			if($_GET["update"]==0) successbox($_GET["user"] . " 现在是普通用户。");
			else if($_GET["update"]==1) successbox($_GET["user"] . " 已经被封禁。");
			else if($_GET["update"]==2) successbox($_GET["user"] . " 现在是管理员。");
			
		}
		
	}
	
	//Get Member data
	$order='id';
	$where='WHERE ';
	$args=array();
	
	//Username
	if(!empty($_GET["username"])){
		$where.=' AND username = ?';
		array_push($args, $_GET["username"]);
	}
	
	//Usergroup
	if(!empty($_GET["group"])){
		if($_GET["group"]=="member"||$_GET["group"]=="staff"||$_GET["group"]=="banned")
			$where.=' AND usergroup = ?';
		if($_GET["group"]=="member") array_push($args, 0);
		else if($_GET["group"]=="staff") array_push($args, 2);
		else if($_GET["group"]=="banned") array_push($args, 1);
	}
	
	//IP address
	if(!empty($_GET["ip"])){
		$where.=' AND ip LIKE ?';
		array_push($args, str_replace("*", "%", $_GET["ip"]));
	}
	
	//Order by
	if(!empty($_GET["sort"])&&$_GET["sort"]=='uploaded'){
		$order='uploads';
	}
	
	//No query case
	if($where=='WHERE '){
		$where='';
	}
	
	//Remove first AND from where string
	$where=preg_replace('/AND/', '', $where, 1);
	
	$stmt = $db->prepare("
		SELECT SQL_CALC_FOUND_ROWS *
		FROM cl_user
		" . $where . "
		ORDER BY " . $order .  " DESC
		" . page_sql_calc(25));
	$stmt->execute($args);

	$result = $stmt->fetchAll();
	
	$num_rows = $db->query('SELECT FOUND_ROWS()')->fetchColumn();
	
}

//Handle errors
catch(PDOException $ex){
	errorbox('无法加载用户数据。');
}
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">筛选器</span>
				<form action="members.php" method="get">
					<div class="input-field col s6">
						<i class="fa fa-user prefix" aria-hidden="true"></i>
						<input id="username" name="username" type="text" value="<?php if(!empty($_GET["username"])){echo $_GET["username"];} ?>" class="validate">
						<label for="username">用户名</label>
					</div>
					<div class="input-field col s6">
						<select id="group" name="group" required>
							<option value="all">所有用户</option>
							<option value="member"<?php if(!empty($_GET["group"])&&$_GET["group"]=='member') echo ' selected'; ?>>普通用户</option>
							<option value="staff"<?php if(!empty($_GET["group"])&&$_GET["group"]=='staff') echo ' selected'; ?>>管理员</option>
							<option value="banned"<?php if(!empty($_GET["group"])&&$_GET["group"]=='banned') echo ' selected'; ?>>被封禁用户</option>
						</select>
						<label for="group">用户组</label>
					</div>
					<div class="input-field col s6">
						<i class="fa fa-map-marker prefix" aria-hidden="true"></i>
						<input id="ip" name="ip" type="text" value="<?php if(!empty($_GET["ip"])){echo $_GET["ip"];} ?>" class="validate">
						<label for="ip">IP 地址</label>
					</div>
					<div class="input-field col s6">
						<select id="sort" name="sort" required>
							<option value="recent">最新</option>
							<option value="uploaded"<?php if(!empty($_GET["sort"])&&$_GET["sort"]=='uploaded') echo ' selected'; ?>>最多上传</option>
						</select>
						<label for="sort">排序</label>
					</div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s10 l8 offset-s1 offset-l2" type="submit">筛选</button>
				</form><div class="row"></div>
			</div>
		</div>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">用户管理</span>
				<div class="row">
					<p class="center col s12"><strong><span class="blue-text">普通用户</span> | <span class="green-text">管理员</span> | <span class="red-text">被封禁用户</span></strong></p>
					<table class="col s10 offset-s1 centered striped">
						<thead>
							<tr>
								<th>用户名</th>
								<th>上传数</th>
								<th>注册时间</th>
								<th>IP 地址</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody><?php
foreach($result as $user){
	$append="<a href=\"members.php?update=1&user=" . $user['username'] . "\" class=\"red-text\">[封禁]</a> <a href=\"members.php?update=2&user=" . $user['username'] . "\" class=\"green-text\">[设置为管理员]</a>";
	if($user['usergroup']==1){
		$append="<a href=\"members.php?update=0&user=" . $user['username'] . "\" class=\"blue-text\">[解封]</a>";
	}
	else if($user['usergroup']==2){
		$append="<a href=\"members.php?update=0&user=" . $user['username'] . "\" class=\"blue-text\">[取消管理员]</a>";
	}
	echo "
							<tr>
								<td><strong>" . memberlink($user['username'], $user['usergroup'], false) . "</strong></td>
								<td>" . $user['uploads'] . "</td>
								<td>" . $user['date'] . "</td>
								<td>" . $user['ip'] . "</td>
								<td>" . $append . "</td>
							</tr>";
	
}
?>

						</tbody>
					</table>
				</div>
<?php
//Pages
pagination($num_rows, 25, $theme);
?>
			</div>
		</div>
		
<?php
//Footer
include 'footer.php';
?>
