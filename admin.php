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

//CloudLevels Admin Control Panel

//Header + Vars:
$page_title='管理员控制面板';
include 'header.php';

//Admins only!
if($user_type!=2){
	errorbox('您无权查看此页面。');
	include 'footer.php';
	exit(0);
}

//When there is input data
if(!empty($_POST["name"])){
	
	//Create configuration file:

	//Open file
	$configfile = fopen("configuration.php", "w") or die('<div class="card hoverable red"><div class="card-content white-text"><p>错误: 无法将文件 <strong>configuration.php</strong> 写入服务器。</p></div></div>');
	fwrite($configfile, "<?php\n");

	//Write database values
	fwrite($configfile, "\$db_type='" . $db_type . "';\n");
	fwrite($configfile, "\$db_hostname='" . $db_hostname . "';\n");
	fwrite($configfile, "\$db_username='" . $db_username . "';\n");
	fwrite($configfile, "\$db_password='" . $db_password . "';\n");
	fwrite($configfile, "\$db_database='" . $db_database . "';\n");
	
	//Write default configuration stuff
	fwrite($configfile, "\$site_name='" . addslashes($_POST["name"]) . "';\n");
	fwrite($configfile, "\$site_desc='" . addslashes($_POST["description"]) . "';\n");
	fwrite($configfile, "\$game_url='" . addslashes($_POST["download"]) . "';\n");
	fwrite($configfile, "\$file_size_limit='" . addslashes($_POST["file_size"]) . "';\n");
	fwrite($configfile, "\$tags='" . addslashes($_POST["tag_list"]) . "';\n");
	fwrite($configfile, "\$theme='" . addslashes($_POST["theme"]) . "';\n");
	fwrite($configfile, "\$reg_question='" . addslashes($_POST["reg_question"]) . "';\n");
	fwrite($configfile, "\$reg_answer='" . addslashes($_POST["reg_answer"]) . "';\n");
	
	//Close file
	fwrite($configfile, "?>\n");
	fclose($configfile);
	
	//Message
	successbox('设置已更新，请稍候。');
	
	//Refresh
	header("Refresh:2");
	
}
else{
?>
		
		<br>
		<div class="container">
			<div class="row card hoverable">
				<span class="col s12 card-title <?php echo $theme ?> white-text center" style="font-size: 200%;">管理员控制面板</span>
				<form action="admin.php" method="post" class="col s12 m10 l8 offset-m1 offset-l2">
					<div class="input-field col s12">
						<i class="fa fa-commenting-o prefix" aria-hidden="true"></i>
						<input id="name" name="name" type="text" value="<?php echo $site_name ?>" class="validate" required>
						<label for="name">网站名称</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-comment prefix" aria-hidden="true"></i>
						<textarea id="description" name="description" class="materialize-textarea" required><?php echo $site_desc ?></textarea>
						<label for="description">网站说明</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-download prefix" aria-hidden="true"></i>
						<input id="download" name="download" type="url" value="<?php echo $game_url ?>" class="validate" required>
						<label for="download">游戏下载链接</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-folder prefix" aria-hidden="true"></i>
						<input id="file-size" name="file_size" type="number" value="<?php echo $file_size_limit ?>" class="validate" required>
						<label for="file-size">最大文件大小限制 (字节)</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-cloud prefix" aria-hidden="true"></i>
						<textarea id="tag-list" name="tag_list" class="materialize-textarea" required><?php echo $tags ?></textarea>
						<label for="tag-list">标签</label>
					</div>
					<i class="fa fa-paint-brush small col s1" aria-hidden="true"></i> 
					<div class="input-field col s11">
						<select id="theme" name="theme">
							<option value="light-blue"<?php if($theme=='light-blue') echo ' selected'; ?>>浅蓝色</option>
							<option value="cyan"<?php if($theme=='cyan') echo ' selected'; ?>>青色</option>
							<option value="teal"<?php if($theme=='teal') echo ' selected'; ?>>蓝绿色</option>
							<option value="green"<?php if($theme=='green') echo ' selected'; ?>>绿色</option>
							<option value="light-green"<?php if($theme=='light-green') echo ' selected'; ?>>浅绿色</option>
							<option value="lime"<?php if($theme=='lime') echo ' selected'; ?>>淡绿色</option>
							<option value="amber"<?php if($theme=='amber') echo ' selected'; ?>>琥珀色</option>
							<option value="orange"<?php if($theme=='orange') echo ' selected'; ?>>橙色</option>
							<option value="deep-orange"<?php if($theme=='deep-orange') echo ' selected'; ?>>深橙色</option>
							<option value="brown"<?php if($theme=='brown') echo ' selected'; ?>>棕色</option>
							<option value="grey"<?php if($theme=='grey') echo ' selected'; ?>>灰色</option>
							<option value="blue-grey"<?php if($theme=='blue-grey') echo ' selected'; ?>>蓝灰色</option>
							<option value="blue"<?php if($theme=='blue') echo ' selected'; ?>>蓝色</option>
							<option value="indigo"<?php if($theme=='indigo') echo ' selected'; ?>>靛青色</option>
							<option value="deep-purple"<?php if($theme=='deep-purple') echo ' selected'; ?>>深紫色</option>
							<option value="purple"<?php if($theme=='purple') echo ' selected'; ?>>紫色</option>
							<option value="pink"<?php if($theme=='pink') echo ' selected'; ?>>粉色</option>
							<option value="red"<?php if($theme=='red') echo ' selected'; ?>>红色</option>
						</select>
						<label for="theme">主题</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-lock prefix" aria-hidden="true"></i>
						<input id="reg-question" name="reg_question" type="text" value="<?php echo $reg_question ?>" class="validate" required>
						<label for="reg-question">验证问题</label>
					</div>
					<div class="input-field col s12">
						<i class="fa fa-key prefix" aria-hidden="true"></i>
						<input id="reg-answer" name="reg_answer" type="text" value="<?php echo $reg_answer ?>" class="validate" required>
						<label for="reg-answer">验证问题答案</label>
					</div>
					<button class="btn waves-effect waves-light <?php echo $theme ?> col s12" type="submit">Save</button>
				</form><div class="row"></div>
			</div>
		</div>
		
<?php
}
//Footer
include 'footer.php';
?>
