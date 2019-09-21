<?php

// created by http://bit.ly/GerhGithub
// 2019

//show all errors
//ini_set('display_errors', 1); 
//ini_set('display_startup_errors', 1); 
//error_reporting(E_ALL); 

function showMessage($number, $message){
	echo $message;
	http_response_code($number);
}

function urlToXpathPart($query, $symbol, $query_parts, $i){
	$query = str_replace($query_parts[$i],$query_parts[$i]."]",$query);
			
	$queryPart = explode($query_parts[$i],$query)[0];
	$pos = strpos($query, '/', strlen($queryPart)-1);
	$query[$pos] = $symbol;
	
	return $query;
}

//convert an url to an XPath query
function urlToXpath($query, $query_parts, $isGet) {
	foreach(range(0,sizeof($query_parts)-2+$isGet) as $i){
		
		if (is_numeric($query_parts[$i]) or preg_match('/^%22.*%22$/', $query_parts[$i])){
			$query = urlToXpathPart($query, "=", $query_parts, $i);
			
			$queryPart = explode($query_parts[$i-1],$query)[0];
			$pos = strpos($query, '/', strlen($queryPart)-1);
			$query[$pos] = "[";
		}
		else if(preg_match('/.*%3E.*/', $query_parts[$i]))
			$query = urlToXpathPart($query, "[", $query_parts, $i);
		else if(preg_match('/.*%3C.*/', $query_parts[$i]))
			$query = urlToXpathPart($query, "[", $query_parts, $i);
	}
	$query = str_replace("%22","\"",$query);
	$query = str_replace("%20"," ",$query);
	$query = str_replace("%3E",">",$query);
	$query = str_replace("%3C","<",$query);
	
    return $query;
}

//show available services
function showAvailableUrls($base_url) {
	$returnStr = "<h1>Available Services: </h1><br>";
	$files = glob("*xml");
	if (is_array($files)) {
		foreach($files as $xml_file) {
			//get url
			$current_url = $base_url . ".php/" . substr($xml_file, 0, -4);
			try{
				//get root
				$xml = simplexml_load_file($xml_file);
				$root_name = $xml->getName();
				$current_url .= "/" . $root_name;
				$returnStr .= "<a href=\"" . $current_url . "\">" . $current_url . "</a><br>";
			} catch(Exception $e) {
				$returnStr .= $current_url . " (file corrupted)<br>";
			}
		}
	}
	showMessage(400, $returnStr);
	exit();
}

$method = $_SERVER['REQUEST_METHOD'];

//get Query from the url
$user_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

//user can have a slash '/' at the end of the url; remove for XPath query.
if ($user_url[-1] == '/')
	$user_url = substr($user_url, 0, -1);
	
$url_parts = explode(".php",$user_url); 
$query = $url_parts[1];
$query = str_replace("attr/","@",$query);
$query_parts = explode("/",$query);			
if( isset( $query_parts[1] ))
	$xml_file = $query_parts[1] . ".xml"; 
else 
	showAvailableUrls($url_parts[0]);

$query = substr($query, strlen($xml_file)-3, strlen($query));

//show all xml content
if($query === "/" or $query === "")
	$query = "/*";

switch ($method) {
case 'GET':	
	try{
		//part after the last slash in the url - check if function
		$last_part = end($query_parts);
		switch ($last_part){
			case "sum()":
				$query = substr($query, 0, -6);
				$funcFilter = "sum";
				break;
			case "count()":
				$query = substr($query, 0, -8);
				$funcFilter = "count";
				break;
			case "avg()":
				$query = substr($query, 0, -6);
				$funcFilter = "avg";
				break;
			case "min()":
				$query = substr($query, 0, -6);
				$funcFilter = "min";
				break;
			case "max()":
				$query = substr($query, 0, -6);
				$funcFilter = "max";
				break;
			default:
				$funcFilter = "none";
		}
		
		//show a specific resource, perform larger/smaller than operations
		$query = urlToXpath($query, $query_parts, 1);
		
		//query -> xml
		$xml = simplexml_load_file($xml_file); 
		if($xml == false)
			showAvailableUrls($url_parts[0]);
		$result = $xml -> xpath($query);
		
		//Perform sum/count/avg/min/max operations
		switch ($funcFilter){
			case "sum":
				$resultSum = 0.0;
				foreach( $result as $resultPart ){
					$resultSum+=(float)$resultPart[0][0];
				}
				$result = array("sum" => $resultSum);
				break;
			case "count":
				$count=0;
				foreach( $result as $resultPart ){
					$count += 1;
				}
				$result = array("count" => $count);
				break;
			case "avg":
				$resultSum = 0.0;
				$count=0;
				foreach( $result as $resultPart ){
					$resultSum+=(float)$resultPart[0][0];
					$count += 1;
				}
				$avg = $resultSum / $count;
				$result = array("avg" => $avg);
				break;
			case "min":
				$minArray = array();
				foreach( $result as $resultPart ){
					array_push($minArray, (float)$resultPart[0][0]);
				}
				$result = array("min" => min($minArray));
				break;
			case "max":
				$maxArray = array();
				foreach( $result as $resultPart ){
					array_push($maxArray, (float)$resultPart[0][0]);
				}
				$result = array("max" => max($maxArray));
				break;
		}
		
		//output the result
		header('content-type: application/json');
		print_r(json_encode($result));
		exit(0);
		break;
	} catch(Exception $e) {
		showMessage(400, "Status: 400 (Bad Request): " . $e->getMessage());
	}
	break;
	
case 'PUT': 
	try{
		$data = json_decode(file_get_contents('php://input'), true);
		$api_key = $data["API-key"]; 
		if($api_key !== "CHANGE-THIS-PUT-API-KEY"){
			showMessage(401, "Status: 401 (Unauthorized): " . $e->getMessage());
		}
		else{
			//transform url to XPath query
			$query = str_replace("attr/","@",$query);
			$query_parts = explode("/",$query);
			$value = end($query_parts);
			$query = substr($query, 0, -1 * strlen($value) -1); //last -1 because of /
			$query = urlToXpath($query, $query_parts, 0);
			
			//choose correct xml file
			$doc = new DOMDocument;
			$doc->load($xml_file);
			
			//change value by XPath search
			$xpath = new DOMXpath($doc);
			foreach($xpath->query($query) as $node) {
				$node->nodeValue = $value;
			}
			
			//save xml as string and remove empty lines (incl. whitespace & tab)
			$resultXmlStr = $doc->savexml();
			$resultXmlStr = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $resultXmlStr);
			
			file_put_contents($xml_file, $resultXmlStr);
		}
	} catch(Exception $e) {
		showMessage(400, "Status: 400 (Bad Request): " . $e->getMessage());
	}
	break;
	
