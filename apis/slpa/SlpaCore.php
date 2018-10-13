<?php
namespace apis\slpa;
use \PDO;

include_once $_SERVER['DOCUMENT_ROOT']."/external_includes/config.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/NotORM.php";

/**
 * SlpaCore class
 * This class is SlpaCoreAdapter's Adaptee. 
 * It takes care of handling all the interaction with the database.
 * 
 * @author ni15aaf
 */

class SlpaCore
{
    /*defining the PDO's dns constant*/
    private const DNS = "mysql:dbname=".\DBNAME.";host=".\SERVERNAME;

    /* defining the maximum amount of group members, group owned and group joined */
    private const MAXGROUPMEMBERS = 10;
    private const MAXGROUPOWNED = 5;
    private const MAXGROUPJOINED = 5;

    private $db; //NotORM type
    private $userId;
    private $pdo;

    /**
     * Constructor
     * @param  string  $userId  A string representing the Facebook's user PSID, which is also stored in FB_USER.id (in the DB).
     */
    public function __construct($userId){
        $this->pdo = new PDO($this::DNS,\USERNAME,\PASSWORD);
        $this->db = new \NotORM($this->pdo);
        $this->userId = $userId;
    }

    /**
     * Getter for $userId.
     * @return  string  User id (PSID).
     */
    public function getUserId(){
        return $this->userId;
    }

    /**
     * Checks whether the current user is registered on the System.
     * @return  bool  true user exist; false the contrary.
     */
    public function isUserRegistered(){
        if($this->db->FB_USER[$this->userId] === null){
            return false;
        }
        else{
            return true;
        }
    }

    /**
     * Checks whether the current user has SELECTED_CG set and whether it is valid (a group might be non existent its old id could be still set). 
     * @return  bool  True: it is set to a valid cg; false: not set at all or set to an invalid one.
     */
    public function isSelectedCustomerGroupSet(){
        $fbUser = $this->db->FB_USER[$this->userId];
        if($fbUser["SELECTED_CG"]===null){
            return false;
        }
        else{
            if($this->db->CONSUMER_GROUP[$fbUser["SELECTED_CG"]]===null){
                return false;
            }
            else{
                return true;
            }
        }
    }

    /**
     * Checks whether the current CONSUMER_GROUP has a row in SHOPPING_LIST (which represents their shopping list).
     * @return  bool  True: there is a row; false: there isn't.
     */
    public function isShoppingListPresent(){
        $fbUser = $this->db->FB_USER[$this->userId];
        if($this->db->SHOPPING_LIST[$fbUser["SELECTED_CG"]]===null){
            return false;
        }
        else{
            return true;
        }
    }
    
