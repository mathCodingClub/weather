<?php

namespace Weather;

class Weather {

  private $city = null;
  private $country = null;
  private $state = null;

  public function __construct() {
  }

  public function get($city=null, $country=null, $state=null){
    $this->city = $city;
    $this->country = $country;
    $this->state = $country;
    if (is_null($city)){

      $json_string = file_get_contents("http://api.wunderground.com/api/4ba4bf44277e7a9a/conditions/q/icao:EFTP.json"); 
      $parsed_json = json_decode($json_string);
      $current = $parsed_json->{'current_observation'};
      $location = $current->{'observation_location'}->{'city'};
      $temp = "Temp: " . $current->{'temp_c'} . " °C";
      $wind = "Wind: " . round($current->{'wind_kph'}/3.6, 2) . " m/s, Direction: " . $current->{'wind_dir'};
      $humi = "Humidity: " . $current->{'relative_humidity'};

      return "@" . $location . ": " . $temp . ", " . $wind . ", " . $humi;
    }


      $querypath = "";
      if(!is_null($country))
      {
        $querypath .= rawurlencode($country) . "/";
      }
      if(!is_null($state))
      {
        $querypath .= rawurlencode($state) . "/";
      }
      $querypath .= rawurlencode($city);

      $json_string = file_get_contents("http://api.wunderground.com/api/4ba4bf44277e7a9a/geolookup/q/$querypath.json"); 
      $parsed_json = json_decode($json_string);

      if(isset($parsed_json->{'response'}->{'results'}))
      {
        return $this->which_city($parsed_json);
      }
      else if(isset($parsed_json->{'response'}->{'error'}))
      {
        return "No data available for " . $city . ", $country $state";
      }
      else
      {
        $station_id = "";
        foreach($parsed_json->{'location'}->{'nearby_weather_stations'}->{'airport'}->{'station'} as $station)
        {
           if($station->{'icao'} !== "")
           {
             $station_id = "icao:" . $station->{'icao'};
             break;
           }
           else
           {
             foreach($parsed_json->{'location'}->{'nearby_weather_stations'}->{'pws'}->{'station'} as $station)
             {

               $station_id = "pws:" . $station->{'id'};
               break;
             }
             break;
           }
        }

        if($station_id === "")
        {

          return "No data available for " . $city . ", $country, $state";

        }
        else
        {
          $json_string = file_get_contents("http://api.wunderground.com/api/4ba4bf44277e7a9a/conditions/q/$station_id.json"); 
          $parsed_json = json_decode($json_string);

          if(isset($parsed_json->{'response'}->{'error'}))
          {
            return "No data available for " . $city . ", $country $state";
          }
          else
          {
            $current = $parsed_json->{'current_observation'};
            $location = $current->{'observation_location'}->{'city'};
            $temp = "Temp: " . $current->{'temp_c'} . " °C";
            $wind = "Wind: " . round($current->{'wind_kph'}/3.6, 2) . " m/s, Direction: " . $current->{'wind_dir'};
            $humi = "Humidity: " . $current->{'relative_humidity'};

            return "@" . $location . ": " . $temp . ", " . $wind . ", " . $humi;
          }
        }
      }
  }

  private function which_city($parsed_json)
  {
    $choices = "Which $this->city? (!saa $this->city country state): ";
    foreach($parsed_json->{'response'}->{'results'} as $result)
    {
      $choices .= trim("\"" . $result->{'country_name'} . " " . $result->{'state'}) . "\"" . ", ";
    }

    return $choices;
  }
}



?>
