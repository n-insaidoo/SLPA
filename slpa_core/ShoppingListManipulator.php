<?php
namespace slpa_core;
use apis\slpa;

include_once "RequestRole.php";
include_once "Request.php";
include_once "SlpaErrorMessage.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/slpa/SlpaCore.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/slpa/SlpaCoreAdapter.php";

/**
 * ShoppingListManipulator class.
 * This class represents one of the Roles that a Request can play.
 * All the operations related to the shopping list will be managed here.
 * 
 * @author ni15aaf
 */

class ShoppingListManipulator extends RequestRole
{

    /*defining the constant that will keep track of all the commands available in this role class*/
    const COMMANDS = ['add','showlist','amlist','rmlist','clrlist'];

    /*defininng the role title constant*/
    const ROLETITLE = "ShoppingListManipulator";

    private $slpaApi;

    /**
     * Constructor
     * @param  Request  $player  A Request object representing the request that plays a role.
     * @param  bool  $commandFlag  A flag that tells whether the current Role instance was created from a command or not.
     * True if it was created due to a command, false otherwise.
     */
    public function __construct(Request $player, $commandFlag){
        parent::__construct($player,$commandFlag);
        $this->slpaApi = new slpa\SlpaCoreAdapter(new slpa\SlpaCore($this->getRequest()->getPsid()));
    }

    //@Override
    /**
     * Checks whether $roleTitle corresponds to the role's title.
     * @param  string  $roleTitle  A string representing a role's title e.g. "ShoppingListManipulator" in this case.
     * @return  bool  True if the role title matches, false otherwise.
     */
    public function isRole($roleTitle){
        if(strcmp($this::ROLETITLE,$roleTitle)===0) return true;
        return false;
    }

    /**
     * Adds one or several items to a specific user's shopping list.
     * @param mixed $args,... Explainatorium!
     * @return  string  A message representing the outcome.
     */
    public function addCommand(...$args){
        if(!empty($args)){
            $items = $args[0];
            $result = $this->slpaApi->addItemsToShoppingList($items);
            $resMess="";
            if($result<2){
                $resMess = (new SlpaErrorMessage($result))->getMessage();
            }
            else{
                $resMess = "The shopping list was successfully altered!";
            }
            return $resMess;
        }
        else{
            return "To use this command you need to provide at least one parameter";
        }
    }

    /**
     * Removes the shopping list from the system.
     * @param mixed $args,... Explainatorium!
     * @return  string  A message representing the outcome.
     */
    public function rmlistCommand(...$args){
        $items = $args[0];
        $result = $this->slpaApi->removeTheShoppingList();
        $resMess = "";
        if($result<2){
            $resMess = (new SlpaErrorMessage($result))->getMessage();
        }
        else{
            $resMess = "The shopping list was successfully removed!";
        }
        return $resMess;
    }

    /**
     * Clears the shopping list.
     * @param mixed $args,... Explainatorium!
     * @return  string  A message representing the outcome.
     */
    public function clrlistCommand(...$args){
        $items = $args[0];
        $result = $this->slpaApi->clearTheShoppingList();
        $resMess = "";
        if($result<3){
            $resMess = (new SlpaErrorMessage($result))->getMessage();
        }
        else{
            $resMess = "The shopping list was successfully cleared!";
        }
        return $resMess;
    }

    /**
     * Overwrites the shopping list with new items.
     * @param mixed $args,... Explainatorium!
     * @return  string  A message representing the outcome.
     */
    public function amlistCommand(...$args){
        if(!empty($args)){
            $result = $this->slpaApi->editTheShoppingList($args[0]);
            $resMess="";
            if($result<3){
                $resMess = (new SlpaErrorMessage($result))->getMessage();
            }
            else{
                $resMess = "The shopping list was successfully altered!";
            }
            return $resMess;
        }
        else{
            return "To use this command you need to provide at least one entry.";
        }
    }

    /**
     * Shows a group's shopping list.
     * @return  string  A message representing the outcome.
     */
    public function showlistCommand(){
        $result = $this->slpaApi->showShoppingList();
        $resMess = "";
        if(is_string($result)){
            $resMess = $result;
        }
        else{
            $resMess = (new SlpaErrorMessage($result))->getMessage();
        }

        return $resMess;
    }

    //Override
    /**
     * Processes the given request (usually parsed from a command or established through the request's payload).
     * @return  string  Could either be a success, a failure message or an empty string.
     */
    public function processRequest(){
        $result = "";
        if($this->commandFlag){
            $funcPostfix = "Command";
            $rawParams = null;
            preg_match("~^!(\w+)(?:\s+([\w,\s%&'\(\)\*\.]+))?$~",$this->getRequest()->getTextMessage(),$match);
            if(count($match)>2){
                $rawParams = $match[2];
            }
            $command = $match[1];

            $paramsArray = null;
            $_function = $command.$funcPostfix;
            if($rawParams){
                $paramsArray = $this->parseParameters($rawParams);
                $paramsArray = array_filter($paramsArray);

                $result=$this->$_function($paramsArray);
            }
            else{
                $result=$this->$_function();
            }
        }
        else{
            $payloadData = explode("_",$this->getRequest()->getPayload());
            $method = $payloadData[3];
            if(count($payloadData)>4){
                $params = [];
                for($i=4;$i<count($payloadData);$i++){
                    array_push($params,$payloadData[$i]);
                }
                $result = $this->$method($params);
            }
            else{
                $result = $this->$method();
            }
        }
        return $result;
    }
}

?>