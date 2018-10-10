<?php 
/** 
 * @author  dudley 
 * @date    2015-01-24 
 * 
 * bravia remote interface
 */

class Bravia implements Remote { 
    public $messages = array(); 
    public $ip = '10.0.0.9';
    public $codes = array(
        'power'=>       'AAAAAQAAAAEAAAAvAw==', 
        'mute'=>        'AAAAAQAAAAEAAAAUAw==', 
        'volume_up'=>   'AAAAAQAAAAEAAAASAw==', 
        'volume_down'=> 'AAAAAQAAAAEAAAATAw==', 
        'channel_up'=>  'AAAAAQAAAAEAAAAQAw==', 
        'channel_down'=>'AAAAAQAAAAEAAAARAw==', 
        'num_0'=>       'AAAAAQAAAAEAAAAJAw==', 
        'num_1'=>       'AAAAAQAAAAEAAAAAAw==', 
        'num_2'=>       'AAAAAQAAAAEAAAABAw==', 
        'num_3'=>       'AAAAAQAAAAEAAAACAw==', 
        'num_4'=>       'AAAAAQAAAAEAAAADAw==', 
        'num_5'=>       'AAAAAQAAAAEAAAAEAw==', 
        'num_6'=>       'AAAAAQAAAAEAAAAFAw==', 
        'num_7'=>       'AAAAAQAAAAEAAAAGAw==', 
        'num_8'=>       'AAAAAQAAAAEAAAAHAw==', 
        'num_9'=>       'AAAAAQAAAAEAAAAIAw==', 
        'pause'=>       'AAAAAgAAAJcAAAAZAw==', 
        'play'=>        'AAAAAgAAAJcAAAAaAw==', 
        'stop'=>        'AAAAAgAAAJcAAAAYAw==', 
        'forward'=>     'AAAAAgAAAJcAAAAcAw==', 
        'reverse'=>     'AAAAAgAAAJcAAAAbAw==', 
        'previous'=>    'AAAAAgAAAJcAAAA8Aw==', 
        'next'=>        'AAAAAgAAAJcAAAA9Aw=='
    ); 

    public function __construct() { 
    } 

