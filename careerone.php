<?php
class careerone extends api{
    //Constructor
    public function __construct(){
        error_reporting(E_ERROR);
        // set display errors status
        ini_set('display_errors', 0); // 1-turn on all error reporings 0-turn off all error reporings
        // change max execution time to unlimitied
        ini_set('max_execution_time', 0);
        //Context for file_get_content
        $this->context = stream_context_create(array(
            'http'=>array(
                'method'=>"GET",
                'timeout' => 3600,
                'header'=> "User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0\r\n "
            )
        ));
    }
    //Extract Email Address
	function extractEmailAddress($string){
		$matches = array();
		$pattern = '/[A-Za-z0-9_-]+@[A-Za-z0-9_-]+\.([A-Za-z0-9_-][A-Za-z0-9_]+)/';
		preg_match_all($pattern,$string,$matches);
        return $matches[0][0];
    }
    //Execute
    public function execute($hermesEndpoint){      
        global $afx;       
        //Base URL
        $baseUrl = "http://www.careerone.com.au/";
        $this->json_response = array();
        //Create an HTML DOM object
        $pageObject = @str_get_html(file_get_contents("{$baseUrl}/job-search/web-development/search?lid[]=",false,$this->context));
		//Loop
		foreach($pageObject->find(".job_title a") as $currentJob){
			//Define the $jobUrl
			$jobUrl = $currentJob->href;
			//Load the $jobPageObject
			$jobPageObject = @str_get_html(file_get_contents($jobUrl,false,$this->context));
			//Make sure we got something
			if($jobPageObject){
                $jobData["url"] = $jobUrl;
                $jobData["payload"] = $afx->action_value;
                $jobData["title"] = trim($jobPageObject->find("h3",0)->plaintext);
                $jobData["description"] = trim($jobPageObject->find("#lux-job-input-description",0)->innertext);                
                $jobData["response_email"] = $this->extractEmailAddress($jobData["description"]);        
                //Make sure we have something
                //Create a $curlHandle
                $curlHandle = curl_init($hermesEndpoint);
                //Initialize $curlHandle
                curl_setopt($curlHandle, CURLOPT_POST, true);
                curl_setopt($curlHandle,CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $jobData);                    
                $curlResult = json_decode(curl_exec($curlHandle),true);
                //Make sure things went as well as we could've hoped
                if( $curlResult["status"] == true){
                    print_r($jobData);
                    $this->json_response[] = $jobData;
                }
                else{
                    print_r($curlResult);
                }
			}
		}        
    }
}
?>
