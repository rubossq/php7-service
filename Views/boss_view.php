<?
use Famous\Lib\Utils\Constant as Constant;
if($data){
?>
	<p><?echo "server time " . (time())?></p>
	<table class="statistic-table">
		<tr>
			<td>Total count of users</td>
			<td><b><?=$data['totalUsersCount']?></b></td>
		</tr>
		<tr>
			<td>Online users ROYAL FLWRS</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRoyalFlwrs']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours ROYAL FLWRS</td>
			<td style="color:red;"><b><?=$data['newUsersCountRoyalFlwrs']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users REAL LKS</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRealLks']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours REAL LKS</td>
			<td style="color:red;"><b><?=$data['newUsersCountRealLks']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users FLWRS</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountFlwrs']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours FLWRS</td>
			<td style="color:red;"><b><?=$data['newUsersCountFlwrs']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users LKS</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountLks']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours LKS</td>
			<td style="color:red;"><b><?=$data['newUsersCountLks']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------ESMD------------------------</td>
		</tr>
		<tr>
			<td>Online users Royal likes</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRoyalLikes']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours royal likes</td>
			<td style="color:red;"><b><?=$data['newUsersCountRoyalLikes']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users real followers</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRealFollowers']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours real followers</td>
			<td style="color:red;"><b><?=$data['newUsersCountRealFollowers']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users flwrs boost</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountFlwrsBoost']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours flwrs boost</td>
			<td style="color:red;"><b><?=$data['newUsersCountFlwrsBoost']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users Meteor</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountPhantom']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours Meteor</td>
			<td style="color:red;"><b><?=$data['newUsersCountPhantom']?></b></td>
		</tr>
		<?if(!empty($_GET['way'])){?>
		<tr>
			<td>Online users Meteor</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountMeteor']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours Meteor</td>
			<td style="color:red;"><b><?=$data['newUsersCountMeteor']?></b></td>
		</tr>
		<tr>
			<td colspan="2">-----------------ESMD-------------------------</td>
		</tr>
		<tr>
			<td>Online users Phantom</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountPhantom']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours Phantom</td>
			<td style="color:red;"><b><?=$data['newUsersCountPhantom']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users Royal followers</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRoyalFollowers']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours royal followers</td>
			<td style="color:red;"><b><?=$data['newUsersCountRoyalFollowers']?></b></td>
		</tr>
		<tr>
			<td colspan="2">------------------------------------------</td>
		</tr>
		<tr>
			<td>Online users real likes</td>
			<td style="color:blue;"><b><?=$data['onlineUsersCountRealLikes']?></b></td>
		</tr>
		<tr>
			<td>New users in 24 hours real likes</td>
			<td style="color:red;"><b><?=$data['newUsersCountRealLikes']?></b></td>
		</tr>
		<?}?>
	</table>
	
	</br>
	
	Adding new tasks
	<form action="" method="POST" >
		<textarea class="query" name="query" ></textarea>
		</br>
		<input type="submit" value=" Send " />
	</form>
	
	<h2>Push news</h2>
	<form action="" method="POST" >
		<p>Name:</p>
		<input type="text" name = "news_name" value="" />
		<p>Fire time:</p>
		<input type="text" name = "ftime" value="0" />
		<input type="submit" value=" Fire " />
	</form>
	<h2>Push Ad</h2>
	<form action="" method="POST" >
		<p>Name:</p>
		<input type="text" name = "ad_name" value="" />
		<p>Priority:</p>
		<input type="text" name = "ad_priority" value="" />
		<p>Name App:</p>
		<input type="text" name = "ad_name_app" value="" />
		<p>App id:</p>
		<input type="text" name = "ad_app_id" value="" />
		<p>Desc 1:</p>
		<input type="text" name = "ad_desc1" value="" />
		<p>Desc 2:</p>
		<input type="text" name = "ad_desc2" value="" />
		<p>Target lang:</p>
		<select name="target_lang">
			<option value="all">All</option>
			<option value="ru">Ru</option>
		</select>
		<input type="submit" value=" Push " />
	</form>
	<?if($data['news']){?>
		<h2>Red news</h2>
		<form action="" method="POST" >
			<input type="hidden" name = "news_id" value="<?=$data['news']['id']?>" />
			<p>Name:</p>
			<input type="text" name = "name" value="<?=$data['news']['name']?>" />
			<p>Can complite:</p>
			<input type="number" name = "can_complete" value="<?=$data['news']['can_complete']?>"/>
			<p>Refire time:</p>
			<input type="text" name = "rtime" value="<?=$data['news']['rtime']?>" />
			<p>Type:</p>
			<select name="type">
				<option <?if($data['news']['type'] == Constant::ONE_TYPE) echo "selected ";?> value="<?=Constant::ONE_TYPE?>">
					Onetime
				</option>
				<option <?if($data['news']['type'] == Constant::MULTIPLE_TYPE) echo "selected ";?> value="<?=Constant::MULTIPLE_TYPE?>">
					Multiple times
				</option>
			</select>
			
			<p>CSS:</p>
			<textarea class="query" name="news_css" ><?=$data['news']['css_json']?></textarea>
			<p>HTML:</p>
			<textarea class="query" name="news_html"><?=$data['news']['html_json']?></textarea>
			<p>JS:</p>
			<textarea class="query" name="news_js"> <?=$data['news']['js_json']?></textarea>
			<br>
			<input type="submit" value="Save" />
		</form>
	<?}else{?>
		<h2>Add news</h2>
		<form action="" method="POST" >
			<p>Name:</p>
			<input type="text" name = "name" value="" />
			<p>Can complite:</p>
			<input type="number" name = "can_complete" value="1"/>
			<p>Refire time:</p>
			<input type="text" name = "rtime" value="0" />
			<p>Type:</p>
			<select name="type">
				<option value="<?=Constant::ONE_TYPE?>">
					Onetime
				</option>
				<option value="<?=Constant::MULTIPLE_TYPE?>">
					Multiple times
				</option>
			</select>
			
			<p>CSS:</p>
			<textarea class="query" name="news_css" ></textarea>
			<p>HTML:</p>
			<textarea class="query" name="news_html" ></textarea>
			<p>JS:</p>
			<textarea class="query" name="news_js" ></textarea>
			<br>
			<input type="submit" value=" Send " />
		</form>
	<?}?>
<?
}else{
	echo "no info";
}

?>
