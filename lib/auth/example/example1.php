<?php

require_once '../PHPGangsta/GoogleAuthenticator.php';

$ga = new PHPGangsta_GoogleAuthenticator();

//$secret = $ga->createSecret();
$secret = file_get_contents('secret');
echo "Secret is: ".$secret."\n\n<br>";
file_put_contents('secret',$secret);
$qrCodeUrl = $ga->getQRCodeGoogleUrl('BTC', $secret);
echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n<br>";
echo <<<HTML
<img src="$qrCodeUrl">
HTML
;


$oneCode = $ga->getCode($secret);
echo "Checking Code '$oneCode' and Secret '$secret':\n<br>";

$checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
if ($checkResult) {
    echo 'OK';
} else {
    echo 'FAILED';
}