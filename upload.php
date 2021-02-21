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

//CloudLevels Upload File Page

//Header + Vars:
$page_title='上传';
include 'header.php';

//Members only!
if($user_type==-1||$user_type==1){
	errorbox('您无权查看此页面。');
	include 'footer.php';
	exit(0);
}

//When there is input data
if(!empty($_POST["title"])){
	
	//Upload file
	
	//Check for errors
	if($_FILES['file']['error']!=UPLOAD_ERR_OK||$_FILES['screenshot']['error']!=UPLOAD_ERR_OK){
		errorbox('上传失败，请稍后再试。');
		include 'footer.php';
		exit(0);
	}
	
	//Verify file size
	if($_FILES['file']['size']>$file_size_limit){
		errorbox('您的文件已超过最大文件大小 ' . $file_size_limit/1000000 . 'MB。');
		include 'footer.php';
		exit(0);
	}
	
	//Verify image size
	if($_FILES['screenshot']['size']>$file_size_limit){
		errorbox('您的截图已超过最大文件大小 ' . $file_size_limit/1000000 . 'MB。');
		include 'footer.php';
		exit(0);
	}
	
	//File must be a ZIP
	if(strtolower(substr($_FILES['file']['name'], -4))!='.zip'){
		errorbox('必须上传 .zip 格式的文件。');
		include 'footer.php';
		exit(0);
	}
	
	//Image must be a PNG
	if(strtolower(substr($_FILES['screenshot']['name'], -4))!='.png'){
		errorbox('必须上传 .png 格式的截图。');
		include 'footer.php';
		exit(0);
	}
	
	$last_id=0;
	try{
		
		//Create database entries
		
		//Begin
		$db->beginTransaction();
		
		//File table entry
		date_default_timezone_set('Asia/Shanghai');
		$stmt = $db->prepare("
			INSERT INTO cl_file(name, author, date, ip, description)
			VALUES(?,?,?,?,?)");
		$stmt->execute(array(htmlspecialchars($_POST["title"]), $_SESSION['uid'], date("Y-m-d"), $_SERVER['REMOTE_ADDR'], nl2br(htmlspecialchars($_POST["description"]))));
		
		//Get file id
		$last_id = $db->lastInsertId();
		
		//Tag table entry
		if(!empty($_POST["tags"])){
			foreach($_POST["tags"] as $tag){
				$stmt = $db->prepare("
					INSERT INTO cl_tag(file, tag)
					VALUES(?,?)");
				$stmt->execute(array($last_id, htmlspecialchars($tag)));
			}
		}
		
		//Increment upload count
		$stmt = $db->prepare("
			UPDATE cl_user
			SET uploads = uploads+1
			WHERE id = ?");
		$stmt->execute(array($_SESSION['uid']));
		
		//End
		$db->commit();
		
	}
	//Handle errors
	catch(PDOException $ex){
		
		$db->rollBack();
		errorbox('上传失败，请稍后再试。');
		include 'footer.php';
		exit(0);
	}
	
	//Actually upload the files
	move_uploaded_file($_FILES["file"]["tmp_name"], "data/" . $last_id . ".zip");
	move_uploaded_file($_FILES["screenshot"]["tmp_name"], "data/" . $last_id . ".png");
	
	//Success!
	successbox('文件已上传，请稍候。');
	
	//Refresh
	header("Refresh:2;url=file.php?id=" . $last_id);
	
}
else{
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">上传文件</span>
				<form action="upload.php" method="post" enctype="multipart/form-data" class="col s12 m10 l8 offset-m1 offset-l2">
					<div class="file-field input-field">
						<div class="btn <?php echo $theme ?>">
							<span>文件 (最大 <?php echo ($file_size_limit/1000000); ?>MB)</span>
							<input type="file" name="file" accept="application/x-zip-compressed" required>
						</div>
						<div class="file-path-wrapper">
							<input class="file-path validate" type="text">
						</div>
					</div>
					<div class="file-field input-field">
						<div class="btn <?php echo $theme ?>">
							<span>截图</span>
							<input type="file" name="screenshot" accept="image/png" required>
						</div>
						<div class="file-path-wrapper">
							<input class="file-path validate" type="text">
						</div>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-commenting-o prefix" aria-hidden="true"></i>
						<input id="title" name="title" type="text" class="validate" required>
						<label for="title">标题</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-comment prefix" aria-hidden="true"></i>
						<textarea id="description" name="description" class="materialize-textarea" required></textarea>
						<label for="description">说明</label>
					</div>
					<i class="fa fa-cloud small col s1" aria-hidden="true"></i> 
					<div class="input-field col s11">
						<select id="tags" name="tags[]" multiple>
							<option value="" disabled selected>选择标签</option>
							<?php 
								$get_tags=explode("\n", $tags);
								foreach($get_tags as $tag)
									echo '<option value="' . trim($tag) . '">' . trim($tag) . '</option>';
							?>

						</select>
						<label for="tags">标签</label>
					</div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s12" type="submit">上传</button>
				</form><div class="row"></div>
			</div>
		</div>
		
<?php
}
//Footer
include 'footer.php';
?>