case 'POST':
	try{
		$data = json_decode(file_get_contents('php://input'), true);
		
		$api_key = $data["API-key"]; 
		if($api_key !== "CHANGE-THIS-POST-API-KEY"){
			showMessage(401, "Status: 401 (Unauthorized): " . $e->getMessage());
		}
		else{
			$xml = simplexml_load_file($xml_file);
			$root_name = $xml->getName();
			
			$some_id = explode("/object/",$query)[1];
			//$query = substr($query, 0, 15);
			
			#post xml object
			if(is_numeric($some_id)){
				//get values
				$some_attribute=$data["some_attribute"];  
				$some_value=$data["some_value"];  
			
				$xml_str = $xml->asXML();
				
				//check if object id exists already
				$count = 0;
				foreach( $xml->xpath("//object[id='" . $some_id . "']") as $t ) {
					$count+=1;
				}
				if($count > 0){
					showMessage(400, "Status: 400 (Bad Request): This id is already taken, try again with a different id!");
				}
				else{

					$newXmlConstruct = 
"	<object>
		<id>" . $some_id . "</id>
		<element attr=\"" . $some_attribute . "\">" . $some_value . "</element>
	</object>
</" . $root_name . ">";

					//add to the xml file
					$new_xml_str = str_replace("</" . $root_name . ">", $newXmlConstruct, $xml_str);
					
					file_put_contents($xml_file, $new_xml_str);
				}
			} #file upload
			else{
				try{
					//get values
					$filename=$data["filename"];  
					$content=$data["content"]; 
					
					$dirname = pathinfo($filename, PATHINFO_DIRNAME); 
					if ($dirname!="" and !file_exists($dirname)) {
						mkdir($dirname, 0777, true);
					}
					
					file_put_contents($filename, $content);
					
				} catch(Exception $e) {
					showMessage(400, "Status: 400 (Bad Request): " . $e->getMessage());
				}
			}
		}
	} catch(Exception $e) {
		showMessage(400, "Status: 400 (Bad Request): " . $e->getMessage());
	}
	break;
	
case 'DELETE':
	try{
		//delete file
		$data = json_decode(file_get_contents('php://input'), true);
		
		$api_key = $data["API-key"]; 
		if($api_key !== "CHANGE-THIS-DELETE-API-KEY"){
			showMessage(401, "Status: 401 (Unauthorized): " . $e->getMessage());
		}
		else{
			//get values
			$file = $data["filename"]; 
			if($file != ""){
				if (file_exists($file)) {
					unlink($file);
				} else {
					showMessage(400, "Status: 400 (Bad Request): File not found.");
				}
			}
			//delete XML entry
			else{
				//transform query to XPath
				$query = str_replace("attr/","@",$query);
				$query_parts = explode("/",$query);
				$query = urlToXpath($query, $query_parts, 1);
				
				//choose correct xml file
				$doc = new DOMDocument;
				$doc->load($xml_file);
				
				//remove by XPath search
				$xpath = new DOMXpath($doc);
				foreach($xpath->query($query) as $node) {
					try{
						$node->parentNode->removeChild($node);
					}
					catch(Exception $e) {
						header('content-type: application/json');
						print_r(json_encode('Message: ' .$e->getMessage()));
						exit(0);
					}
				}
				
				//save xml as string and remove empty lines (incl. whitespace & tab)
				$resultXmlStr = $doc->savexml();
				$resultXmlStr = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $resultXmlStr);
				
				file_put_contents($xml_file, $resultXmlStr);
			}
		}
	} catch(Exception $e) {
		showMessage(400, "Status: 400 (Bad Request): " . $e->getMessage());
	}
	break;

default:
	showMessage(405, "Status: 405 (Method Not Allowed)");
}
?>