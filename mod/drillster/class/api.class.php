<?php

/**
 * File: api.class.php
 * Encoding: UTF-8
 * @Project: Moodle Drillster
 *
 * @Version: 1.0.0 [@26-mrt-2012]
 * @Author: Luuk Verhoeven :: sebsoft.nl
 * */
defined('MOODLE_INTERNAL') || die;

class DrillsterApi
{

    private
            $useCurl = false;
    private
            $curl; // libcURL object
    private
            $strUrl = '';
    private
            $outputType = 'json';
    private
            $useOAuth = false;
    private
            $errorCodes = array(200 => '200 OK: everything went OK.',
        304 => '304 Not Modified: there was no new data to return.',
        400 => '400 Bad Request: your request is invalid, and the API will return an error message that tells you why.',
        401 => '401 Not Authorized: either you need to provide authentication credentials, or the credentials provided are not valid.',
        403 => '403 Forbidden: we understand your request, but are refusing to fulfill it. An accompanying error message should explain why.',
        404 => '404 Not Found: either you are requesting an invalid URI or the resource in question does not exist (example: no such user).',
        500 => '500 Internal Server Error: something went wrong in the Drillster application.',
        502 => '502 Bad Gateway: returned if Drillster is down or being upgraded.',
        503 => '503 Service Unavailable: the Drillster servers are up, but are overloaded with requests. Try again later.',
    );

    /**
     * get the URL
     * @return string
     */
    protected
            function getStrUrl()
    {
        return $this->strUrl;
    }

    /**
     * set The URL
     * @param string $strUrl
     */
    protected
            function setStrUrl($strUrl)
    {
        $this->strUrl = $strUrl;
    }

    /**
     * get If Oauth is used
     * @return boolean
     */
    protected
            function getUseOAuth()
    {
        return $this->useOAuth;
    }

    /**
     * set Oauth
     * @param boolean $useOAuth
     */
    protected
            function setUseOAuth($useOAuth)
    {
        $this->useOAuth = $useOAuth;
    }

    /**
     * get The last used URL
     * @global moodle_session $SESSION
     * @return string
     */
    protected
            function getLastUrl()
    {
        global $SESSION;
        if (!empty($SESSION->lastUrl))
        {
            return $SESSION->lastUrl;
        }
        return false;
    }

    /**
     * add The url to the session
     * @global moodle_session $SESSION
     * @param sting $lastUrl
     */
    protected
            function setLastUrl($lastUrl)
    {
        global $SESSION;
        $SESSION->lastUrl = $lastUrl;
    }

    /**
     * get Type we wan't to return the response
     * @return string
     */
    protected
            function getOutputType()
    {
        return $this->outputType;
    }

    /**
     * set output Type
     * @param string $outputType
     */
    protected
            function setOutputType($outputType)
    {
        $this->outputType = $outputType;
    }

    /**
     * construct
     * @param string $url
     * @param boolean $useOauth default false
     */
    function __construct($url = null, $useOauth = false)
    {
        if (!is_null($url) && strlen($url) > 5)
        {
            $this->strUrl = $url;
        }

        if ($useOauth)
        {
            $this->setUseOAuth(true);
        }

        // Determine if we can use curl, or have to fall back to crappy system
        if (function_exists('curl_init'))
        {
            $this->useCurl = true;
        }
        else
        {
            throw new Exception('Curl must be installed!');
        }
    }

    /**
     * return the url
     * @return string
     */
    final protected
            function getUrl()
    {
        return $this->strUrl;
    }

