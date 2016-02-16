<pre>
<?php

$uid = $_POST['loginID'];
$gameid = $_POST['gameID'];

if($uid && $gameid) {
	$checkUrl = 'http://www.uuuu.com/system/json/payment/verify.case?json={"params":{"uid":'.$uid.',"prodid":'.$gameid.'}}';
	$checkData = file_get_contents($checkUrl, 0, null, null);
	$checkOutput = json_decode($checkData, true);
	print($checkUrl);
	print_r($checkOutput);
	
	if($checkOutput[response][ticket][valid] == true) {
		print("valid=1");
	} else {
		print("valid=0");
	}
	
}

?>
</pre>