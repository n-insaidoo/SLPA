<?php
namespace apis\messenger;

/**
 * ChatBotAdapter interface.
 * This class is the Adapter interface for FacebookMessengerApi.
 * It defines the methods that the inheriting Adapter has to implement.
 * @author ni15aaf
 */

interface ChatBotAdapter {

    public function getUserData($id);
    public function sendMessage($recipient,$message);
    public function sendMessageWithButtons($recipient,$message,$buttons);
    public function setSeenMessage($recipient);
    public function setTypingOn($recipient);
    public function setTypingOff($recipient);
    public function sendQuickReply($recipient,$message,$replies);
    public function sendGenericTemplateMessage($recipient,$elements);
}

?>