<div class='midBlock' id='midBlock'>
	<div class='topAdv'>
		<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
		<!-- adaptive3 -->
		<ins class="adsbygoogle"
			 style="display:block"
			 data-ad-client="ca-pub-7097445146737161"
			 data-ad-slot="4801192138"
			 data-ad-format="horizontal"></ins>
		<script>
		(adsbygoogle = window.adsbygoogle || []).push({});
		</script>
	</div>
	<?
		if(is_array($data)){
			foreach($data as $question)
			{
				//echo "{$question->getId()}</br>";
				//echo "{$question->getQuestion()}</br>";
				//echo "{$question->getCategoryId()}</br>";
				//echo "{$question->getCategoryName()}</br>";
				//echo "{$question->getAuthorName()}</br>";
				//echo "{$question->getWordDate()}</br>";
				$url = Route::getUrl()."/questions/{$question->getId()}/".Route::getShort($question->getQuestion());
				$urlc = Route::getUrl()."/categories/{$question->getCategoryId()}/";
				echo "
					<div class='questBlock'>
						<div class='questInsideTop'>
							<div class='questAuthorInside'>
								<div class='questAva'><div class='avaContainer'><div><span>M</span></div></div></div>
								<div class='questAuthor'><span>{$question->getAuthorName()}</span></div>
								<div class='clear'></div>
							</div>
							
							<div class='questCategoryInside'>
								<div class='questCategory'><a href='{$urlc}'>{$question->getCategoryName()}</a></div>
							</div>
						</div>
						
						<div class='questTexti'><a href='{$url}'>{$question->getQuestion()}</a></div>
						
						<table class='questInsideBottom'>
							<tr>
								<td class='questNumAns'>
									<span>{$question->getAnswerCount()} answers</span>
								</td>
									
								<td class='questTime'>
									<span>{$question->getWordDate()}</span>
								</td>
							</tr>
						</table>
					</div>";
			}
			$lim = 20;
			if(isset($_GET['lim'])){
				if(is_numeric($_GET['lim']))
					$lim = $_GET['lim'] + 20;
			}
			echo "
			<div style='text-align:center'>
				<a href = '". Route::getUrl() . "{$curCat}/?lim={$lim}' class='mbtn'>Еще вопросы</a>
			</div>
			";
		}else{
			echo "<h2 style = 'color:white; text-align:center; margin-top:20px;'>Зима близко - вот поэтому на этой странице ничего нету =(</h3>";
		}
		
	?>
</div>
