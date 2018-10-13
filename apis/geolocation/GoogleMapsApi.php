<?php
namespace apis\geolocation;

//https://developers.google.com/maps/documentation/geocoding/start -- for more functionalities to implement in the future.
//https://developers.google.com/maps/documentation/static-maps/intro -- static maps


/**
 * GoogleMapsApi class.
 * This class is GoogleMapsAdapter's Adaptee. 
 * It takes care of handling all requests to Google Maps.
 * 
 * @author ni15aaf
 */

 class GoogleMapsApi
 {

    /*defining constants for the API urls*/
    private const GEOCODEAPIURL = "https://maps.googleapis.com/maps/api/geocode/json?";

    private $apiKey;

    /**
     * Constructor
     * @param  string  $apiKey  A string representing the API key to needed to authenticate requests.
     */
    public function __construct($apiKey){
        $this->apiKey = $apiKey;
    }

    /**
     * Internal function used to perform POST requests.
     * @param  string  $url  The url to use for the request.
     * @param  array  $requestBody  The associative array representing the JSON body of the request.
     */
    private function performPostRequest($url,$requestBody){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_exec($ch); 
        curl_close($ch);
    }
    
    /**
     * Internal function used to perform GET requests.
     * @param  string  $url  The url to use for the request.
     * @return  string  A string representing the response.
     */
    private function performGetRequest($url){
		return file_get_contents($url);
    }

    /**
     * Requests the address corresponding to a given latitude/longitude.
     * @param  float  $lat  Latitude.
     * @param  float  $lon  Longitude.
     * @return  array  JSON object representing the response.
     */
    public function reverseGeocoding($lat,$lon){
        //https://developers.google.com/maps/documentation/geocoding/intro#Results -- for reference e.g. params and status codes.
        $requestUrl = $this::GEOCODEAPIURL."latlng=$lat,$lon&key=".$this->apiKey;
        $response = $this->performGetRequest($requestUrl);
        $resultArray = json_decode($response,true);
        return $resultArray;

    }
 }
?>