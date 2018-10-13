<?php
namespace slpa_core;

include_once "Request.php";

/**
 * RequestRole class.
 * This class represents a specific role that a given Request is playing.
 * It  is the Role's abstraction of the Player-Role pattern which also involves these other roles:
 * ShoppingListManipulator, GroupManipulator, AccountManipulator, SimpleMessage, LocationBasedService and CommandManager.
 *
 * @author ni15aaf
 */

 abstract class RequestRole 
 {
     private $player;
     protected $commandFlag;

     /**
      * Constructor
      * @param  Request  $player  A Request object representing the request that plays a role.
      * @param  bool  $commandFlag  A flag that tells whether the current Role instance was created from a command or not.
      * True if it was created due to a command, false otherwise.
      */
     public function __construct(Request $player,$flag){
        $this->player = $player;
        $this->commandFlag = $flag;
     }

     abstract public function isRole($roleTitle);

     abstract public function processRequest();

     /**
      * Gets the Request object that plays a specific role.
      * @return  Request  A Request object that plays a specific role. 
      */
     protected function getRequest(){
         return $this->player;
     }

    /**
     * Parses eventual parameter(s) from the text message string that was recognised as a command.
     * @param  string  $str  A string representing the portion of the text message that contains the commands.
     * @return  array  An array of string representing the parameters provided fro a specific command.
     */
    protected function parseParameters($str){
        return $params=preg_split('~\s*,\s*~', $str);
    }
 }

?>