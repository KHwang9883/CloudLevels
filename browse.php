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

//CloudLevels Browse Page

//Header + Vars:
$page_title='浏览';
include 'header.php';

$result=null;
$num_rows=0;
try{
	
	//Get requested files
	$where='WHERE ';
	$args=array();
	
	//Title
	if(!empty($_GET["title"])){
		$where.=' AND name LIKE ?';
		array_push($args, "%" . $_GET["title"] . "%");
	}
	
	//Author
	if(!empty($_GET["author"])){
		$where.=' AND username = ?';
		array_push($args, $_GET["author"]);
	}
	
	//Featured
	if(!empty($_GET["featured"]))
		$where.=' AND featured=1';
	
	//Liked
	if(!empty($_GET["liked"])){
		$where.=' AND cl_file.id IN (SELECT file FROM cl_like WHERE author = ? ) ';
		array_push($args, $_SESSION['uid']);
	}
	
	//Tags
	if(!empty($_GET["tags"])){
		foreach($_GET["tags"] as $tag){
			$where.=' AND cl_file.id IN (SELECT file FROM cl_tag WHERE tag = ? ) ';
			array_push($args, $tag);
		}
	}
	
	//Order by
	$order='';
	if(empty($_GET["sort"]))
		$order='cl_file.id';
	else if($_GET["sort"]=='popular')
		$order='likes';
	else if($_GET["sort"]=='downloaded')
		$order='downloads';
	else
		$order='cl_file.id';
	
	//No query case
	if($where=='WHERE '){
		$where='';
	}
	
	//Remove first AND from where string
	$where=preg_replace('/AND/', '', $where, 1);
	
	$stmt = $db->prepare("
		SELECT SQL_CALC_FOUND_ROWS *
		FROM cl_file JOIN cl_user ON cl_file.author=cl_user.id
		" . $where . "
		ORDER BY " . $order .  " DESC
		" . page_sql_calc(16));
	$stmt->execute($args);
	
	$result = $stmt->fetchAll();
	
	$num_rows = $db->query('SELECT FOUND_ROWS()')->fetchColumn();
	
}

//Handle errors
catch(PDOException $ex){
	errorbox('Failed to load files.');
}
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">筛选器</span>
				<form action="browse.php" method="get">
					<div class="input-field col s6">
						<i class="fa fa-commenting-o prefix" aria-hidden="true"></i>
						<input id="title" name="title" type="text" value="<?php if(!empty($_GET["title"])){echo $_GET["title"];} ?>" class="validate">
						<label for="title">标题</label>
					</div>
					<div class="input-field col s6">
						<select id="tags" name="tags[]" multiple>
							<option value="" disabled selected>选择标签</option>
							<?php 
								$get_tags=explode("\n", $tags);
								foreach($get_tags as $tag){
									if(!empty($_GET["tags"])&&in_array(trim($tag), $_GET["tags"]))
										echo '<option value="' . trim($tag) . '" selected>' . trim($tag) . '</option>';
									else
										echo '<option value="' . trim($tag) . '">' . trim($tag) . '</option>';
								}
							?>

						</select>
						<label for="tags">标签</label>
					</div>
					<div class="input-field col s6">
						<i class="fa fa-user prefix" aria-hidden="true"></i>
						<input id="author" name="author" type="text" value="<?php if(!empty($_GET["author"])){echo $_GET["author"];} ?>" class="validate">
						<label for="author">作者</label>
					</div>
					<div class="input-field col s6">
						<select id="sort" name="sort" required>
							<option value="recent"<?php if(!empty($_GET["sort"])&&$_GET["sort"]=='recent') echo ' selected'; ?>>最新</option>
							<option value="popular"<?php if(!empty($_GET["sort"])&&$_GET["sort"]=='popular') echo ' selected'; ?>>最热门</option>
							<option value="downloaded"<?php if(!empty($_GET["sort"])&&$_GET["sort"]=='downloaded') echo ' selected'; ?>>最多下载</option>
						</select>
						<label for="sort">排序</label>
					</div>
					<div class="switch col s6">
						<label>
						全部
						<input type="checkbox" name="featured"<?php if(!empty($_GET["featured"])) echo ' checked'; ?>>
						<span class="lever"></span>
						星标
						</label>
					</div>
<?php if($user_type==0||$user_type==2){ ?>
					<div class="switch col s6 right-align">
						<label>
						全部
						<input type="checkbox" name="liked"<?php if(!empty($_GET["liked"])) echo ' checked'; ?>>
						<span class="lever"></span>
						已赞
						</label>
					</div>
<?php } ?>
					<div class="row"></div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s10 l8 offset-s1 offset-l2" type="submit">筛选</button>
				</form><div class="row"></div>
			</div>
		</div>
		
		<div class="container">
			<div class="card hoverable row">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">浏览</span>
				<div class="card-content"><br><br>
<?php
if(!empty($result)){
	foreach($result as $file){
		filebox($file);
	}
}
?>
				</div>
<?php
//Pages
pagination($num_rows, 16, $theme);
?>
			</div>
		</div>
		
<?php
//Footer
include 'footer.php';
?>
