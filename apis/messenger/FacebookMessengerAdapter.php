<?php
namespace apis\messenger;

include_once "FacebookMessengerApi.php";
include_once "ChatBotAdapter.php";

/**
 * FacebookMessengerAdapter class.
 * This class is FacebookMessengerApi's the Adapter. 
 * Certain methods in this class will be used by some of the roles of the Request class.
 * @author ni15aaf
 */

 // find a better name for $recipient

 class FacebookMessengerAdapter implements ChatBotAdapter{
     
    private $fMessengerApi;

    /**
     * Constructor
     * @param  FacebookMessengerApi  $fma  An instance of the API needed to perform calls.
     */
    public function __construct(FacebookMessengerApi $fma){
        $this->fMessengerApi = $fma;
    }

    /**
     * Retrieve the Facebook's user first name, surname and profile picture through his/hers page scoped id (PSID).
     * Indexes are: "first_name","last_name", "profile_pic".
     * @param  string  $recipient  The eventual user's PSID.
     * @return  array  An array containing the Facebook user's name, surname and profile picture url.
     */
    public function getUserData($userId){
        return $this->fMessengerApi->getFacebookUserObj($userId);
    }

    /**
     * Sends a simple text message to an eventual Facebook user defined by his/hers PSID.
     * @param  string  $recipient  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     */
    public function sendMessage($recipient,$message){
        $this->fMessengerApi->sendJustTextMessage($recipient,$message);
    }

    /**
     * Sends a text message with one or several operational buttons.
     * @param  string  $recipient  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     * @param  array  $buttons  An array of associative arrays in the following format (postback buttons example - for more formats see Facebook docs): 
     * $buttons = [
     *      array('type' => 'postback',
     *            'title' => '<your title>',
     *            'payload' => '<your UNIQUE payload>'
     *      ),
     *      ....
     * ]
     */
    public function sendMessageWithButtons($recipient,$message,$buttons){
        $this->fMessengerApi->sendTextWithButtonsMessage($recipient,$message,$buttons);
    }

    /**
     * Sets the seen marker on the latest message in the chat.
     * @param  string  $recipient  The PSID of the message's recipient.
     */
    public function setSeenMessage($recipient){
        $this->fMessengerApi->sendSenderAction($recipient,$this->fMessengerApi::SENDERACTION_SEEN);
    }

    /**
     * Sets on the typing animation in the chat.
     * @param  string  $recipient  The PSID of the message's recipient.
     */
    public function setTypingOn($recipient){
        $this->fMessengerApi->sendSenderAction($recipient,$this->fMessengerApi::SENDERACTION_TYPING_ON);
    }

    /**
     * Sets off the typing animation in the chat.
     * @param  string  $recipient  The PSID of the message's recipient.
     */
    public function setTypingOff($recipient){
        $this->fMessengerApi->sendSenderAction($recipient,$this->fMessengerApi::SENDERACTION_TYPING_OFF);
    }

    /**
     * Sends Quick Reply message.
     * These type of messages look like this:
     * Do you want to continue?
     * Quick Replies: (Yes) (No) (Tomorrow)
     * See Facebook docs for more info on Quick Reply.
     * @param  string  $recipient  The PSID of the message's recipient.
     * @param  string  $message  The message to be sent.
     * @param  array  $replies  An array of associative arrays in the following format (text content_type quick reply example - for more formats see Facebook docs): 
     * replies = [
     *      array('content_type' => 'postback',
     *            'title' => '<your title>',
     *            'payload' => '<your UNIQUE payload>'
     *      ),
     *      ....
     * ].
     */
    public function sendQuickReply($recipient,$message,$replies){
        $this->fMessengerApi->sendQuickReplyMessage($recipient,$message,$replies);
    }

    /**
     * Sends a message following the generic template's guidelines (see docs. for more details).
     * @param  string  $recipient  The PSID of the message's recipient.
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
    public function sendGenericTemplateMessage($recipient,$elements){
        $this->fMessengerApi->sendGenericTemplateMessage($recipient,$elements);
    }
 }
?>