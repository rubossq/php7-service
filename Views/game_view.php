<?

if($data){
?>
	<p><?echo "server time " . (time())?></p>

	<?if($data['info']){?>

		<table class="statistic-table">
			<tr>
				<td>Total count of games:</td>
				<td><b><?=$data['info']["cnt_games"]?></b></td>
			</tr>
			<tr>
				<td>Count of errors:</td>
				<td style="color:red;"><b><?=$data['info']["cnt_wait"]?></b></td>
			</tr>
		</table>

	<?}?>
	<div style="height:50px;">

	</div>
	<?if($data['games']){?>

		<table class="statistic-table">
			<tr style = "padding:10px; background-color:#000; color: #fff">
				<th>Company</th>
				<th>Game</th>
				<th>Link</th>
				<th>Hours ago</th>
				<th>Type</th>
			</tr>
		<?foreach($data['games'] as $game){?>
			<tr style = "padding:10px; background-color:#9C9C9C;">
				<td><?=$game["cname"]?></td>
				<td><?=$game["gname"]?></td>
				<td>
					<a href="<?=$game["link"]?>">
						<?=$game["package_id"]?>
					</a>
				</td>
				<td>
					<?= $game["ago"] == 0 ? "just now" : $game["ago"] . " h"?>
				</td>
				<td>
					<?= $game["type"] == 1 ? "Market" : "ITunes"?>
				</td>
			</tr>
		<?}?>
		</table>
	<?}?>
	

<?
}else{
	echo "no info";
}

?>
