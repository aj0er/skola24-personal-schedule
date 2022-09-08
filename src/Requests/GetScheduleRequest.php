<?php

namespace PersonalSchedule\Requests;

use Framework\Mapping\CustomRequest;
use Framework\Mapping\RequestMappingType;

class GetScheduleRequest extends CustomRequest {

    public int $width;
    public int $height;
    public string $wholeWeek;

    function getMappingType(): RequestMappingType
    {
        return RequestMappingType::FORM;
    }

    public function getValidationRules(): ?array
    {
        return [
            "width" => "required",
            "height" => "required",
            "wholeWeek" => "required"
        ];
    }

}