    /**
     * Checks whether the current SHOPPING_LIST's CONTENT column is empty (= null).
     * @return  bool  True: it is empty; false: it is not.
     */
    public function isCurrentShoppingListEmpty(){
        $fbUser = $this->db->FB_USER[$this->userId];
        $shoppingList=$this->db->SHOPPING_LIST[$fbUser["SELECTED_CG"]];
        if($shoppingList["CONTENT"]===null){
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * Tells whether the user is the owner of the COMSUMER_GROUP that his/hers SELECTED_CG identifies.
     * @return  bool  True: is the owner; false: not the owner.
     */
    public function isUserGroupOwner(){
        $fbUser = $this->db->FB_USER[$this->userId];
        foreach($fbUser->CG_MEMBER() as $cgMember){
            if($cgMember["CONSUMER_GROUP_id"] === $fbUser["SELECTED_CG"]){
                if(((bool)$cgMember["OWNER"])){
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Tells whether the user is the owner of the COMSUMER_GROUP identified by $groupId.
     * @param  string  $groupId  The id of a group.
     * @return  bool  True: is the owner; false: not the owner.
     */
    public function isUserGroupOwnerById($groupId){
        $fbUser = $this->db->FB_USER[$this->userId];
        foreach($fbUser->CG_MEMBER() as $cgMember){
            if($cgMember["CONSUMER_GROUP_id"] === $groupId){
                if(((bool)$cgMember["OWNER"])){
                    return true;
                }
                break;
            }
        }
        return false;
    }

    /**
     * Tells if the user has at least a group (owner or member).
     * @return  bool  True: the user has at least a group; false: he/she doesn't.
     */
    public function hasGroups(){
        $fbUser = $this->db->FB_USER[$this->userId];
        $res = false;
        if(count($fbUser->CG_MEMBER())>0)
            $res = true;
        return $res;
    }

    /**
     * Tells if the user can be the owner of an additional group (checked when creating a group or being elected new owner).
     * @param  string  $psid  The id of the user.
     * @return  bool  True: the user can be owner of one more group; false: the user can't.
     */
    public function canUserOwnMoreGroups($psid){
        $fbUser = $this->db->FB_USER[$psid];
        $count = 0;
        foreach($fbUser->CG_MEMBER() as $cgMember){
            if(((bool)$cgMember["OWNER"])){
                $count++;
            }
        }
        if($count<$this::MAXGROUPOWNED){
            return true;
        }
        return false;
    }

    /**
     * Tells if the user can join an additional group.
     * @param  string  $psid  The id of the user.
     * @return  bool  True: the user can join; false: the user can't join.
     */
    public function canUserJoinMoreGroups($psid){
        $fbUser = $this->db->FB_USER[$psid];
        $count = 0;
        foreach($fbUser->CG_MEMBER() as $cgMember){
            if(!((bool)$cgMember["OWNER"])){
                $count++;
            }
        }
        if($count<$this::MAXGROUPJOINED){
            return true;
        }
        return false;
    }

    /**
     * Tells if the group can have an additional member.
     * @param  string  $groupId  The id of a specific group.
     * @return  bool  True: the group can; false: the group can't.
     */
    public function canGroupHaveMoreMembers($groupId){
        $consumerGroup = $this->db->CONSUMER_GROUP[((int)$groupId)];
        if(count($consumerGroup->CG_MEMBER())<$this::MAXGROUPMEMBERS){
            return true;
        }
        return false;
    }

    /**
     * Inserts a new row in the FB_USER table.
     * @param  array  $columns  An array containing allowed values in the FB_USER table.
     * e.g.
     * $columns = array (
     *  "id" => ???,
     *  "SELECTED_CG" => ??? <-- this field could be omitted
     * )
     * @return  NotORM_Row  The inserted row.
     */
    public function insertNewFacebookUserRow($columns){
        $facebookUser = $this->db->FB_USER();
        $row = $facebookUser->insert($columns);
        return $row;
    }

    /**
     * Inserts a new row in the SHOPPING_LIST table.
     * @param  array  $columns  An array containing allowed values in the SHOPPING_LIST table.
     * e.g.
     * $columns = array (
     *  "id" => ???, <-- note foreign key to CONSUMER_GROUP.id
     *  "CONTENT" => ???, <-- omissible
     *  "DATE_CREATED => ??? <-- omissible - by default new \NotORM_Literal("CURRENT_TIMESTAMP")
     * )
     * @return  NotORM_Row  The inserted row.
     */
    public function insertNewShoppingListRow($columns){
        $shoppingList = $this->db->SHOPPING_LIST();
        $row = $shoppingList->insert($columns);
        return $row;
    }

    /**
     * Inserts a new row in the CONSUMER_GROUP table.
     * @param  array  $columns  An array containing allowed values in the CONSUMER_GROUP table.
     * e.g.
     * $columns = array (
     *  "id" => ???, <-- omissible - by default auto incremented by db
     *  "NAME" => ???, <-- omissible
     *  "DATE_CREATED => ??? <-- omissible - by default new \NotORM_Literal("CURRENT_TIMESTAMP")
     * )
     * @return  NotORM_Row  The inserted row.
     */
    public function insertNewConsumerGroupRow($columns){
        $consumerGroup = $this->db->CONSUMER_GROUP();
        $row = $consumerGroup->insert($columns);
        return $row;
    }

    /**
     * Inserts a new row in the CG_MEMBER table.
     * @param  array  $columns  An array containing allowed values in the CG_MEMBER table.
     * e.g.
     * $columns = array (
     *  "CONSUMER_GROUP_id" => ???, <-- note foreign key to CONSUMER_GROUP.id
     *  "FB_USER_id" => ???, <-- note foreign key to FB_USER.id
     *  "OWNER => ??? <-- omissible - by default true
     * )
     * @return  NotORM_Row  The inserted row.
     */
    public function insertNewConsumerMemberRow($columns){
        // below won't work because of the id -- se removeFacebookUser()
        $consumerMember = $this->db->CG_MEMBER();
        $row = $consumerMember->insert($columns);
        return $row;
    }

    /**
     * Registers a user using the PSID the current instance is set with.
     */
    public function registerWithCurrentUserId(){
        $this->insertNewFacebookUserRow(array("id" => $this->userId));
    }

    /**
     * Setter for the column CONTENT in the SHOPPING_LIST table.
     * @param  string  $jsonContent  An associative array representing the a shopping list object.
     * Structure:
     * {
     *      "list":[
     *          {
     *              "item_name":"item name"
     *          }
     *      ]
     * }
     */
    public function setShoppingListContent($jsonContent){
        $fbUser = $this->db->FB_USER[$this->userId];
        $shoppingList=$this->db->SHOPPING_LIST[$fbUser["SELECTED_CG"]];
        //$shoppingList["CONTENT"] = json_encode($jsonContent); - no need to encode
        $shoppingList["CONTENT"] = $jsonContent;
        $shoppingList->update();
    }

    /**
     * Getter for the column CONTENT in the SHOPPING_LIST table.
     * @return  array  An associative array representing the shopping list.
     * Structure:
     * array (
     *      "id" => ???,
     *      "content" => ???,
     *      "created" => ???
     * )
     */
    public function getShoppingListContent(){
        $fbUser = $this->db->FB_USER[$this->userId];
        $shoppingList=$this->db->SHOPPING_LIST[$fbUser["SELECTED_CG"]];
        $arr = array(
            "id" => ((int)$shoppingList["id"]),
            "content" => $shoppingList["CONTENT"],
            "created" => $shoppingList["DATE_CREATED"]
        );
        return $arr;
    }

    /**
     * Appends the content of $items to  the CONTENT column of the SHOPPING_LIST table.
     * If CONTENT is empty $items replace the null at this position.
     * @param  array  $items  An array that holds the elements the user wants to add to his/her shopping list.
     */
    public function addItemsToShoppingList($items){
        $fbUser = $this->db->FB_USER[$this->userId];
        $shoppingList=$this->db->SHOPPING_LIST[$fbUser["SELECTED_CG"]];
        if($shoppingList["CONTENT"]){
            $content = json_decode($shoppingList["CONTENT"],true);
        }
        else{
            $content = array("list"=>[]);
        }

        foreach($items as $item){
            array_push($content["list"],array("item_name" => trim($item)));
        }

        $content = json_encode($content);
        
        $shoppingList["CONTENT"] = $content;
        $affected = $shoppingList->update();
    }

    /**
     * Gets the user's SELECTED_CG's shopping list.
     * @return  array  An array of items representing the user's shopping list.
     */
    public function getShoppingList(){
        $shoppingList = $this->db->SHOPPING_LIST[($this->db->FB_USER[$this->userId])["SELECTED_CG"]];
        $content = json_decode($shoppingList["CONTENT"],true);
        $list = [];
        for($i=0;$i<count($content["list"]);$i++){
            array_push($list,$content["list"][$i]["item_name"]);
        }
        return $list;
    }

    /**
     * Overwrites the content of the shopping list with $items.
     * @param  array  $items  An array that holds items to store in the shopping list.
     */
    public function amendShoppingList($items){
        $shoppingList = $this->db->SHOPPING_LIST[($this->db->FB_USER[$this->userId])["SELECTED_CG"]];
        $content = array("list"=>[]);
        foreach($items as $item){
            array_push($content["list"],array("item_name" => trim($item)));
        }

        $content = json_encode($content);
        $shoppingList["CONTENT"] = $content;
        $shoppingList->update();
    }

    /**
     * Removes the shopping list from the system.
     */
    public function removeShoppingList(){
        $shoppingList = $this->db->SHOPPING_LIST[($this->db->FB_USER[$this->userId])["SELECTED_CG"]];
        $shoppingList->delete();
    }

    /**
     * Removes user from the system.
     * @param  bool  $safeMode  True: safely delete user:
     * When a user unregisters from the system all his groups are checked.
     * If the groups where he/she has ownership privileges have other users select the first member and make them the new owner. 
     * If there are no members remove the group.
     */
    public function removeFacebookUser($safeMode){
        $fbUser = $this->db->FB_USER[$this->userId];
        if($safeMode){
            $newOwnerId = null;
            $cgNewOwner = null;
            foreach($fbUser->CG_MEMBER() as $cgMember){
                if(((bool)$cgMember["OWNER"])){
                    $groupMembers = $cgMember->CONSUMER_GROUP->CG_MEMBER();
                    if(count($groupMembers)>1){
                        $cgNewOwner = $cgMember["CONSUMER_GROUP_id"];
                        foreach($groupMembers as $member){
                            if(strcmp($member["FB_USER_id"],$this->userId)!==0){
                                $this->pdo->exec("UPDATE CG_MEMBER SET OWNER = '1' WHERE CG_MEMBER.CONSUMER_GROUP_id = ".((int)$member["CONSUMER_GROUP_id"])." AND CG_MEMBER.FB_USER_id = '".$member["FB_USER_id"]."'");
                                break;
                            }
                        }
                    }
                    else{
                        $cgMember->CONSUMER_GROUP->delete();
                    }
                }
            }
        }
        $fbUser->delete();
    }

    /**
     * Clears the shopping list of all its content.
     */
    public function clearShoppingList(){
        $shoppingList = $this->db->SHOPPING_LIST[($this->db->FB_USER[$this->userId])["SELECTED_CG"]];
        $shoppingList["CONTENT"] = null;
        $shoppingList->update();
    }

    /**
     * Renames the user's current group to $newName.
     * @param  string  $newName  The new name.
     */
    public function renameSelectedGroup($newName){
        $fbUser = $this->db->FB_USER[$this->userId];
        $consumerGroup = $this->db->CONSUMER_GROUP[$fbUser["SELECTED_CG"]];
        $consumerGroup["NAME"] = $newName;
        $consumerGroup->update();
    }

    /**
     * Removes the user's current group and sets the selected group to null.
     */
    public function removeSelectedGroup(){
        $fbUser = $this->db->FB_USER[$this->userId];
        $consumerGroup = $this->db->CONSUMER_GROUP[$fbUser["SELECTED_CG"]];
        $fbUser["SELECTED_CG"] = null;
        $fbUser->update();
        $consumerGroup->delete();
    }

    /**
     * Removes a specific consumer group from the system.
     * @param  string  $groupId  The id of a specific group.
     */
    public function removeConsumerGroup($groupId){
        $consumerGroup = $this->db->CONSUMER_GROUP[((int)$groupId)];
        if($consumerGroup)
            $consumerGroup->delete();
    }

    /**
     * Unsubscribes the user from a specific group.
     * @param  string  $groupId  The id of a specific group.
     */
    public function unsubscribeFromGroup($groupId){
        $fbUser = $this->db->FB_USER[$this->userId];
        foreach($fbUser->CG_MEMBER() as $cgMember){
            if(strcmp($cgMember["CONSUMER_GROUP_id"],$groupId)===0){
                $this->pdo->exec("DELETE FROM CG_MEMBER WHERE CG_MEMBER.CONSUMER_GROUP_id = ".((int)$groupId)." AND CG_MEMBER.FB_USER_id = '".$this->userId."'");
                if(strcmp($fbUser["SELECTED_CG"],$groupId)===0){
                    $fbUser["SELECTED_CG"]=null;
                    $fbUser->update();
                }
                break;
            }
        }
    }

    /**
     * Gets all groups the user is associated with.
     * Example:
     * groups => [{
     *  "id" => int,
     *  "owner" => bool,
     *  "name" => string,
     *  "created" => string
     * }]
     * @return  array  A list containing all groups' details or and empty list if no group is found.
     */
    public function getConsumerGroups(){
        $groups = [];
        $fbUser =  $this->db->FB_USER[$this->userId];
        foreach($fbUser->CG_MEMBER() as $cgMember){
            array_push($groups,array(
                "id" => ((int)$cgMember["CONSUMER_GROUP_id"]),
                "owner" => ((bool)$cgMember["OWNER"]),
                "name" => $cgMember->CONSUMER_GROUP["NAME"],
                "created" => $cgMember->CONSUMER_GROUP["DATE_CREATED"]
            ));
        }
        return $groups;
    }

    /**
     * Get all members that are part of a specific consumer group.
     * @param  string  $groupId  The id of the group.
     * @return  array  A list containing all the members in the group. The array is empty if the row doesn't exist.
     */
    public function getMembersByConsumerGroupId($groupId){
        $consumerGroup = $this->db->CONSUMER_GROUP[((int)$groupId)];
        $members = [];
        if($consumerGroup){
            foreach($consumerGroup->CG_MEMBER() as $cgMember){
                array_push($members, array(
                    "psid" => $cgMember["FB_USER_id"],
                    "owner" => ((bool)$cgMember["OWNER"])
                ));
            }
        }
        return $members;
    }

    /**
     * Gets the user's current group id.
     * @return  int  The current group's id.
     */
    public function getCurrentConsumerGroupId(){
        $fbUser =  $this->db->FB_USER[$this->userId];
        return ((int)$fbUser["SELECTED_CG"]);
    }

    /**
     * Sets a group to the user using the group id.
     * @param  int  $cgId  The id of the group.
     */
    public function setSelectedConsumerGroup($cgId){
        $fbUser =  $this->db->FB_USER[$this->userId];
        $fbUser["SELECTED_CG"] = $cgId;
        $fbUser->update();
    }

    /**
     * Tells whether a group exists on the system or not.
     * @param  string  $groupId  The if of the group.
     * @return  bool  True: group exists; false: group does not exists.
     */
    public function doesGroupExists($groupId){
        $group = $this->db->CONSUMER_GROUP[((int)$groupId)];
        return $group !== null;
    }

    /**
     * Tells whether a user is still on the system (registered).
     * @param  string  $psid  The psid of the user.
     * @return  bool  True: the user is registered; false: the user is not registered.
     */
    public function doesUserExists($psid){
        $user = $this->db->FB_USER[$psid];
        return $user !==null;
    }

    /**
     * Sets a user as the new owner of a specific group. The old owner loses all ownership privileges.
     * @param  string  $groupId  The id of a group.
     * @param  string  $psid  The id of the new owner.
     */
    public function setNewOwner($groupId,$psid){
        $query_res = $this->pdo->exec("UPDATE CG_MEMBER SET OWNER = '1' WHERE CG_MEMBER.CONSUMER_GROUP_id = ".((int)$groupId)." AND CG_MEMBER.FB_USER_id = '".$psid."'");
        //pdo::exec return value php manual.
        if(!($query_res==false)){
            if($query_res>0){// actually affected rows
                $this->pdo->exec("UPDATE CG_MEMBER SET OWNER = '0' WHERE CG_MEMBER.CONSUMER_GROUP_id = ".((int)$groupId)." AND CG_MEMBER.FB_USER_id = '".$this->userId."'");                
            }
        }
    }

    /**
     * Kicks user out of a specific group.
     * @param  string  $groupId  The id of the group.
     * @param  string  $psid  The id of the user to kick
     */
    public function kickUserOut($groupId,$psid){
        $query_res = $this->pdo->exec("DELETE FROM CG_MEMBER WHERE CG_MEMBER.CONSUMER_GROUP_id = ".((int)$groupId)." AND CG_MEMBER.FB_USER_id = '".$psid."'");
        //pdo::exec retunn value php manual.
        if(!($query_res==false)){
            if($query_res>0){
                $fbUser = $this->db->FB_USER[$psid];
                if($fbUser){
                    if(strcmp($fbUser['SELECTED_CG'],$groupId)===0){
                        $fbUser["SELECTED_CG"] = null;
                        $fbUser->update();
                    }
                }
            }
        }
    }
}

?>