<?php

// Functions for Plugin Statistic

// Show statistics
function showStatistics() {
  global $options;
  include($options['pluginpath']."/statistic/statistics.html.inc.php");
}

// Show question banner to accept cookies
function askQuestion() {
    global $options;
    if(!(isset($_COOKIE['allowCookies']) && $_COOKIE['allowCookies'] == "true")) {
      if(isset($_POST['enter'])) {
        if(isset($_POST['choice']) && $_POST['choice'] == "Yes") {
          setcookie("allowCookies", "true", time() + (86400 * 365));
        }
      } else {
        include($options['pluginpath']."/statistic/cookiequestion.html.inc.php");
      }
    }
  }

// Show number of website hits
function getAll() {
  global $pdo;
  $statement = $pdo->query("SELECT * FROM Statistiken");
  if ($statement) {
    foreach ($statement as $row) {
      $result[] = array('id' => $row["id"], 'timeofview'  => $row["timeofview"], 'link' => $row["link"], 'wasonsite' => $row["wasonsite"], 'country' => $row["country"], 'browsername' => $row["browsername"], 'browserversion' => $row["browserversion"], 'platform' => $row["platform"]);
    }
  }
  if(!isset($result)) {
    return 'Keine Daten';
  } else {
    return 'Aufrufe: '.count($result).'';
  }
}

// Show number of users who visited the website
function getUsers() {
  global $pdo;
  $statement = $pdo->query("SELECT * FROM Statistiken");
  if ($statement) {
    foreach ($statement as $row) {
      $result[] = array('id' => $row["id"], 'timeofview'  => $row["timeofview"], 'link' => $row["link"], 'wasonsite' => $row["wasonsite"], 'country' => $row["country"], 'browsername' => $row["browsername"], 'browserversion' => $row["browserversion"], 'platform' => $row["platform"]);
    }
  }
  $filtered = array();
	  foreach ($result as $inhalt) {
		if($inhalt["wasonsite"] == "false") {
		  array_push($filtered, $inhalt);
		}
	  }
  if(!isset($filtered)) {
    return 'Keine Daten';
  } else {
    return 'einzelne Benutzer: '.count($filtered).'';
  }
}

// Retrieve data for charts
function getStatistic($mode) {
  global $pdo;
  $querystats = [
    "browsername" => "SELECT browsername, count(*) as amount FROM Statistiken GROUP BY browsername",
    "platform" => "SELECT platform, count(*) as amount FROM Statistiken GROUP BY platform",
    "daily_clicks" => "SELECT substr(timeofview, 1, 10) as daily_clicks, count(*) AS amount FROM Statistiken GROUP BY daily_clicks;",
    "daily_new_visitors" => "SELECT substr(`timeofview`, 1, 10) AS `daily_new_visitors`, COUNT(*) AS `amount` FROM `Statistiken` WHERE `wasonsite` = 'false' GROUP BY `daily_new_visitors`"
  ];
  if(in_array($mode, array_keys($querystats))) {
    $statement = $pdo->query($querystats[$mode]);
    $result = ["labels", "data"];
    if ($statement) {
      foreach ($statement as $row) {
        $result["labels"][] = $row[$mode];
        $result["data"][] = $row["amount"];
      }
    }
    return $result;
  } else {
    return "Angegebener Mode nicht verfügbar.";
  }
}

// Add site hit to statistics table
function add($link) {
  if((isset($_COOKIE['allowCookies'])) && ($_COOKIE['allowCookies'] == "true")) {
    global $pdo;
    $statement = $pdo->prepare("INSERT INTO Statistiken (link, wasonsite, country, browsername, browserversion, platform, useragent) VALUES (:link, :wasonsite, :country, :browsername, :browserversion, :platform, :useragent)");
    $statement->execute(array('link' => $link, 'wasonsite' => checkWasOnSite(), 'country' => getUserInfo(getUserIP(), "Country"), 'browsername' => getUserBrowser()['name'], 'browserversion' => getUserBrowser()['version'], 'platform' => getUserBrowser()['platform'], 'useragent' => $_SERVER['HTTP_USER_AGENT']));
  }
}

