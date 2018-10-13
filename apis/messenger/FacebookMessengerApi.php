<?php
namespace apis\messenger;

/**
 * FacebookMessengerApi class.
 * This class is FacebookMessengerAdapter's Adaptee. 
 * It handles all the requests to the Facebook Messenger platform.
 * @author ni15aaf
 */

class FacebookMessengerApi {

    /*defining constants for the sender actions*/
    const SENDERACTION_SEEN = "mark_seen";
    const SENDERACTION_TYPING_ON = "typing_on";
    const SENDERACTION_TYPING_OFF = "typing_off";

    /*defining constants for the API urls*/
    private const SENDAPIURL = "https://graph.facebook.com/v2.6/me/messages?access_token=";

    private $accessToken;
    private $sendApiAuthenticatedUrl;
    
    /**
     * Constructor
     * @param  string  $accessToken  A string representing the access token key needed to make API calls.
     */
    public function __construct($accessToken){
        $this->accessToken = $accessToken;
        $this->sendApiAuthenticatedUrl = $this::SENDAPIURL.$this->accessToken;
    }

    /**
     * Internal function used to perform POST requests.
     * @param  string  $url  The url to use for the request.
     * @param  array  $requestBody  The associative array representing the JSON body of the request.
     */
    private function performPostRequest($url,$requestBody){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_exec($ch); //Returns true or false according to the outcome of the request
        curl_close($ch);
    }
    
    /**
     * Internal function used to perform GET requests.
     * @param  string  $url  The url to use for the request.
     * @return  string  A string representing the response.
     */
    private function performGetRequest($url){
		return file_get_contents($url);
    }
    
    /**
     * Retrieve Facebook's user first name, surname and profile picture through his/hers page scoped id (PSID).
     * Indexes are: "first_name","last_name", "profile_pic".
     * @param  string  $senderId  The eventual user's PSID.
     * @return  array  An array containing the Facebook user's name, surname and profile picture url.
     */
    public function getFacebookUserObj($senderId){
		$url = "https://graph.facebook.com/v2.6/".$senderId."?fields=first_name,last_name,profile_pic&access_token=".$this->accessToken; 
		$response = $this->performGetRequest($url);
		$fbApiJsonObj = json_decode($response,true);
		return $fbApiJsonObj;
    }
    
    /**
     * Sends a simple text message to an eventual facebook user defined by his/hers PSID.
     * @param  string  $recipientId  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     */
    public function sendJustTextMessage($recipientId, $message){
        $recipientId = json_encode($recipientId);
        $message = json_encode($message);

		$jsonData = "{
		    'messaging_type': 'RESPONSE',
		    'recipient':{
			'id':".$recipientId."	
		    },
		    'message':{
			'text':".$message."
		    }
        }";

        $this->performPostRequest($this->sendApiAuthenticatedUrl,$jsonData);
    }
    
    /**
     * Sends a text message with one or several operational buttons.
     * @param  string  $recipientId  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     * @param  array  $jsonButtons  An array of associative arrays in the following format (postback buttons example - for more formats see facebook docs): 
     * $jsonButtons = [
     *      array('type' => 'postback',
     *            'title' => '<your title>',
     *            'payload' => '<your UNIQUE payload>'
     *      ),
     *      ....
     * ]
     */
    public function sendTextWithButtonsMessage($recipientId, $message, $jsonButtons){

        $jsonData = 
        array(
            "messaging_type" => "RESPONSE",
            "recipient" => array(
                "id" => $recipientId
            ),
            "message" => array(
                "attachment" => array(
                    "type" => "template",
                    "payload" => array(
                        "template_type" => "button",
                        "text"=>$message,
                        "buttons"=>[]
                    )
                )
            )
        );

        for($i=0; $i < count($jsonButtons); $i++){
            array_push($jsonData['message']['attachment']['payload']['buttons'],$jsonButtons[$i]);
        }

        $jsonData = json_encode($jsonData);
		$this->performPostRequest($this->sendApiAuthenticatedUrl,$jsonData); 
    }

    /**
     * Sends sender actions to animate the chat. E.g. mark seen messages, display typing loading animation an so on.
     * @param  string  $recipientId  The PSID of the message's recipient.
     * @param  string  $action  The constant corresponding to a given sender action. Usage:
     * sendSenderAction(...,FacebookMessengerApi::SENDERACTION_SEEN,...)
     */
    public function sendSenderAction($recipientId, $action){
        
		$jsonData = array(
            "messaging_type" => "RESPONSE",
            'recipient' => array(
                'id' => $recipientId
            ), 
                'sender_action' => $action
            );
		$jsonData = json_encode($jsonData);
		$this->performPostRequest($this->sendApiAuthenticatedUrl,$jsonData); 
    }

    /**
     * Sends Quick Reply message.
     * These type of messages look like this:
     * Do you want to continue?
     * Quick Replies: (Yes) (No) (Tomorrow)
     * See Facebook docs for more info on Quick Reply.
     * @param  string  $recipientId  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     * @param  array  $quickReplies  An array of associative arrays in the following format (text content_type quick reply example - for more formats see Facebook docs): 
     * $quickReplies = [
     *      array('content_type' => 'postback',
     *            'title' => '<your title>',
     *            'payload' => '<your UNIQUE payload>'
     *      ),
     *      ....
     * ].
     */
    public function sendQuickReplyMessage($recipientId, $message, $quickReplies){
		
		$jsonData = array(
            "messaging_type" => "RESPONSE",
			'recipient' => array(
				'id' => $recipientId
			),
			'message' => array(
				'text' => $message,
				'quick_replies' => []
			)
        );
        
        for($i=0; $i < count($quickReplies); $i++){
            array_push($jsonData['message']['quick_replies'],$quickReplies[$i]);
        }

		$jsonData = json_encode($jsonData);
		$this->performPostRequest($this->sendApiAuthenticatedUrl,$jsonData); 
    }

    /**
     * Sends a message following the Generic template's guidelines.
     * Visit (https://developers.facebook.com/docs/messenger-platform/reference/template/generic/) for more details.
     * @param  string  $recipientId  The PSID of the message's recipient.
     * @param  array  $elements  A list of Elements objects (JSON).
     * $elements = [
     *  array(
     *      "title" => "",
     *      "subtitle" => "",
     *      "img_url" => "",
     *      "default_action" => URL button object, //what happens when the element is tapped.
     *      "buttons" => Array <buttons objects> // 3 at most.
     *  ),
     * ...
     * ]
     */
    public function sendGenericTemplateMessage($recipientId,$elements){
        $jsonData = array(
            "messaging_type" => "RESPONSE",
			'recipient' => array(
				'id' => $recipientId
            ),
            "message" => array(
                "attachment" => array( 
                    "type" => "template",
                    "payload" => array(
                        "template_type" => "generic",
                        "sharable"=>false,
                        "elements"=>[]
                    )
                )
            )
        );

        foreach($elements as $element){
            array_push($jsonData["message"]["attachment"]["payload"]["elements"],$element);
        }

        $jsonData = json_encode($jsonData);
		$this->performPostRequest($this->sendApiAuthenticatedUrl,$jsonData);
    }
}

?>