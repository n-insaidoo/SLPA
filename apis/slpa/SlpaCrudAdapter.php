<?php
namespace apis\slpa;

/**
 * SlpaCrudAdapter interface.
 * This class is the Adapter interface for SlpaCore.
 * It defines the methods that the inheriting Adapter has to implement.
 * @author ni15aaf
 */

 interface SlpaCrudAdapter
 {
     public function editWholeShoppingList();
     public function addItemsToShoppingList($items);
     public function showShoppingList();
     public function editTheShoppingList($items);
     public function removeTheShoppingList();
     public function clearTheShoppingList();
     public function getShoppingListObj();
     public function registerUser();
     public function unregisterUser();
     public function renameGroup($name);
     public function createGroup();
     public function showGroups();
     public function removeGroup($groupId);
     public function leaveGroup($groupId);
     public function showGroupMembers($groupId);
     public function makeGroupOwner($groupId,$othersPsid);
     public function kickUserOut($groupId,$othersPsid);
     public function canGenerateGroupInvitation($groupId);
     public function joinGroup($groupId);
 }

?>