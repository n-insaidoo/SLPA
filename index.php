<?php
namespace slpa_project;
use slpa_core;
use apis\messenger;

include_once $_SERVER['DOCUMENT_ROOT']."/external_includes/config.php";
include_once "slpa_core/Request.php";
include_once "apis/messenger/FacebookMessengerAdapter.php";
include_once "apis/messenger/FacebookMessengerApi.php";

//Verification 

if(isset($_REQUEST['hub_challenge'])) {
    $challenge = $_REQUEST['hub_challenge'];
    $hub_verify_token = $_REQUEST['hub_verify_token'];
}
 
// Facebook sends our token to be verified with theirs - in our case the token is "fb_slpa_bot"
if ($hub_verify_token === \VERIFICATION_TOKEN) {
    echo $challenge; // Success is the output 
}
else{
	// when not verifying we perform our usual code
	$jsonObj = json_decode(file_get_contents('php://input'),true);
	if($jsonObj){
		$fbMessengerApi = new messenger\FacebookMessengerAdapter(new messenger\FacebookMessengerApi(\ACCESS_TOKEN));		
		$request = new slpa_core\Request($jsonObj);
		//var_dump($request);
		$fbMessengerApi->setSeenMessage($request->getPsid());
		foreach($request->getRoles() as $role){
			$fbMessengerApi->setTypingOn($request->getPsid());
			$output = $role->processRequest();
			$fbMessengerApi->setTypingOff($request->getPsid());
			if(!empty($output))
				$fbMessengerApi->sendMessage($request->getPsid(),$output);
		}
	}
	
}


?>