function checkWasOnSite() {
  if(!isset($_COOKIE["wasonsite"])) {
    setcookie("wasonsite", "true", time() + (86400 * 365));
    $resultofcheck = "false";
  } else {
    $resultofcheck = "true";
  }
  return $resultofcheck;
}
function getUserIP() {
  $ip = '';
  if (getenv('HTTP_CLIENT_IP'))
      $ip = getenv('HTTP_CLIENT_IP');
  else if(getenv('HTTP_X_FORWARDED_FOR'))
      $ip = getenv('HTTP_X_FORWARDED_FOR');
  else if(getenv('HTTP_X_FORWARDED'))
      $ip = getenv('HTTP_X_FORWARDED');
  else if(getenv('HTTP_FORWARDED_FOR'))
      $ip = getenv('HTTP_FORWARDED_FOR');
  else if(getenv('HTTP_FORWARDED'))
      $ip = getenv('HTTP_FORWARDED');
  else if(getenv('REMOTE_ADDR'))
      $ip = getenv('REMOTE_ADDR');
  else
      $ip = 'UNKNOWN';
  return $ip;
}
function getUserBrowser() {
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Other';
    $platform = 'Other';
    $version = "";
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'Windows';
    }
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    } elseif(preg_match('/Firefox/i',$u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    } elseif(preg_match('/Chrome/i',$u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    } elseif(preg_match('/Safari/i',$u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    } elseif(preg_match('/Opera/i',$u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    } elseif(preg_match('/Netscape/i',$u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {

    }
    $i = count($matches['browser']);
    if ($i != 1) {
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        } else {
            $version= $matches['version'][1];
        }
    } else {
        $version= $matches['version'][0];
    }
    if ($version==null || $version=="") {
      $version="?";
    }
    return array(
        'userAgent' => $u_agent,
        'name'      => $bname,
        'version'   => $version,
        'platform'  => $platform,
        'pattern'    => $pattern
    );
}
function getUserInfo($ip = NULL, $purpose = "location", $deep_detect = TRUE) {
  $output = NULL;
  if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
    $ip = $_SERVER["REMOTE_ADDR"];
    if ($deep_detect) {
      if (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
      if (filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
  }
  $purpose = str_replace(array("name", "\n", "\t", " ", "-", "_"), NULL, strtolower(trim($purpose)));
  $support = array("country", "countrycode", "state", "region", "city", "location", "address");
  $continents = array( "AF" => "Africa", "AN" => "Antarctica", "AS" => "Asia", "EU" => "Europe", "OC" => "Australia (Oceania)", "NA" => "North America", "SA" => "South America" );
  if (filter_var($ip, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
    $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=" . $ip));
    if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
      switch ($purpose) {
        case "location":
          $output = array( "city" => @$ipdat->geoplugin_city, "state" => @$ipdat->geoplugin_regionName, "country" => @$ipdat->geoplugin_countryName, "country_code" => @$ipdat->geoplugin_countryCode, "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)], "continent_code" => @$ipdat->geoplugin_continentCode );
          break;
        case "address":
          $address = array($ipdat->geoplugin_countryName);
          if (@strlen($ipdat->geoplugin_regionName) >= 1) $address[] = $ipdat->geoplugin_regionName;
          if (@strlen($ipdat->geoplugin_city) >= 1) $address[] = $ipdat->geoplugin_city; $output = implode(", ", array_reverse($address));
          break;
        case "city":
          $output = @$ipdat->geoplugin_city;
          break;
        case "state":
          $output = @$ipdat->geoplugin_regionName;
          break;
        case "region":
          $output = @$ipdat->geoplugin_regionName;
          break;
        case "country":
          $output = @$ipdat->geoplugin_countryName;
          break;
        case "countrycode":
          $output = @$ipdat->geoplugin_countryCode;
          break;
        }
      }
    }
  return $output;
}
