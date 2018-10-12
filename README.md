# SLPA
Shopping List Personal Assistant - [Facebook Messenger Chatbot](https://developers.facebook.com/docs/messenger-platform/)

PHP framework representing a simple Chatbot aimed at managing communal shopping lists. The Chatbot operates within Facebook Messenger platform. At the current stage the latter does not operate throuh [NLP](https://en.wikipedia.org/wiki/Natural_language_processing) (Natural Language Processing), but the system is structured to easily allow this integration and many other in the future. The system only responds to a set of predefined commands and the entries on [Persistance Menu](https://developers.facebook.com/docs/messenger-platform/reference/messenger-profile-api/persistent-menu/):

```
// Commands

$elements = [
            array(
                "title" => "!add",
                "subtitle" => "Adds one item or more items to you shopping list.\n"
                ."Usage: !add item1, ..., itemN"
            ),
            array(
                "title" => "!amlist",
                "subtitle" => "Overwrites your shopping list with new items.\n"
                ."Usage: !amlist new items"
            ),
            array(
                "title" => "!clrlist",
                "subtitle" => "Clears your shopping list from its contents.\n"
                ."Usage: !clrlist"
            ),
            array(
                "title" => "!rmlist",
                "subtitle" => "Removes your shopping list from the system.\n"
                ."Usage: !rmlist"
            ),
            array(
                "title" => "!rngroup",
                "subtitle" => "Renames your current group name.\n"
                ."Usage: !rngroup new group name"
            ),
            array(
                "title" => "!showlist",
                "subtitle" => "Shows your shopping list.\n"
                ."Usage: !showlist"
            ),
        ];
        
// Persistent menu setup

// Menu 
/*
    -> Account
                    * Register
                    -> Group
                                    * Create group
                                    * Show groups
                    * Unregister                            
                    
    -> Shopping list
                    * Edit
                    * Interactive view 
                    * Nearby stores
    -> Misc
                    * Show commands

*/
```

This repository contains only part of the framework, however here follows a diagram showing its entire class composition.

![Class diagram](https://i.imgur.com/h5u2Cpj.png)
