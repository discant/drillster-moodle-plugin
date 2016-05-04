<?php
class mod_drillster_api_response{
        
    private static $status_codes = array(
        '200' => 'OK',
        '304' => 'Not Modified',
        '400' => 'Bad Request',
        '401' => 'Not Authorized',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '500' => 'Internal Server Error',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable'
    );

    private $_transfer_info;
    private $_response;
    
    public function __construct($transfer_info, $response){

        $this->_transfer_info = $transfer_info;
        $this->_response = json_decode($response);
    }
    
    public function getData(){
        if($this->getStatusCode() == 200){
            return (!empty($this->_response)) ? $this->_response : true;
        } else {
            return false;
        }            
    }
    
    public function getErrorData(){
        return $this->_response;
    }
    
    public function toArray(){
        
        $a = array();
        $a['status_code'] = $this->getStatusCode();
        $a['status'] = $this->getStatusMessage();
        
        if($this->getStatusCode() == '200'){
            $a['data'] = $this->_response;
        } else {
            $a['error_data'] = $this->_response;
        }
        
        return $a;
    }
    
    public function jsonEncode(){

        return json_encode($this->toArray());
    }
        
    public function getStatusCode(){
        return $this->_transfer_info['http_code'];
    }
    
    public function getStatusMessage(){
        
        if(array_key_exists($this->getStatusCode(), self::$status_codes)){
            return self::$status_codes[$this->_transfer_info['http_code']];
        } else {
            return 'Unknown status code';
        }
    }
    
    public function getErrorMessage(){

        $message = 'Code: '.$this->getStatusCode().' - '.$this->getStatusMessage();
        $message .= ' | '.$this->_response->description;  
        return $message;
    }
}

class mod_drillster_api{
    
    private static $webservice_url = 'https://www.drillster.com/api/2';
    
    const GET       = 'GET';
    const POST      = 'POST';
    const PUT       = 'PUT';
    const DELETE    = 'DELETE';
    
    private static $instance = null;
    
    public function __construct(){
        
        global $CFG;

        if(time() > get_config('drillster', 'drillster_expires_in')) $this->refreshToken();          
    }
    
    public static function getInstance(){
        if(is_null(self::$instance)) self::$instance = new mod_drillster_api();
        return self::$instance;   
    }
      
    public function get($url, $params = array()){
        return $this->request($url, $params, self::GET);
    }
    
    public function post($url, $params = array()){
        return $this->request($url, $params, self::POST);
    }
    
    public function delete($url, $params = array()){
        return $this->request($url, $params, self::DELETE);
    }
    
    public function put($url, $params = array()){
        return $this->request($url, $params, self::PUT);
    }
    
    public function refreshToken(){

        global $CFG;
        
        $client_id = $CFG->drillster_client_id;
        $client_secret = $CFG->drillster_client_secret;
        
        $refresh_token = get_config('drillster', 'drillster_refresh_token');
        
        $response = $this->post('token', array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token
        ));

        if($response->getStatusCode() == 200) $this->updateToken($response);
    }
    
    public function updateToken($response){
        
        if($data = $response->getData()){

            set_config('drillster_token', $data->access_token, 'drillster');
            set_config('drillster_refresh_token', $data->refresh_token, 'drillster');
            set_config('drillster_expires_in', time() + $data->expires_in, 'drillster');
        }
    }
    
    public static function build_query($params){
        return http_build_query($params, '', '&');   
    }
    
    private function request($url, $params, $method){
        
        global $CFG;
        
        $ch = curl_init();
        
        $token = get_config('drillster', 'drillster_token');

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
        curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
        
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_REFERER, $CFG->wwwroot);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        switch($method){
            case "GET":
                
                curl_setopt($ch, CURLOPT_URL, self::$webservice_url.'/'.$url.(sizeof($params) > 0 ? '?'.self::build_query($params) : ''));  
                   
            break;
            case "POST":
                
                curl_setopt($ch, CURLOPT_URL, self::$webservice_url.'/'.$url);
                curl_setopt($ch, CURLOPT_POST, 1);    
                if(sizeof($params) > 0) curl_setopt($ch, CURLOPT_POSTFIELDS, self::build_query($params));  
                          
            break;    
            case "DELETE":
                
                curl_setopt($ch, CURLOPT_URL, self::$webservice_url.'/'.$url.(sizeof($params) > 0 ? '?'.self::build_query($params) : ''));
                
            break;
            case "PUT":
                
                curl_setopt($ch, CURLOPT_URL, self::$webservice_url.'/'.$url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                if(sizeof($params) > 0) curl_setopt($ch, CURLOPT_POSTFIELDS, self::build_query($params));
                
            break;
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        
        return new mod_drillster_api_response($info, $result);

    }
}