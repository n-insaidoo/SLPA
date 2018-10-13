<?php
namespace slpa_core;
use \apis\messenger;

include_once "RequestRole.php";
include_once "Request.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerAdapter.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerApi.php";
include_once $_SERVER['DOCUMENT_ROOT']."/external_includes/config.php";

/**
 * SimpleMessage class.
 * This class represents one of the Roles that a Request can play.
 * It handles all the other messages that do not invoke any of the available functions.
 * 
 * @author ni15aaf
 */

class SimpleMessage extends RequestRole
{
    /*defining the role title constant*/
    const ROLETITLE = "SimpleMessage";

    private $fbMesgApi;

    /**
     * Constructor
     * @param  Request  $player  A Request object representing the request that plays a role.
     * @param  bool  $commandFlag  A flag that tells whether the current Role instance was created from a command or not.
     * True if it was created due to a command, false otherwise.
     */
    public function __construct(Request $player,$flag){
        parent::__construct($player,$flag);
    }

    //@Override
    /**
     * Checks whether $roleTitle corresponds to the role's title.
     * @param  string  $roleTitle  A string representing a role's title e.g. "SimpleMessage" in this case.
     * @return  bool  True if the role title matches, false otherwise.
     */
    public function isRole($roleTitle){
        if(strcmp($this::ROLETITLE,$roleTitle)===0) return true;
        return false;
    }

    //Override
    /**
     * Processes the given request (usually parsed from a command or established through the request's payload).
     * @return  string  Could either be a success, a failure message or an empty string.
     */
    public function processRequest(){
        if($this->commandFlag){
            return "Sorry, but that command does not exist. Please take a look at the list of allowed commands from the menu under 'Misc.'.";
        }
        else{
            //Natural Language Processing could take place in here
            if(preg_match("~!\w+.*~",$this->getRequest()->getTextMessage())){
                return "It seems that you are trying to issue a command...\nPlease make sure it is spelled correctly. Take a look at the list of allowed commands from the menu under 'Misc.' to ensure that.";
            }
            return "I'm sorry, I don't understand these type of messages yet...\n\n"
            ."Please find out more about what I can do by accessing the menu.";
        }
    }
}

?>