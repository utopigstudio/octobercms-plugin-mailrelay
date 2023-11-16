<?php namespace Utopigs\MailRelay\Api;

class MailRelay
{
    private $api_key;
    private $api_endpoint = 'https://%s.ipzmarketing.com/api/v1';
    private $request_successful = false;
    private $last_error  = '';
    private $last_response = array();
    private $verify_ssl;

    const TIMEOUT = 10;

    /**
     * Create a new instance
     *
     * @param string $account_name Your MailRelay account name
     * @param string $api_key      Your MailRelay API key
     *
     * @throws \Exception
     */
    public function __construct($account_name, $api_key, $verify_ssl = true)
    {
        if (!function_exists('curl_init') || !function_exists('curl_setopt')) {
            throw new \Exception("cURL support is required, but can't be found.");
        }

        $this->api_endpoint = sprintf($this->api_endpoint, $account_name);
        $this->api_key = $api_key;
        $this->verify_ssl = $verify_ssl;
        $this->last_response = array('headers' => null, 'body' => null);
    }

    /**
     * @return string The url to the API endpoint
     */
    public function getApiEndpoint()
    {
        return $this->api_endpoint;
    }

    /**
     * Was the last request successful?
     *
     * @return bool  True for success, false for failure
     */
    public function success()
    {
        return $this->request_successful;
    }

    /**
     * Get the last error returned by either the network transport, or by the API.
     * If something didn't work, this should contain the string describing the problem.
     *
     * @return  string|false  describing the error
     */
    public function getLastError()
    {
        return $this->last_error ?: false;
    }

    /**
     * Get an array containing the HTTP headers and the body of the API response.
     *
     * @return array  Assoc array with keys 'headers' and 'body'
     */
    public function getLastResponse()
    {
        return $this->last_response;
    }

    /**
     * Make an HTTP POST request - for creating and updating items
     *
     * @param   string $method  URL of the API request method
     * @param   array  $args    Assoc array of arguments (usually your data)
     * @param   int    $timeout Timeout limit for request in seconds
     *
     * @return  array|false   Assoc array of API response, decoded from JSON
     */
    public function post($method, $args = array(), $timeout = self::TIMEOUT)
    {
        return $this->makeRequest('post', $method, $args, $timeout);
    }

    /**
     * Performs the underlying HTTP request. Not very exciting.
     *
     * @param  string $http_verb The HTTP verb to use: get, post, put, patch, delete
     * @param  string $method    The API method to be called
     * @param  array  $args      Assoc array of parameters to be passed
     * @param int     $timeout
     *
     * @return array|false Assoc array of decoded result
     */
    private function makeRequest($http_verb, $method, $args = array(), $timeout = self::TIMEOUT)
    {
        $url = $this->api_endpoint . '/' . $method;
        $response = $this->prepareStateForRequest($http_verb, $method, $url, $timeout);
        
        $httpHeader = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'X-AUTH-TOKEN: ' . $this->api_key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        switch ($http_verb) {
            case 'post':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
                break;
        }

        $responseContent = curl_exec($ch);
        $response['headers'] = curl_getinfo($ch);
        $response = $this->setResponseState($response, $responseContent, $ch);
        $formattedResponse = $this->formatResponse($response);
        
        curl_close($ch);

        $isSuccess = $this->determineSuccess($response, $formattedResponse, $timeout);
        
        return $isSuccess ? true : (is_array($formattedResponse) ? $formattedResponse : false);
    }

    /**
     * @param string  $http_verb
     * @param string  $method
     * @param string  $url
     * @param integer $timeout
     *
     * @return array
     */
    private function prepareStateForRequest($http_verb, $method, $url, $timeout)
    {
        $this->last_error = '';

        $this->request_successful = false;

        $this->last_response = array(
            'headers'     => null,
            'httpHeaders' => null,
            'body'        => null
        );

        return $this->last_response;
    }

    /**
     * Get the HTTP headers as an array of header-name => header-value pairs.
     *
     * @param string $headersAsString
     *
     * @return array
     */
    private function getHeadersAsArray($headersAsString)
    {
        $headers = array();
        
        foreach (explode("\r\n", $headersAsString) as $i => $line) {
            if (preg_match('/HTTP\/[1-2]/', substr($line, 0, 7)) === 1) {
                continue;
            }

            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            list($key, $value) = $line;

            $headers[$key] = $value;
        }

        return $headers;
    }

    /**
     * Decode the response and format any error messages for debugging
     *
     * @param array $response The response from the curl request
     *
     * @return array|false    The JSON decoded into an array
     */
    private function formatResponse($response)
    {
        $this->last_response = $response;
        
        if (!empty($response['body'])) {
            return json_decode($response['body'], true);
        }

        return false;
    }

    /**
     * Do post-request formatting and setting state from the response
     *
     * @param array    $response        The response from the curl request
     * @param string   $responseContent The body of the response from the curl request
     * @param resource $ch              The curl resource
     *
     * @return array    The modified response
     */
    private function setResponseState($response, $responseContent, $ch)
    {
        if ($responseContent === false) {
            $this->last_error = curl_error($ch);
        } else {
            $headerSize = $response['headers']['header_size'];
            $response['httpHeaders'] = $this->getHeadersAsArray(substr($responseContent, 0, $headerSize));
            $response['body'] = substr($responseContent, $headerSize);
        }

        return $response;
    }

    /**
     * Check if the response was successful or a failure. If it failed, store the error.
     *
     * @param array       $response          The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     * @param int         $timeout           The timeout supplied to the curl request.
     *
     * @return bool     If the request was successful
     */
    private function determineSuccess($response, $formattedResponse, $timeout)
    {
        $status = $this->findHTTPStatus($response, $formattedResponse);

        if ($status >= 200 && $status <= 299) {
            $this->request_successful = true;
            return true;
        }

        if (isset($formattedResponse['errors']) && isset($formattedResponse['errors']['email'])) {
            $this->last_error = sprintf('Error: %s', $formattedResponse['errors']['email'][0]);
            return false;
        }

        if ($timeout > 0 && $response['headers'] && $response['headers']['total_time'] >= $timeout) {
            $this->last_error = sprintf('Request timed out after %f seconds.', $response['headers']['total_time']);
            return false;
        }

        $this->last_error = 'Unknown error, call getLastResponse() to find out what happened.';
        return false;
    }

    /**
     * Find the HTTP status code from the headers or API response body
     *
     * @param array       $response          The response from the curl request
     * @param array|false $formattedResponse The response body payload from the curl request
     *
     * @return int  HTTP status code
     */
    private function findHTTPStatus($response, $formattedResponse)
    {
        if (!empty($response['headers']) && isset($response['headers']['http_code'])) {
            return (int)$response['headers']['http_code'];
        }

        if (!empty($response['body']) && isset($formattedResponse['status'])) {
            return (int)$formattedResponse['status'];
        }

        return 418;
    }
}
