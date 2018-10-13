<?php
namespace apis\slpa;
use \apis\messenger;

include_once "SlpaCore.php";
include_once "SlpaCrudAdapter.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerAdapter.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerApi.php";
include_once $_SERVER['DOCUMENT_ROOT']."/external_includes/config.php";
/**
 * SlpaCoreAdapter class.
 * This class is SlpaCore's Adapter.
 * Roles will use this class and its methods to process their operations.
 * @author ni15aaf
 */

 class SlpaCoreAdapter implements SlpaCrudAdapter
 {
     
    private $slpaCoreApi;

    /**
     * Constructor
     * @param  SlpaCore  $sca  An instance of the API needed to perform calls.
     */
    public function __construct(SlpaCore $sca){
        $this->slpaCoreApi = $sca;
    }

    /**
     * Builds a string which represent the command to use to amend the entire shopping list.
     * @return  mixed  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Missing shopping list.
     * 3: Empty shopping list.
     * string: Command to amend the whole list. E.g. "!<command> item1, item2, item2".
     */
    public function editWholeShoppingList(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    if(!($this->slpaCoreApi->isCurrentShoppingListEmpty())){
                        $items = $this->slpaCoreApi->getShoppingList();
                        $command = "!amlist";
                        for($i=0;$i<count($items);$i++){
                            $command.=" ".$items[$i].",";
                        }
                        return substr($command,0,strlen($command)-1);
                    }
                    else{
                        return 3;
                    }
                }
                else{
                    return 2;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
        
    }

    /**
     * Prepares a structure to display all the groups associated with the user in a manner that allows additional features.
     * @return  mixed  returns:
     * 0: User not registered.
     * 5: No groups present.
     * array: Json object representing the groups with eventual functional buttons.
     */
    public function showGroups(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->hasGroups()){
                $groups = $this->slpaCoreApi->getConsumerGroups();
                $elements = [];
                foreach($groups as $group){
                    $selectedCg = $this->slpaCoreApi->getCurrentConsumerGroupId();
                    $element = array(
                        "title" => $group["name"],
                        "image_url" => "https://23b064a7.ngrok.io/slpa/img/groupicon.png",
                        "buttons" => [
                            array(
                                "type" => "postback",
                                "title" => ($group["owner"] ? "Remove":"Leave"),
                                "payload" => "SLPA_BTN_GroupManipulator_".($group["owner"] ? "removeGroup":"leaveGroup")."_".$group["id"]
                            ),
                            array(
                                "type" => "postback",
                                "title" => "Get invitation",
                                "payload" => "SLPA_BTN_GroupManipulator_createInvitationByGroupId_".$group["id"]
                            ),
                            array(
                                "type" => "postback",
                                "title" => "Show members",
                                "payload" => "SLPA_BTN_GroupManipulator_showGroupMembers_".$group["id"]
                            ),
                        ]
                    );
                    if($selectedCg!==$group["id"]){
                        $element["subtitle"] = "Tap to select this group.";
                        $element["default_action"] = array(
                            "type" => "web_url",
                            "url" => "https://23b064a7.ngrok.io/slpa/slpa_core/webview_docs/changeGroup.php?psid=".$this->slpaCoreApi->getUserId()."&cg=".$group["id"],
                            "webview_height_ratio" => "compact",
                            "messenger_extensions" => true,
                            "webview_share_button" => "hide"
                        );
                    }

                    array_push($elements,$element);
                }
                return $elements;
            }
            else{
                return 5;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Removes a specific group.
     * @param  string  $groupId  The group id.
     */
    public function removeGroup($groupId){
        $this->slpaCoreApi->removeConsumerGroup($groupId);
    }

    /**
     * Unsubscribes the user from a specific group.
     * @param  string  $groupId  The group id.
     */
    public function leaveGroup($groupId){
        $this->slpaCoreApi->unsubscribeFromGroup($groupId);
    }
    
    /**
     * Tells whether the invitation message can be created to share with others.
     * @return  bool  True: possible; false: impossible.
     */
    public function canGenerateGroupInvitation($groupId){
        return $this->slpaCoreApi->canGroupHaveMoreMembers($groupId);
    }

    /**
     * Joins the user to a specific group.
     * @param  string  $groupId  The group id.
     * @return  int  returns:
     * 0: User not registered.
     * 6: User can't join additional groups.
     * 7: Nonexistent group.
     * 8: Group can't have additional members.
     * 10: Success!
     */
    public function joinGroup($groupId){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->canUserJoinMoreGroups($this->slpaCoreApi->getUserId())){
                if($this->slpaCoreApi->doesGroupExists($groupId)){
                    if($this->slpaCoreApi->canGroupHaveMoreMembers($groupId)){
                        $columns = array(
                            "CONSUMER_GROUP_id" => ((int)$groupId),
                            "FB_USER_id" => $this->slpaCoreApi->getUserId(),
                            "OWNER" => false
                        );
                        $this->slpaCoreApi->insertNewConsumerMemberRow($columns);
                        $this->slpaCoreApi->setSelectedConsumerGroup($groupId);
                        return 10;
                    }
                    else{
                        return 8;
                    }
                }
                else{
                    return 7;
                }
            }
            else{
                return 6;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Prepares a structure to display all the members associated with a specific group.
     * @param  string  $groupId  The group id.
     * @return  mixed  returns:
     * 6: Group no longer exists.
     * array: Json object representing the users with eventual functional buttons.
     */
    public function showGroupMembers($groupId){
        $users = $this->slpaCoreApi->getMembersByConsumerGroupId($groupId);
        if(count($users)<1){
            return 6;
        }
        else{
            $elements=[];
            $fbAdapter = new messenger\FacebookMessengerAdapter(new messenger\FacebookMessengerApi(\ACCESS_TOKEN));
            foreach($users as $user){
                $fbUserObj = $fbAdapter->getUserData($user["psid"]);
                $element = array(
                    "title" => $fbUserObj["first_name"]." ".$fbUserObj["last_name"],
                    "subtitle" => ($user["owner"] ? "Owner":"Member"),
                    "image_url" => $fbUserObj["profile_pic"],                          
                );
                if($this->slpaCoreApi->isUserGroupOwnerById($groupId)){
                    if($this->slpaCoreApi->getUserId()!==$user["psid"])
                    $element["buttons"] = [
                        array(
                            "type" => "postback",
                            "title" => "Make owner",
                            "payload" => "SLPA_BTN_GroupManipulator_makeOwner_".$groupId."_".$user["psid"]
                        ),
                        array(
                            "type" => "postback",
                            "title" => "Kick out",
                            "payload" => "SLPA_BTN_GroupManipulator_kickOut_".$groupId."_".$user["psid"]
                        )
                    ];
                }
                array_push($elements,$element);
            }
            return $elements;
        }
    }

    /**
     * Allows the current user to elect another user as a group new owner.
     * @param  string  $groupId  The group id.
     * @param  string  $othersPsid  The new owner's psid.
     * @return  int  returns:
     * 0: User not registered.
     * 9: User can't be owner of an additional group.
     * 10: Success!
     */
    public function makeGroupOwner($groupId,$othersPsid){
        if($this->slpaCoreApi->doesUserExists($othersPsid)){
            if($this->slpaCoreApi->canUserOwnMoreGroups($othersPsid)){
                $this->slpaCoreApi->setNewOwner($groupId,$othersPsid);
                return 10;
            }
            else{
                return 9;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Kicks a user out of a group.
     * @param  string  $groupId  The group id.
     * @param  string  $othersPsid  The new owner's psid.
     */
    public function kickUserOut($groupId,$othersPsid){
        $this->slpaCoreApi->kickUserOut($groupId,$othersPsid);
    }

    /**
     * Renames the user's group.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 4: Insufficient credentials to rename.
     * 5: Success!
     */
    public function renameGroup($name){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isUserGroupOwner()){
                    $this->slpaCoreApi->renameSelectedGroup($name);
                    return 5;
                }
                else{
                    return 4;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Creates a generic group.
     * @return  int  returns:
     * 0: User not registered.
     * 9: User can't be owner of an additional group.
     * 10: Success!
     */
    public function createGroup(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->canUserOwnMoreGroups($this->slpaCoreApi->getUserId())){
                $row = $this->slpaCoreApi->insertNewConsumerGroupRow(array());
                $this->slpaCoreApi->setSelectedConsumerGroup(((int)$row["id"]));
                $this->slpaCoreApi->insertNewConsumerMemberRow(
                    array(
                        "CONSUMER_GROUP_id" => ((int)$row["id"]),
                        "FB_USER_id" => ((string)$this->slpaCoreApi->getUserId())
                    )
                );
                return 10;
            }
            else
                return 9;
        }
        else
            return 0;
    }

    /**
     * Gets the database's shopping list entity.
     * @return  mixed  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Missing shopping list.
     * 3: Empty shopping list.
     * array: Shopping list and all relating attributes.
     * Structure:
     * array (
     *      "id" => ???,
     *      "content" => ???,
     *      "created" => ???
     * )
     */
    public function getShoppingListObj(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    if(!($this->slpaCoreApi->isCurrentShoppingListEmpty())){
                        return $this->slpaCoreApi->getShoppingListContent();
                    }
                    else{
                        return 3;
                    }
                }
                else{
                    return 2;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Registers a person to the System.
     * @return  int  returns:
     * 0: User already registered.
     * 1: Success!
     */
    public function registerUser(){
        if(!($this->slpaCoreApi->isUserRegistered())){
            $this->slpaCoreApi->registerWithCurrentUserId();
            return 1;
        }
        return 0;
    }

    /**
     * Unregisters a person from the System.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Success!
     */
    public function unregisterUser(){
        if($this->slpaCoreApi->isUserRegistered()){
            $this->slpaCoreApi->removeFacebookUser(true);
            return 1;
        }
        return 0;
    }

    /**
     * Appends the content of $items to the shopping list.
     * If the list does not exist, it is created.
     * @param  array  $items  An array that holds the items the user wants to add to his/her shopping list.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Success!
     */
    public function addItemsToShoppingList($items){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if(!($this->slpaCoreApi->isShoppingListPresent())){
                    $this->slpaCoreApi->insertNewShoppingListRow(
                        array(
                            "id" => $this->slpaCoreApi->getCurrentConsumerGroupId()
                        )
                    );
                }
                $this->slpaCoreApi->addItemsToShoppingList($items);
                return 2;
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Provides a string which represent a human readable version of the shopping list.
     * @return  mixed  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Missing shopping list.
     * 3: Empty shopping list.
     * string: Human readable shopping list in number points**".
     */
    public function showShoppingList(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    if(!($this->slpaCoreApi->isCurrentShoppingListEmpty())){
                        $items = $this->slpaCoreApi->getShoppingList();
                        $list = "";
                        for($i=0;$i<count($items);$i++){
                            $list.= ($i+1)." ) ".$items[$i]."\n";
                        }
                        return $list;
                    }
                    else{
                        return 3;
                    }
                }
                else{
                    return 2;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Edits the shopping list with the contents of $item.
     * @param  array  $items  An array that holds the items.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Missing shopping list.
     * 3: Success!
     */
    public function editTheShoppingList($items){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    $this->slpaCoreApi->amendShoppingList($items);
                    return 3;
                }
                else{
                    return 2;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Removes the shopping list from the system.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Success!
     */
    public function removeTheShoppingList(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    $this->slpaCoreApi->removeShoppingList();
                }
                return 2;
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }

    /**
     * Clears the shopping list.
     * @param  array  $items  An array that holds the items.
     * @return  int  returns:
     * 0: User not registered.
     * 1: Invalid selected consumer group.
     * 2: Missing shopping list.
     * 3: Success!
     */
    public function clearTheShoppingList(){
        if($this->slpaCoreApi->isUserRegistered()){
            if($this->slpaCoreApi->isSelectedCustomerGroupSet()){
                if($this->slpaCoreApi->isShoppingListPresent()){
                    if(!($this->slpaCoreApi->isCurrentShoppingListEmpty())){
                        $this->slpaCoreApi->clearShoppingList();
                    }
                    return 3;
                }
                else{
                    return 2;
                }
            }
            else{
                return 1;
            }
        }
        else{
            return 0;
        }
    }
 }

?>