    /**
     * Method for doing requests by eighter cURL or file_get_contents
     *
     * @param array $data can be any type
     * @param string $method default = 'POST' / GET | POST | DELETE | PUT
     * @param integer $retryCount default = 5
     * @return data || false
     *
     */
    final public function doRequest(array $data = array(), $method = 'POST', $retryCount = 5)
    {
        global $CFG;

        if ($retryCount < 0)
        {
            return false;
        }
        $retryCount--; // Every request can be tried for 5 times (unless specific errors)
        if ($this->useCurl)
        {
            // Construct URL
            $strUrl = ($method == 'GET') ? $this->prepareHttpGet($this->strUrl, $data) : $this->strUrl;

            //ALWAYS re-init
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($this->curl, CURLOPT_USERAGENT, 'Moodle');
            curl_setopt($this->curl, CURLOPT_TIMEOUT, 15);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($this->curl, CURLOPT_REFERER, $CFG->wwwroot);
            curl_setopt($this->curl, CURLOPT_MAXREDIRS, 3);

            // Verbose crap please TURN off if you want a valid response JSON gets &@$(*$&
            if (!empty($CFG->drillster_debug) && 1 == 2 /*  ;-) */)
            {
                curl_setopt($this->curl, CURLOPT_HEADER, true);
                curl_setopt($this->curl, CURLOPT_VERBOSE, true);
                curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
            }

            if ($this->useOAuth !== false)
            {
                //set headers check if token is given
                if (($token = get_config('drillster', 'drillster_token')) !== '')
                {
                    //echo "<br/>USE OAUTH<br/>";
                    curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
                }
                else
                {
                    throw new Exception('FATAL: Drillster token is not set');
                }
            }

            if ($method == 'POST')
            {
                //echo "POST<br/>";
                curl_setopt($this->curl, CURLOPT_POST, true);
                if (isset($data['json']))
                {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data['json']);
                }
                else
                {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
                }
            }
            elseif ($method == 'PUT' || $method == 'DELETE')
            {
                //echo "PUT<br/>";
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $method);
                if (isset($data['json']))
                {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data['json']);
                }
                else
                {
                    curl_setopt($this->curl, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            }

            //set last url handy for debugging
            $this->setLastUrl($strUrl);
            //echo $strUrl;
            if (filter_var($strUrl, FILTER_VALIDATE_URL))
            {
                curl_setopt($this->curl, CURLOPT_URL, $strUrl);
            }
            else
            {
                throw new Exception('Error: Undefined URL');
            }
            $response = curl_exec($this->curl);

            $getinfo = curl_getinfo($this->curl);
            $error_nr = curl_errno($this->curl);

            $responseMessage = $this->convertCodeToMessage($getinfo['http_code']);

            if (!empty($CFG->drillster_debug))
            {
                echo '<pre>DUBUG IS ON:<br/>';
                print_r($getinfo);
                print_r($method);
                print_r($data);
                print_r($responseMessage);
                print_r($strUrl);
                print_r($response);
                echo '</pre>';
            }

            $response = $this->output($response);

            if ($error_nr > 0 || $getinfo['http_code'] > 400)
            {
                if (empty($CFG->drillster_debug))
                {
                    throw new Exception('Error: ' . $responseMessage . ' [' . $response->response->error->description . ']');
                }
                else
                {
                    die('..DEBUG MODE..');
                }
            }
            elseif (!$response)
            {
                throw new Exception('BAD response');
            }
            //echo "OK";
            return $response;
        }
        else
        {
            throw new Exception('Error: cURL is not found!');
        }
        return false;
    }

    /**
     * Function to modify array of params into REST API parameters.
     *
     * @param string $strUrl
     * @param array $arrParams
     * @return string
     */
    final protected
            function prepareHttpGet($strUrl, array $arrParams)
    {
        $first = 1;

        // Prepare query string
        foreach ($arrParams as $key => $value)
        {
            if ($first != 1)
            {
                $strUrl = $strUrl . "&";
            }
            else
            {
                $strUrl = $strUrl . "?";
                $first = 0;
            }

            if (is_array($value))
            {
                $count = count($value);
                foreach ($value as $k => $v)
                {
                    $count--;
                    $strUrl = $strUrl . $key . "[" . $k . "]=" . urlencode($v);
                    if ($count > 0)
                        $strUrl.="&";
                }
                continue;
            }

            // Add item to string
            $strUrl = $strUrl . $key . "=" . urlencode($value);
        }

        return $strUrl;
    }

    final private
            function output($data = '')
    {
        global $COURSE;
        $output = $this->getOutputType();

        if ($output == 'json')
        {
            $return = json_decode($data);
            if (!empty($return))
            {
                if (isset($return->response->error))
                {
                    add_to_log($COURSE->id, 'drillster', 'view', "Drillster api error", $return->response->error->description . ' [' . $return->response->error->code . ']');
                }
            }
            return $return;
        }
        else
        {
            //echo 'default';
            //default do nothing
            return $data;
        }
    }

    /**
     * HTTP Status Codes
     * @param int $code httpcode
     * @return string
     */
    final private
            function convertCodeToMessage($code = '')
    {
        if (isset($this->errorCodes[$code]))
        {
            return $this->errorCodes[$code];
        }

        return 'Error: [' . $code . ']';
    }

}