<?php
set_time_limit(300);
ini_set("memory_limit","2048M");
error_reporting(E_ALL ^E_NOTICE ^E_WARNING);

class CHB{
    var $xml;
    var $csvHeader, $csvData;

    function CHB($folder, $towns){
        
        $url = $this->findLastUrl($folder);
        
        $this->unzip($url);   
        $this->data = str_replace(Array("<ns1:", "</ns1:", "<d:", "</d:"),  Array("<","</","<","</"), $this->data);
        $this->xml = new SimpleXMLElement($this->data);
        
        $this->csvHeader = [];
        $this->csvData = [];
        $this->csvRow = [];
        
        $this->towns = $towns;
    }
    
    function findLastUrl($folder){
        $html = file_get_contents($folder);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $links = $doc->getElementsByTagName("a");
        foreach($links as $link){
            if(substr($link->nodeValue,0,9) == "ExportCHB"){
                return $folder . $link->nodeValue;
            }
        }
        exit("Kon geen laatste CHB bestand vinden in ". $folder);
    }
    
    function unzip($url){
        $parts = explode("/", $url);
        $fname = end($parts);
        $dest = "../data/". str_replace(".gz","",$fname);
        $buffer_size = 4096; // read 4kb at a time

        // Open our files (in binary mode)
        $file = gzopen($url, 'rb');
        $out_file = fopen($dest, 'wb'); 
        
        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);        

