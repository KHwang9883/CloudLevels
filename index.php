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

//CloudLevels Front Page

//Header + Vars:
$page_title='';
include 'header.php';

//When there is comment data
if($user_type!=-1&&!empty($_POST["comment"])){
	
	try{
		date_default_timezone_set('Asia/Shanghai');
		$stmt = $db->prepare("
			INSERT INTO cl_comment(author, file, date, ip, comment)
			VALUES(?,?,?,?,?)");
		$stmt->execute(array($_SESSION['uid'], 0, date("Y-m-d"), $_SERVER['REMOTE_ADDR'], nl2br(htmlspecialchars($_POST["comment"]))));
	}
	
	//Handle errors
	catch(PDOException $ex){
		errorbox('无法发表评论。');
		include 'footer.php';
		exit(0);
	}
	
	successbox('评论已发布，请稍候。');
	header("Location:index.php");
	include 'footer.php';
	exit(0);
	
}

//Delete comments
if($user_type==2&&!empty($_GET["deletecomment"])){
	try{
			$stmt = $db->prepare("
				DELETE FROM cl_comment
				WHERE id = ?");
			$stmt->execute(array($_GET["deletecomment"]));
	}
	//Handle errors
	catch(PDOException $ex){
		errorbox('无法删除评论。');
		include 'footer.php';
		exit(0);
	}
	successbox('评论已删除，请稍候。');
	header("Location:index.php");
	include 'footer.php';
	exit(0);
}

$result1=null;
$result2=null;
$result3=null;
$comments=null;
$num_rows=0;
try{
	
	//Featured
	$stmt = $db->prepare("
		SELECT *
		FROM cl_file JOIN cl_user ON cl_file.author=cl_user.id
		WHERE featured=1
		ORDER BY cl_file.id DESC
		LIMIT 4");
	$stmt->execute();
	
	$result1 = $stmt->fetchAll();
	
	//Popular
	$stmt = $db->prepare("
		SELECT *
		FROM cl_file JOIN cl_user ON cl_file.author=cl_user.id
		ORDER BY likes DESC
		LIMIT 4");
	$stmt->execute();
	
	$result2 = $stmt->fetchAll();
	
	//Recent
	$stmt = $db->prepare("
		SELECT *
		FROM cl_file JOIN cl_user ON cl_file.author=cl_user.id
		ORDER BY cl_file.id DESC
		LIMIT 4");
	$stmt->execute();
	
	$result3 = $stmt->fetchAll();
	
	//Comments
	$stmt = $db->prepare("
		SELECT SQL_CALC_FOUND_ROWS *
		FROM cl_comment JOIN cl_user ON cl_comment.author=cl_user.id
		WHERE file = 0
		ORDER BY cl_comment.id DESC
		" . page_sql_calc(10));
	$stmt->execute();
	$comments = $stmt->fetchAll();
	$num_rows = $db->query('SELECT FOUND_ROWS()')->fetchColumn();
	
}

//Handle errors
catch(PDOException $ex){
	errorbox('无法加载文件信息。');
}
?>
		
		<div class="section no-pad-bot" id="index-banner">
			<div class="container">
				<br><br>
				<h1 class="header center <?php echo $theme ?>-text"><?php echo $site_name ?></h1>
				<div class="row center">
					<h5 class="header col s12 light"><?php echo $site_desc ?></h5>
				</div>
				<div class="row center">
					<a href="<?php echo $game_url ?>" id="download-button" class="btn-large waves-effect waves-light <?php echo $theme ?>">下载游戏</a>
				</div>
				<br><br>
			</div>
		</div>
		<div class="container">
			<div class="card hoverable row">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">星标</span>
				<div class="card-content"><br><br>
<?php
if(!empty($result1)){
	foreach($result1 as $file){
		filebox($file);
	}
}
?>
				</div>
				
				<div class="col s12 card-action">
					<a class="<?php echo $theme ?>-text right" href="browse.php?featured=on">查看更多...</a>
				</div>
				
			</div>
		</div>
		<div class="container">
			<div class="card hoverable row">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">热门文件</span>
				<div class="card-content"><br><br>
<?php
if(!empty($result2)){
	foreach($result2 as $file){
		filebox($file);
	}
}
?>
				</div>
				
				<div class="col s12 card-action">
					<a class="<?php echo $theme ?>-text right" href="browse.php?sort=popular">查看更多...</a>
				</div>
				
			</div>
		</div>
		<div class="container">
			<div class="card hoverable row">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">最新文件</span>
				<div class="card-content"><br><br>
<?php
if(!empty($result3)){
	foreach($result3 as $file){
		filebox($file);
	}
}
?>
				</div>
				<div class="col s12 card-action">
					<a class="<?php echo $theme ?>-text right" href="browse.php?sort=recent">查看更多...</a>
				</div>
			</div>
		</div>
<?php if(!empty($comments)){ ?>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">评论</span>
				<div class="row"></div>
<?php
	//Comments
	foreach($comments as $comment){
		$append='';
		if($user_type==2) $append=' <span class="green-text">[' . $comment[4] . ']</span> <a href="index.php?deletecomment=' . $comment[0] . '" class="red-text">[删除]</a>';
		commentbox($comment, $append);
	}
//Pages
pagination($num_rows, 10, $theme);
?>
			</div>
		</div>
		
<?php }
if($user_type==0||$user_type==2){ ?>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">发表新评论</span>
				<form action="index.php" method="post" class="col s12 m10 offset-m1 l8 offset-l2">
					<div class="input-field col s12">
						<i class="fa fa-comment prefix" aria-hidden="true"></i>
						<textarea id="comment" name="comment" class="materialize-textarea" required></textarea>
						<label for="comment">内容</label>
					</div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s12" type="submit">发表评论</button>
				</form><div class="row"></div>
			</div>
		</div>
		
<?php
}
//Footer
include 'footer.php';
?>
