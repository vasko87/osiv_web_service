<?php

namespace DbService\Service\Dms;

class DmsService
{
    private $client;

    public function __construct()
    {
        $fileWsdl = __DIR__ . '/wsdl/Kinetic.Services.WMObjectService.wsdl';
        $this->client = new \SoapClient($fileWsdl, [
            'trace' => 1,
            'exceptions' => true,
        ]);
    }

    public function getSessionToken(): string
    {
        $response = $this->client->__soapCall("CreateWMSession", [
            "parameters" => [
                "sessionModule" => "Default",
            ]
        ]);


        if (empty($response->CreateWMSessionResult) || !is_string($response->CreateWMSessionResult)) {
            throw new \RuntimeException('Error creating session');
        }

        return $response->CreateWMSessionResult;
    }

    public function getObjectInfo(string $sessionToken, string $id): array
    {
        $this->client->__soapCall("LoadByVersionId", [
            "parameters" => [
                "sessionId" => $sessionToken,
                "versionId" => $id,
            ]
        ]);

        $response = $this->client->__soapCall("GetObjectInfo", [
            "parameters" => [
                "sessionId" => $sessionToken,
            ]
        ]);

        if (empty($response->GetObjectInfoResult) || (!$response->GetObjectInfoResult instanceof \stdClass)) {
            throw new \RuntimeException('Error calling getObjectInfo');
        }

        return get_object_vars($response->GetObjectInfoResult);
    }

    public function getTrace(): array
    {
        return [
            'Request Headers' => $this->client->__getLastRequestHeaders(),
            'Request' => $this->client->__getLastRequest(),
            'Response Headers' => $this->client->__getLastResponseHeaders(),
            'Response' => $this->client->__getLastResponse(),
        ];
    }
}
