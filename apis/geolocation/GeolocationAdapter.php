<?php
namespace apis\geolocation;

/**
 * GeolocationAdapter interface.
 * This class is the Adapter interface for GoogleMapsApi.
 * It defines the methods that the inheriting Adapter has to implement.
 * 
 * @author ni15aaf
 */

interface GeolocationAdapter
{
    public function addressLookUp($latitude,$longitude);
}

?>