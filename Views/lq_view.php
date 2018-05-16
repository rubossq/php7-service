<?
	//var_dump($data);
	$data = $data->getObject();
?>
<!doctype html>
<html lang="en">
<head>
	<meta name="viewport" content="user-scalable=no, initial-scale=1, maximum-scale=1, minimum-scale=1, width=device-width, height=device-height" />
	<title>LiqPay</title>
	<style>
		body{
			margin:0px;
			padding:0px;
		}
	</style>
</head>
<body>
<div>
	<!--<textarea style="width:400px; height:200px;">
		<?=$data['params']?>
	</textarea>-->
</div>
<div id="liqpay_checkout"></div>
<script>
	//alert(1);
	window.LiqPayCheckoutCallback = function() {
		LiqPayCheckout.init({
			data: "<?=$data['data']?>",
			signature: "<?=$data['signature']?>",
			embedTo: "#liqpay_checkout",
			language: "en",
			mode: "embed" // embed || popup
		}).on("liqpay.callback", function(data){
			//alert("callback");
			console.log(data.status);
			if(data.status == "3ds_verify"){
				document.location.href = data.redirect_to;
			}
			if(data.status == "wait_sender"){
				//alert("here");
				document.location.href = "http://instagram.starfamous.ru/boss/";
			}
		}).on("liqpay.ready", function(data){
			//alert("ready");
			console.log("ready");
		}).on("liqpay.close", function(data){
			//alert("close");
			console.log("close");
		});
	};
</script>
<script src="//static.liqpay.com/libjs/checkout.js" async></script>
</body>
</html>