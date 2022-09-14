<?php

namespace PersonalSchedule\Controllers;

use Framework\Controller\Controller;
use Framework\Util\StatusCode;

use PersonalSchedule\Services\ScheduleService;
use PersonalSchedule\Requests\SetupRequest;
use PersonalSchedule\Requests\GetScheduleRequest;

class MainController extends Controller {

    private ScheduleService $scheduleService;

    function __construct(ScheduleService $scheduleService){
        $this->scheduleService = $scheduleService;
    }

    function index(){
        return parent::view("index");
    }

    function setup(SetupRequest $request){
        $signature = $this->scheduleService->encryptSignature($request->signature);
        setcookie(
            "signature",
            $request->domain . "|" . $signature,
            time() + 60 * 60 * 24 * 30, // 30 days
            "/schema2",
            "te19adjo.kgwebb.se",
            true,
            true
        );

        return parent::redirect("/schema2");
    }

    function getSchedule(GetScheduleRequest $request){
        $signature = $_COOKIE["signature"];
        $parts = explode("|", $signature);

        if(count($parts) != 2)
            return parent::status(StatusCode::BadRequest);

        $domain = $parts[0];
        $id = $parts[1];

        return $this->scheduleService->getSchedule($domain, $id, $request->width, 
                                                        $request->height, $request->wholeWeek === "true");
    }

}