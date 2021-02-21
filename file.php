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

//CloudLevels View File Page

//Header + Vars:
include 'header.php';

//ID Check
if($file_get==NULL){
	errorbox('无效的文件');
	include 'footer.php';
	exit(0);
}

//When there is comment data
if($user_type!=-1&&!empty($_POST["comment"])){
	
	try{
		date_default_timezone_set('America/New_York');
		$stmt = $db->prepare("
			INSERT INTO cl_comment(author, file, date, ip, comment)
			VALUES(?,?,?,?,?)");
		$stmt->execute(array($_SESSION['uid'], $file_get['id'], date("F j, Y"), $_SERVER['REMOTE_ADDR'], nl2br(htmlspecialchars($_POST["comment"]))));
	}
	
	//Handle errors
	catch(PDOException $ex){
		errorbox('无法发表评论。');
		include 'footer.php';
		exit(0);
	}
	
	successbox('评论已发布，请稍候。');
	header("Location:file.php?id=" . $file_get['id']);
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
	header("Location:file.php?id=" . $file_get['id']);
	include 'footer.php';
	exit(0);
}

//Check if user likes this file
$file_liked=FALSE;
if($user_type!=-1){
	try{
		$stmt = $db->prepare("
				SELECT *
				FROM cl_like
				WHERE author = ? AND file = ?");
		$stmt->execute(array($_SESSION['uid'], $file_get['id']));
		$result = $stmt->fetchAll();
		if(!empty($result)) $file_liked=TRUE;
	}
	//Handle errors
	catch(PDOException $ex){
		errorbox('无法检查用户是否点赞该文件。');
		include 'footer.php';
		exit(0);
	}
}

//If something is being done to the file
if(!empty($_GET["action"])){
	try{
	
		//Like
		if($user_type!=-1&&$_GET["action"]=='like'){
			$db->beginTransaction();
			if($file_liked){
				$stmt = $db->prepare("
					DELETE FROM cl_like
					WHERE author = ? AND file = ?");
				$stmt->execute(array($_SESSION['uid'], $file_get['id']));
				$stmt = $db->prepare("
					UPDATE cl_file
					SET likes = likes-1
					WHERE id = ?");
				$stmt->execute(array($file_get['id']));
				successbox('已取消点赞该文件，请稍候。');
			}
			else{
				$stmt = $db->prepare("
					INSERT INTO cl_like(author, file)
					VALUES(?,?)");
				$stmt->execute(array($_SESSION['uid'], $file_get['id']));
				$stmt = $db->prepare("
					UPDATE cl_file
					SET likes = likes+1
					WHERE id = ?");
				$stmt->execute(array($file_get['id']));
				successbox('已成功点赞该文件，请稍候。');
			}
			$db->commit();
			header("Location:file.php?id=" . $file_get['id']);
			include 'footer.php';
			exit(0);
		}
		
		//Admin stuff
		else if($user_type==2){
			
			//Delete
			if($_GET["action"]=='delete'){
				
				$stmt = $db->prepare("
					DELETE FROM cl_tag
					WHERE file = ?");
				$stmt->execute(array($file_get['id']));
				$stmt = $db->prepare("
					DELETE FROM cl_like
					WHERE file = ?");
				$stmt->execute(array($file_get['id']));
				$stmt = $db->prepare("
					DELETE FROM cl_comment
					WHERE file = ?");
				$stmt->execute(array($file_get['id']));
				$stmt = $db->prepare("
					DELETE FROM cl_file
					WHERE id = ?");
				$stmt->execute(array($file_get['id']));
				$stmt = $db->prepare("
					UPDATE cl_user
					SET uploads = uploads-1
					WHERE id = ?");
				$stmt->execute(array($file_get['author']));
				
				unlink('data/' . $file_get['id'] . '.zip');
				unlink('data/' . $file_get['id'] . '.png');
				successbox('文件已删除。');
				include 'footer.php';
				exit(0);
			}
			
			//Feature
			else if($_GET["action"]=='feature'){
				if($file_get['featured']){
					$stmt = $db->prepare("
						UPDATE cl_file
						SET featured = 0
						WHERE id = ?");
					$stmt->execute(array($file_get['id']));
					successbox('文件已被取消星标，请稍候。');
				}
				else{
					$stmt = $db->prepare("
						UPDATE cl_file
						SET featured = 1
						WHERE id = ?");
					$stmt->execute(array($file_get['id']));
					successbox('文件已被标记为星标，请稍候。');
				}
				header("Location:file.php?id=" . $file_get['id']);
				include 'footer.php';
				exit(0);
			}
			
			else
				errorbox('无效的文件操作。');
		}
		else
			errorbox('无效的文件操作。');
		
	}
	catch(PDOException $ex){
		errorbox('无法修改文件。');
		include 'footer.php';
		exit(0);
	}
}
	
$result=null;
$comments=null;
$num_rows=0;
$file_author='';
try{
	
	//Get author
	$stmt = $db->prepare("
			SELECT username, usergroup
			FROM cl_user
			WHERE id = ?");
	$stmt->execute(array($file_get["author"]));
	$result = $stmt->fetchAll();
	$file_author=$result[0];
	
	//Get tags
	$stmt = $db->prepare("
			SELECT tag
			FROM cl_tag
			WHERE file = ?");
	$stmt->execute(array($file_get["id"]));
	$result = $stmt->fetchAll();
	
	//Get comments
	$stmt = $db->prepare("
		SELECT SQL_CALC_FOUND_ROWS *
		FROM cl_comment JOIN cl_user ON cl_comment.author=cl_user.id
		WHERE file = ?
		ORDER BY cl_comment.id DESC
		" . page_sql_calc(10));
	$stmt->execute(array($file_get["id"]));
	$comments = $stmt->fetchAll();
	$num_rows = $db->query('SELECT FOUND_ROWS()')->fetchColumn();
	
}

//Handle errors
catch(PDOException $ex){
	errorbox('无法加载文件信息。');
	include 'footer.php';
	exit(0);
}
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%; word-wrap: break-word;"><?php if($file_get['featured']==1){ echo '<i class="fa fa-star" aria-hidden="true"></i> '; } echo $file_get['name']; ?></span>
				<div class="row"></div>
				<div class="row">
					<div class="col s5 offset-s1 center-align">
						<img class="responsive-img" src="/data/<?php echo $file_get['id'] ?>.png">
<?php if($user_type==2){ ?>
						<br><a href="file.php?id=<?php echo $file_get['id'] ?>&action=feature" class="btn waves-effect waves-light <?php if($file_get['featured']){ echo 'red">Un-Feature'; } else { echo 'green">Feature'; } ?></a>
						<a href="file.php?id=<?php echo $file_get['id'] ?>&action=delete" class="btn waves-effect waves-light red">删除</a>
<?php } ?>
					</div>
					<div class="col s5 center-align">
						<p>By <?php echo memberlink($file_author['username'], $file_author['usergroup'], false); if($user_type==2){ echo ' <span class="green-text">[' . $file_get['ip'] . ']</span>'; } ?></p>
						<p><?php echo $file_get['date'] ?></p>
						<p><i class="tiny fa fa-download" aria-hidden="true"></i> <?php echo $file_get['downloads'] ?> <i class="tiny fa fa-thumbs-up" aria-hidden="true"></i> <?php echo $file_get['likes'] ?></p>
						<p style="word-wrap: break-word;"><?php echo $file_get['description'] ?></p>
						<p><?php 
foreach($result as $tag){
	echo '<a href="browse.php?tags[]=' . $tag['tag'] . '" class="chip">' . $tag['tag'] . '</a>';
}
						?></p>
						<a href="download.php?id=<?php echo $file_get['id'] ?>" class="btn waves-effect waves-light <?php echo $theme ?>">下载</a>
<?php if($user_type==0||$user_type==2){ ?>
						<a href="file.php?id=<?php echo $file_get['id'] ?>&action=like" class="btn waves-effect waves-light <?php if($file_liked){ echo 'red'; } else { echo $theme; } ?>"><i class="tiny fa fa-thumbs-<?php if($file_liked) echo 'down'; else echo 'up'; ?>" aria-hidden="true"></i></a>
<?php } ?>
					</div>
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
		if($user_type==2) $append=' <span class="green-text">[' . $comment[4] . ']</span> <a href="file.php?id=' . $file_get['id'] . '&deletecomment=' . $comment[0] . '" class="red-text">[删除]</a>';
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
				<form action="file.php?id=<?php echo $file_get['id'] ?>" method="post" class="col s12 m10 offset-m1 l8 offset-l2">
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