    // soap based querying of bravia tv
    public function IRCCQuery($code, $success = array()) { 
        $request = <<<EOL
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
    <s:Body>
        <u:X_SendIRCC xmlns:u="urn:schemas-sony-com:service:IRCC:1">
            <IRCCCode>$code</IRCCCode>
        </u:X_SendIRCC>
    </s:Body>
</s:Envelope>
EOL;
        $curlClient = curl_init(); 
        curl_setopt($curlClient, CURLOPT_URL, 'http://' . $this->ip . '/IRCC');   
        curl_setopt($curlClient, CURLOPT_CONNECTTIMEOUT, 10); 
        curl_setopt($curlClient, CURLOPT_TIMEOUT, 10); 
        curl_setopt($curlClient, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curlClient, CURLOPT_HEADER, true); 
        // curl_setopt($curlClient, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curlClient, CURLOPT_POST, true); 
        curl_setopt($curlClient, CURLOPT_POSTFIELDS, $request); 
        curl_setopt($curlClient, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8', 'Content-Length: ' . strlen($request) )); 
        $response = curl_exec($curlClient);
        $responseHeadSize = curl_getinfo($curlClient, CURLINFO_HEADER_SIZE);
        $responseCode = curl_getinfo($curlClient, CURLINFO_HTTP_CODE); 
        $responseHead = substr($response, 0, $responseHeadSize);
        $responseBody = substr($response, $responseHeadSize);
        if(curl_errno($curlClient)) {
            $this->messages[] = array('error'=>ucfirst(curl_error($curlClient))); 
        }
        else { 
            $this->IRCCResponseMessage($responseCode, $responseHead, $responseBody, $success);
        } 
        curl_close($curlClient);
        return $this; 
    } 

    // process soap based response from bravia tv
    public function IRCCResponseMessage($responseCode, $responseHead, $responseBody, $success) { 
        switch($responseCode) { 
            case(200):  
                $dom = new DOMDocument(); 
                $dom->loadXML($responseBody); 
                $xpath = new DOMXPath($dom);
                $xpath->registerNamespace('u', 'urn:schemas-sony-com:service:IRCC:1');
                $result = $xpath->query('//u:X_SendIRCCResponse'); 
                $message = trim($result->item(0)->nodeValue); 
                if(strlen($message) > 0) { 
                    $this->messages[] = array('error'=>'System Error.'); 
                } 
                else { 
                    $this->messages[] = $success; 
                } 
                break; 
            case(404): 
                $this->messages[] = array('error'=>'Not Implemented.'); 
                break;
            default: 
                var_dump($responseCode); 
                break; 
        } 
        return $this; 
    } 

    // json based querying of bravia tv
    public function JSONQuery($request, $success = array(), $uri = null) { 
        $curlClient = curl_init(); 
        curl_setopt($curlClient, CURLOPT_URL, 'http://' . $this->ip . $uri);   
        curl_setopt($curlClient, CURLOPT_CONNECTTIMEOUT, 10); 
        curl_setopt($curlClient, CURLOPT_TIMEOUT, 10); 
        curl_setopt($curlClient, CURLOPT_RETURNTRANSFER, true );
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($curlClient, CURLOPT_SSL_VERIFYHOST, false); 
        curl_setopt($curlClient, CURLOPT_HEADER, true); 
        curl_setopt($curlClient, CURLOPT_POST, true ); 
        curl_setopt($curlClient, CURLOPT_POSTFIELDS, $request); 
        curl_setopt($curlClient, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8', 'Content-Length: ' . strlen($request) )); 
        $response = curl_exec($curlClient);
        $responseHeadSize = curl_getinfo($curlClient, CURLINFO_HEADER_SIZE);
        $responseCode = curl_getinfo($curlClient, CURLINFO_HTTP_CODE); 
        $responseHead = substr($response, 0, $responseHeadSize);
        $responseBody = substr($response, $responseHeadSize);
        if(curl_errno($curlClient)) {
            $this->messages[] = array('error'=>ucfirst(curl_error($curlClient))); 
        }
        else { 
            $this->JSONResponseMessage($responseCode, $responseHead, $responseBody, $success);
        } 
        curl_close($curlClient);
        return $this; 
    } 

    // process json based response from bravia tv
    public function JSONResponseMessage($responseCode, $responseHead, $responseBody, $success) { 
        switch($responseCode) { 
            case(200):  
                // TODO: parse json response
                // var_dump($responseCode, $responseHead, $responseBody); 
                break; 
            case(404): 
                $this->messages[] = array('error'=>'Not Implemented.'); 
                break;
            default: 
                break; 
        } 
        return $this; 
    } 

    public function getRemoteControllerInfo() { 
        return $this->JSONQuery('{"id":20,"method":"getRemoteControllerInfo","version":"1.0","params":[]}', array('success'=>'remote controller info.'), '/sony/system'); 
    } 

    public function power() { 
        return $this->IRCCQuery($this->codes['power'], array('success'=>'Power off.')); 
    }

    public function mute() { 
        return $this->IRCCQuery($this->codes['mute'], array('success'=>'Volume muted.')); 
    }

    public function volume_up() { 
        return $this->IRCCQuery($this->codes['volume_up'], array('success'=>'Volume increased.')); 
    }

    public function volume_down() { 
        return $this->IRCCQuery($this->codes['volume_down'], array('success'=>'Volume decreased.')); 
    }

    public function audio() { 
        return $this->IRCCQuery($this->codes['audio'], array('success'=>'Audio.')); 
    } 

    public function channel_up() { 
        return $this->IRCCQuery($this->codes['channel_up'], array('success'=>'Channel increased.')); 
    } 

    public function channel_down() { 
        return $this->IRCCQuery($this->codes['channel_down'], array('success'=>'Channel decreased.')); 
    } 

    public function channel_number($number) { 
        return $this->IRCCQuery($this->codes['channel_' . $number], array('success'=>'Channel changed to ' . $number . '.')); 
    } 

    public function pause() { 
        return $this->IRCCQuery($this->codes['pause'], array('success'=>'Media paused.')); 
    } 

    public function play() { 
        return $this->IRCCQuery($this->codes['play'], array('success'=>'Media playing.')); 
    } 

    public function stop() { 
        return $this->IRCCQuery($this->codes['stop'], array('success'=>'Media stopped.')); 
    } 

    public function forward() { 
        return $this->IRCCQuery($this->codes['forward'], array('success'=>'Media forward.')); 
    } 

    public function reverse() { 
        return $this->IRCCQuery($this->codes['reverse'], array('success'=>'Media reversed.')); 
    } 

    public function previous() { 
        return $this->IRCCQuery($this->codes['previous'], array('success'=>'Previous media.')); 
    } 

    public function next() { 
        return $this->IRCCQuery($this->codes['next'], array('success'=>'Next media.')); 
    } 
} 

?>