        $this->data = file_get_contents($dest);      
        $this->xmlfileurl = $dest;  
    }
    
    function RD2WGS84($x, $y){
        /* Conversie van Rijksdriehoeksmeting naar latitude en longitude (WGS84)
        Voorbeeld: Station Utrecht    
        x = 136013;
        y = 455723;
        */

        $dX = ($x - 155000) * pow(10,-5);
        $dY = ($y - 463000) * pow(10,-5);

        $SomN = (3235.65389 * $dY) + (-32.58297 * pow($dX,2)) + (-0.2475 * pow($dY,2)) + (-0.84978 * pow($dX,2) * $dY) + (-0.0655 * pow($dY,3)) + (-0.01709 * pow($dX,2) * pow($dY,2)) + (-0.00738 * $dX) + (0.0053 * pow($dX,4)) + (-0.00039 * pow($dX,2) * pow($dY,3)) + (0.00033 * pow($dX,4) * $dY) + (-0.00012 * $dX * $dY);
        $SomE = (5260.52916 * $dX) + (105.94684 * $dX * $dY) + (2.45656 * $dX * pow($dY,2)) + (-0.81885 * pow($dX,3)) + (0.05594 * $dX * pow($dY,3)) + (-0.05607 * pow($dX,3) * $dY) + (0.01199 * $dY) + (-0.00256 * pow($dX,3) * pow($dY,2)) + (0.00128 * $dX * pow($dY,4)) + (0.00022 * pow($dY,2)) + (-0.00022 * pow($dX,2)) + (0.00026 * pow($dX,5));

        $lat = 52.15517 + ($SomN / 3600);
        $lon = 5.387206 + ($SomE / 3600);
        
        return(Array($lat, $lon));
    }
    
    function getAccesibleColor($arr){
        $total = 0;
        $OK = 0;
        foreach(["disabledaccessible-tram","disabledaccessible-bus","disabledaccessible-metro","disabledaccessible-ferry"] as $param){
            if(trim($arr[$param]) <> ""){
                $total += 1;
            }
            if(strtoupper($arr[$param]) == "Y"){
                $OK += 1;
            }
        }
        if($OK == 0 || $total == 0) return "#F00";
        if($OK == $total) return "#0F0";
        return "#FF0";
    }
        
    function csvAddObj($obj){
        return $this->csvAddArr(get_object_vars($obj));

    }
    
    function csvAddArr($arr){
        foreach($arr as $property => $value){
            $key = array_search($property, $this->csvHeader);
            if($key === false){
                $this->csvHeader[] = $property;
                $key = count($this->csvHeader) - 1;
            }
            $this->csvRow[$key] = $value;
        }
    }
    
    function csvPushRow(){
        if(count($this->csvRow) > 0){
            $this->csvData[] = $this->csvRow;
        }
        $this->csvRow = [];
        for($i = 0; $i < count($this->csvHeader); $i++){
            $this->csvRow[$i] = "";  
        }
    }
    
    function csvAddQuay($stopplace, $quay){ 
        $this->csvAddArr(["stopplacecode" => (string)$stopplace->stopplacecode]);
        $this->csvAddArr(["stopplacetype" => (string)$stopplace->stopplacetype]);
        $this->csvAddObj($stopplace->stopplacename);
        $this->csvAddArr(["stopplacestatus" => (string)$stopplace->stopplacestatusdata->stopplacestatus]);
        
        $this->csvAddArr(["quaycode" => (string)$quay->quaycode]);
        $this->csvAddArr(["quayname" => (string)$quay->quaynamedata->quayname]);
        $this->csvAddObj($quay->quaytypedata);
        $this->csvAddArr(["quaystatus" => (string)$quay->quaystatusdata->quaystatus]);
        $this->csvAddObj($quay->quaylocationdata);
        $this->csvAddArr(["visuallyaccessible" => (string)$quay->quayvisuallyaccessible->visuallyaccessible]);
        foreach($quay->quaydisabledaccessible as $da){
            $this->csvAddArr(["disabledaccessible-" . $da->transportmode => (string)$da->disabledaccessible]);  
        }
        $this->csvAddObj($quay->quayaccessibilityadaptions);
        $this->csvAddObj($quay->quayfacilities);
        $this->csvAddArr(["remarks" => (string)$quay->quayremarks->remarks]);
        $this->csvAddArr(["stopplace_mutationdate" => (string)$stopplace->mutationdate]);
        $this->csvAddArr(["quay_mutationdate" => (string)$quay->quaystatusdata->mutationdate]);
        $this->csvPushRow();
    }
    
    function csvWrite($fname){
        $f = fopen($fname,"w");
        fputcsv($f, $this->csvHeader);
        foreach($this->csvData as $row){
            fputcsv($f, $row);
        }  
        fclose($f);     
    }
    
    function geoJSONWrite($fname){
        $collection = new stdClass();
        $collection->type = "FeatureCollection";
        $collection->features = [];
        
        
        foreach($this->csvData as $row){
            $properties = [];
            foreach($this->csvHeader as $num => $param){
                $properties[$param] = $row[$num];
            }
            
            $properties["marker-color"] = $this->getAccesibleColor($properties);
            list($lat, $lon) = $this->RD2WGS84($properties["rd-x"], $properties["rd-y"]);

            $feature = new stdClass();
            $feature->type = "Feature";
            $feature->geometry = new stdClass();
            $feature->geometry->type = "Point";
            $feature->geometry->coordinates = [$lon, $lat];
            $feature->properties = (object)$properties;
            $collection->features[] = $feature;
        }
        
        $f = fopen($fname,"w");
        fwrite($f, json_encode($collection, JSON_PRETTY_PRINT));
        fclose($f);
    }
    
    function extractData(){
        foreach($this->towns as $town){
            $this->getDataTown($town);
            $this->csvWrite("../data/". $town .".csv");
            $this->geoJSONWrite("../data/". $town .".json");
        }
        unlink($this->xmlfileurl);
    }
    
    function getDataTown($town){
        $this->csvHeader = ["stopplacecode", "stopplacetype", "validfrom", "publicname", "street", "town", "stopplaceindication","stopplacestatus", "quaycode", "quayname", "quaytype", "quaystatus", "rd-x", "rd-y", "level", "location", "visuallyaccessible", "disabledaccessible-tram", "disabledaccessible-bus", "disabledaccessible-metro", "disabledaccessible-ferry", "quayshapetype", "baylength", "embaymentwidth", "bayentranceangles", "bayexitangles", "markedkerb", "lift", "guidelines", "groundsurfaceindicator", "tactilegroundsurfaceindicator", "stopplaceaccessroute", "kerbheight", "boardingpositionwidth", "alightingpositionwidth", "liftedpartlength", "narrowestpassagewidth", "fulllengthguideline", "guidelinestopplaceconnection", "ramp", "ramplength", "rampwidth", "heightwithenvironment", "stopsign", "audiobutton", "stopsigntype", "shelter", "shelterpublicity", "illuminatedstop", "seatavailable", "leantosupport", "timetableinformation", "infounit", "routenetworkmap", "passengerinformationdisplay", "passengerinformationdisplaytype", "bicycleparking", "numberofbicycleplaces", "bins", "ovccico", "ovccharging", "remarks"];
        //Hoeft niet gevuld, wel handig voor de volgorde
        $this->csvData = [];
        $this->csvRow = [];
        
        $this->csvPushRow(); //Eerste keer, anders gaat eerste rij fout, csvRow moet alvast gevuld worden met lege waarden.
        
        foreach($this->xml->stopplaces->stopplace as $key => $stopplace){
            if($stopplace->stopplacename->town == $town || reset(explode(" ",$stopplace->stopplacename->town)) == $town){
                foreach($stopplace->quays->quay as $quay){
                    $this->csvAddQuay($stopplace, $quay);
                }
            }
        }
    }
}

$chb = new CHB("http://data.ndovloket.nl/haltes/", ["Amsterdam","Rotterdam","Utrecht","Den Haag","Eindhoven","Almere"]);
$chb->extractData();
?>
