<?php
namespace apis\geolocation;

include_once "GoogleMapsApi.php";
include_once "GeolocationAdapter.php";

/**
 * GoogleMapsAdapter class.
 * This class is GoogleMapsApi's Adapter.
 * The LocationBasedService role uses this class to precess its operations.
 * 
 * @author ni15aaf
 */

class GoogleMapsAdapter implements GeolocationAdapter
{
    private $googleMapsApi;

    /**
     * Constructor
     * @param  GoogleMapsApi  $gma  An instance of the API object needed to perform calls.
     */
    public function __construct(GoogleMapsApi $gma){
        $this->googleMapsApi = $gma;
    }

    /**
     * Looks up an address through a given latitude/longitude.
     * @param  float  $latitude  Latitude.
     * @param  float  $longitude  Longitude.
     * @return  mixed  retunrs:
     * string: diagnostic messages corresponding to API's STATUS CODES.
     * array: An array of geocoded address information and geometry information.
     */
    public function addressLookUp($latitude,$longitude){
        $response = $this->googleMapsApi->reverseGeocoding($latitude,$longitude);
        $status = $response["status"];
        switch($status){
            case "OK": 
            return $response["results"];
            break;
            case "ZERO_RESULTS":
            return "No result available for your current location.";
            break;
            case "OVER_QUERY_LIMIT":
            return "Request limit reached.";
            break;
            case "REQUEST_DENIED":
            return "An error occurred. Your request was denied.";
            break;
            case "INVALID_REQUEST":
            return "Invalid request. Check your parameters.";
            break;
            case "UNKNOWN_ERROR":
            return "A server error has occurred. You may consider repeating the operation.";
            break;
        }
    }
}

?>