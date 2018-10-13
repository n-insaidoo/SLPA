<?php
namespace slpa_core;
use \apis\slpa;
use \apis\messenger;
use \apis\geolocation;

include_once "RequestRole.php";
include_once "Request.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerAdapter.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/messenger/FacebookMessengerApi.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/geolocation/GoogleMapsAdapter.php";
include_once $_SERVER['DOCUMENT_ROOT']."/slpa/apis/geolocation/GoogleMapsApi.php";
include_once $_SERVER['DOCUMENT_ROOT']."/external_includes/config.php";

/**
 * LocationBasedService class.
 * This class represents one of the Roles that a Request can play.
 * It inherits from RequestRole.
 * All services based on the user's location are managed here.
 * 
 * @author ni15aaf
 */

 class LocationBasedService extends RequestRole
 {
     /*defining the constant that will keep track of all the commands available in this role class*/
    const COMMANDS = [];

    /*defininng the role title constant*/
    const ROLETITLE = "LocationBasedService";

    private $googleMapsApi;

    /**
     * Constructor
     * @param  Request  $player  A Request object representing the request that plays a role.
     * @param  bool  $commandFlag  A flag that tells whether the current Role instance was created from a command or not.
     * True if it was created due to a command, false otherwise.
     */
    public function __construct(Request $player, $commandFlag){
        parent::__construct($player,$commandFlag);
        $this->googleMapsApi = new geolocation\GoogleMapsAdapter(new geolocation\GoogleMapsApi(\GOOGLE_API_KEY));
    }

    //@Override
    /**
     * Checks whether $roleTitle corresponds to the role's title.
     * @param  string  $roleTitle  A string representing a role's title e.g. "LocationBasedService" in this case.
     * @return  bool  True if the role titles match, false otherwise.
     */
    public function isRole($roleTitle){
        if(strcmp($this::ROLETITLE,$roleTitle)===0) return true;
        return false;
    }

    /**
     * Requests the user's geolocation details through a quick reply message.
     * @return  string  Empty string.
     */
    public function getUserLocation(){
        $fbApi = new messenger\FacebookMessengerAdapter(new messenger\FacebookMessengerApi(\ACCESS_TOKEN));
        $fbApi->sendQuickReply($this->getRequest()->getPsid(),"Please share your location:",[
            array(
                "content_type" => "location",
                "title" => "Send Location"
            )
        ]);
        return "";
    }

    /**
     * Returns the nearest grocery stores based on the user's location.
     * @param  array  $coordinates  Coordinates. Latitude/longitude.
     * @return  string  Eventual error messages or empty string.
     */
    public function findNearestGroceryStores($coordinates){
        $result = $this->googleMapsApi->addressLookUp(((float)$coordinates["lat"]),((float)$coordinates["long"]));
        if(is_string($result)){
            return $result;
        }
        else{
            //Get the most accurate address from the result - index 0
            //Use Maps URLs to query the nearby groceries store for the address
            $gUrl = "https://www.google.com/maps/search/?api=1&query=grocery stores near ".$result[0]["formatted_address"];
            //$gUrl ="http://maps.apple.com/maps?q=grocery stores near ".$result[0]["formatted_address"];
            $fbApi = new messenger\FacebookMessengerAdapter(new messenger\FacebookMessengerApi(\ACCESS_TOKEN));
            $fbApi->sendMessageWithButtons($this->getRequest()->getPsid(),"Here's what was found",[
                array(
                    "type" => "web_url",
                    "title" => "View",
                    "url" => $gUrl,
                    "messenger_extensions" => false,
                    "webview_share_button" => "hide"
                )
            ]);
            return "";
        }
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
            if($this->getRequest()->getLocation()===null){
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
            else{
                $coordinates = $this->getRequest()->getLocation();
                $this->findNearestGroceryStores($coordinates);
            }
        }
        return $result;
    }
 }

?>