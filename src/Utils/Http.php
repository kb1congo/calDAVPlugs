<?php

namespace Ginov\CaldavPlugs\Utils;


use Sabre\HTTP\Client as HttpClient;
use Sabre\DAV\Client as DavClient;
use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Request;

class Http
{
    // const IS_SOAP = 2;
    const IS_DAV = 1;
    const IS_HTTP = 0;

    const IS_NOT_VERIFY = false;

    /** @var HttpClient */
    private $httpClient;

    /** @var DavClient */
    private $davClient;

    private string $baseUrl;

    private $isDav;

    /**
     * Constructor for the Http wrapper class.
     *
     * @param array $davSettings Array containing the DAV server settings.
     */
    public function __construct(string $baseUrl)
    {
        $this->httpClient = null;
        $this->davClient = null;

        $this->baseUrl = $baseUrl;

        // $this->isDav = !!$davSettings;
    }

    /**
     * get http client
     *
     * @return self
     */
    public function http(): self
    {
        $this->httpClient = new HttpClient();

        $this->notVerify();

        return $this;
    }

    /**
     * get dav client
     *
     * @param array $davSettings
     * @return self
     */
    public function dav(array $davSettings): self
    {
        $this->isDav = true;

        $davSettings['baseUri'] = $this->baseUrl;

        $this->davClient = new DavClient($davSettings);

        $this->notVerify();

        return $this;
    }

    /**
     * active ssl verification
     *
     * @param string $caPath
     * @return self
     */
    public function verify(string $caPath): self
    {
        if (!$this->isDav && $this->httpClient)
            $this->httpClient
                ->addCurlSetting(CURLOPT_CAINFO, $caPath);
        elseif($this->isDav && $this->davClient)
            $this->davClient
                ->addCurlSetting(CURLOPT_CAINFO, $caPath);
    
        else throw new \Exception('client error');

        return $this;
    }

    /**
     * Send an HTTP request using Sabre\HTTP\Client.
     *
     * @param string $method The HTTP method (GET, POST, etc.).
     * @param string $url The URL to send the request to.
     * @param array $headers Optional HTTP headers.
     * @param string $body Optional body content for the request.
     *
     * @return \Sabre\HTTP\Response
     * @throws \Sabre\HTTP\ClientHttpException
     */
    public function sendHttpRequest(string $method, string $url, array $headers = [], string $body = ''):ResponseInterface
    {
        $request = new \Sabre\HTTP\Request($method, $this->baseUrl . $url);
        $request->setHeaders($headers);
        $request->setBody($body);

        return $this->httpClient->send($request);
    }

    /**
     * Perform a DAV request using Sabre\DAV\Client.
     *
     * @param string $method The DAV method (PROPFIND, REPORT, etc.).
     * @param string $url The DAV URL to interact with.
     * @param array $headers Optional DAV headers.
     * @param string $body Optional DAV request body.
     *
     * @return array The response from the DAV server.
     * @throws \Sabre\DAV\Exception
     */
    public function sendDavRequest(string $method, string $url, array $headers = [], string $body = '')
    {
        $request = [
            'method'  => $method,
            'url'     => $this->baseUrl . $url,
            'headers' => $headers,
            'body'    => $body,
        ];
        // dd($this->baseUrl . $url);
        return $this->davClient->request($method, $this->baseUrl . $url, $body, $headers);
    }

    private function notVerify(): self
    {
        if (!$this->isDav && $this->httpClient) {
            $this->httpClient
                ->addCurlSetting(CURLOPT_SSL_VERIFYHOST, 0);
            $this->httpClient
                ->addCurlSetting(CURLOPT_SSL_VERIFYPEER, 0);
        } elseif($this->isDav && $this->davClient) {
            $this->davClient
                ->addCurlSetting(CURLOPT_SSL_VERIFYHOST, 0);
            $this->davClient
                ->addCurlSetting(CURLOPT_SSL_VERIFYPEER, 0);
        }
        else throw new \Exception('client error');

        return $this;
    }
}