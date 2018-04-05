<?php

namespace AppBundle\API;

use Symfony\Component\HttpFoundation\JsonResponse;

class APICommunicator {
    private $code;
    private $error;
    private $payload;
    private $info;

    const slugPayload = 'p';
    const slugError   = 'e';
    const slugInfo    = 'i';

    function __construct() {
        $this->code    = 200;
        $this->error   = new APIError($this->code);
        $this->payload = new APIPayload();
        $this->info    = new APIInfo();
    }

    function setError(): APIError {
        return $this->error;
    }

    function setPayload(): APIPayload {
        return $this->payload;
    }

    function setInfo(): APIInfo {
        return $this->info;
    }

    function getCode(): int {
        return $this->code;
    }

    function setCode(int $code) {
        $this->code = $code;
    }

    function toArray(): array {
        if ($this->error->getStatus())
            return [
                self::slugError => $this->error->getErrorString(),
                self::slugPayload => NULL,
                self::slugInfo => $this->info->getContent()
            ];

        return [
            self::slugError => '0.0',
            self::slugPayload => $this->payload->toArray(),
            self::slugInfo => $this->info->getContent()
        ];
    }

    function simpleResponse(): array {
        if ($this->error->getStatus())
            return [
                self::slugError => $this->error->getErrorString(),
                self::slugPayload => NULL
            ];

        return [
            self::slugError => '0.0',
            self::slugPayload => $this->payload->getSimple()
        ];
    }

    function doResponse(): JsonResponse {
        return new JsonResponse($this->toArray(), $this->getCode());
    }
}