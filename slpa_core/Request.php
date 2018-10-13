<?php
namespace slpa_core;
include_once "SimpleMessage.php";
include_once "ShoppingListManipulator.php";
include_once "GroupManipulator.php";
include_once "AccountManipulator.php";
include_once "LocationBasedService.php";
include_once "CommandManager.php";

/**
 * Request class.
 * This class represents both the incoming and outgoing request on our system.
 * It is the subject of the Player-Role pattern which also involves these other roles:
 * ShoppingListManipulator, GroupManipulator, AccountManipulator, SimpleMessage, LocationBasedService and CommandManager.
 * 
 * Important note:
 * The unique payload strings (limit of 1000 char) that I set will allow to identify Roles. 
 * The payload will contain the method's name so that it is parsable.
 * Payload generic format: SLPA_<type>_<role>_<method_name>  # role and method should be case sensitive as they will be placed in place of code.
 * E.g. SLPA_QKR_GroupManipulator_showGroups
 * types: QKR, BTN, MBTN
 * @author ni15aaf 
 */

class Request {

    private $jsonObj;
    private $roles;

    /**
     * Constructor
     * @param  array  $jsonObj  An associative array that represents the JSON body of a Request.
     */
    public function __construct($jsonObj){
        $this->roles = array();
        $this->jsonObj = $jsonObj; //Ensure client class is parsing JSON correctly. e.g. json_decode(...,true);
        $this->parseRole();
    }

    /**
     * Adds a role to the Request.
     * @param  RequestRole  $role  A request's role.
     */
    public function addRole(RequestRole $role){
        array_push($this->roles,$role);
    }

    /**
     * Gets the role by using a role title
     * @param  string  $roleTitle  A role's title (normally a constant defined in each role).
     * @return  RequestRole  An instance of the requested role or null if not found.
     */
    public function roleOf($roleTitle){
        foreach($this->roles as $role){
            if($role->isRole($roleTitle)){
                return $role;
            }
        }
        return null;
    }

    /**
     * Gets all roles active for the current Request's instance.
     * @return  array  A list of type RequestRole.
     */
    public function getRoles(){
        return $this->roles;
    }

    /**
    * Gets the Facebook's PSID (Page Scoped ID). This string field is needed to identify the senders and send messages to them.
    * @return  string  The PSID
    */
    public function getPsid(){
        return $this->jsonObj["entry"][0]["messaging"][0]["sender"]["id"]; 
    }

    /**
    * Gets the message that was sent by the user
    * @return string corresponding to the text message.
    */
    // There are different way to get this from the jsonObj depending on the type of request**
    public function getTextMessage(){
        $paths = [
            $this->jsonObj["entry"][0]["messaging"][0]["message"]["text"], //normal messages and quick replies
            $this->jsonObj["entry"][0]["messaging"][0]["postback"]["title"]  //message with button(s)
        ];
        $hit = null;
        for($i=0;$i<count($paths);$i++){
                $hit = $paths[$i];
                if($hit)
                    break;
        }
        return $hit;
    }

    /**
    * Gets the payload string.
    * @return  string  A string representing a payload.
    */
    // There are different way to get this from the jsonObj depending on the type of request**
    public function getPayload(){
        $paths = [
            $this->jsonObj["entry"][0]["messaging"][0]["postback"]["payload"], //payload location for messages with buttons
            $this->jsonObj["entry"][0]["messaging"][0]["message"]["quick_reply"]["payload"]  //payload location for Quick Replies
        ];
        $hit = null;
        for($i=0;$i<count($paths);$i++){
                $hit = $paths[$i];
                if($hit)
                    break;
        }
        return $hit;
    }

    /**
     * Gets the referral object from the request.
     * @return  array  An array representing the referral object.
     */
    public function getReferral(){
        $paths = [
            $this->jsonObj["entry"][0]["messaging"][0]["referral"],
            $this->jsonObj["entry"][0]["messaging"][0]["postback"]["referral"]
        ];
        $hit = null;
        for($i=0;$i<count($paths);$i++){
                $hit = $paths[$i];
                if($hit)
                    break;
        }
        return $hit;
    }

    /**
     * Gets the coordinates object from the request.
     * @return  array  An array representing the coordinates object.
     */
    public function getLocation(){
        $hit = null;
        $hit = $this->jsonObj["entry"][0]["messaging"][0]["message"]["attachments"][0]["payload"]["coordinates"];
        return $hit;
    }
    
    /**
     * Gets the JSON object parsed from the body of the request.
     * @return  array  An array representing the request's JSON object which is normally found in requests' bodies.
     */
    public function getJson(){
		return $this->jsonObj;
    }

    /**
     * Checks whether $roleTitle is an authentic role.
     * @param  string  $roleTitle  A string representing a role's title.
     * @return  bool  True if the $roleTitle corresponds to an authentic role, false otherwise.
     */
    private function isRole($roleTitle){
        switch($roleTitle){
            case "ShoppingListManipulator":
            case "GroupManipulator":
            case "AccountManipulator":
            case "SimpleMessage":
            case "CommandManager":
            case "LocationBasedService": return true;
            break;
            default: return false;
        }
    }
    
    /**
     * Parses the correct role for the request Request.
     */
    private function parseRole(){
        $namespaceConst = "slpa_core\\";
        $instance = $this;

        if($this->getPayload()!==null){
            $payloadInfo=explode("_",$this->getPayload());
            $roleTitle = $payloadInfo[2];
            if($this->isRole($roleTitle)){
                $roleTitle=$namespaceConst.$roleTitle;
                $this->addRole(new $roleTitle($instance,false));
            }
        }
        elseif($this->getLocation()!==null){
            $this->addRole(new LocationBasedService($instance,false));
        }
        elseif($this->getTextMessage()!==null){
            $message = trim($this->getTextMessage());
            if(preg_match("~^!(\w+)(?:\s+([\w,\s%&'\(\)\*\.]+))?$~",$message,$match)){
                $roleTypes = ["ShoppingListManipulator","GroupManipulator"];
                for($i=0;$i<count($roleTypes);$i++){
                    $role=$namespaceConst.$roleTypes[$i]; 
                    if(in_array($match[1],$role::COMMANDS)){
                        $this->addRole(new $role($instance,true));
                        break;
                    }
                }
                if(count($this->roles)<1){
                    $this->addRole(new SimpleMessage($instance,true));
                }
            }
            else{
                $this->addRole(new SimpleMessage($instance,false));
            }
        }
        if($this->getReferral()!==null){
            $this->addRole(new GroupManipulator($instance,false));
        }
    }
}

?>