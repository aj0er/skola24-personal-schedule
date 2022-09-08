<?php

namespace PersonalSchedule\Requests;

use Framework\Mapping\CustomRequest;
use Framework\Mapping\RequestMappingType;

class SetupRequest extends CustomRequest {

    public string $signature;
    public string $domain;

    function getMappingType(): RequestMappingType
    {
        return RequestMappingType::FORM;
    }

    public function getValidationRules(): ?array
    {
        return [
            "signature" => "required",
            "domain" => "required"
        ];
    }

}