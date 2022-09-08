<?php

namespace PersonalSchedule\Services;

use GuzzleHttp\Client;
use \DateTimeZone;
use \DateTime;

class ScheduleService
{

    private const HEADERS = [
        'Connection' => 'keep-alive',
        'Accept' => 'application/json, text/javascript, */*; q=0.01',
        'X-Scope' => '8a22163c-8662-4535-9050-bc5e1923df48',
        'X-Requested-With' => 'XMLHttpRequest',
        'User-Agent' => 'Mozilla/5.0 (X11; CrOS x86_64 13310.76.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.108 Safari/537.36',
        'Content-Type' => 'application/json',
        'Sec-Fetch-Site' => 'same-origin',
        'Sec-Fetch-Mode' => 'cors',
        'Sec-Fetch-Dest' => 'empty',
        'Accept-Language' => 'sv,en-US;q=0.9,en;q=0.8',
        'Cookie' => 'ASP.NET_SessionId=aaaaaaaaaaaaaaaaaaaaaaaa'
    ];

    private const IGNORED_LESSONS = [
        "Lunch",
        "Rast"
    ];

    function getRenderKey(): string
    {
        $client = new Client();
        $res = $client->request('POST', 'https://web.skola24.se/api/get/timetable/render/key', [
            'headers' => self::HEADERS
        ]);

        $body = (string) $res->getBody();
        $js = json_decode($body, true);

        return $js["data"]["key"];
    }

    function getSchedule(string $domain, string $signature, int $width, int $height, bool $wholeWeek)
    {
        $renderKey = $this->getRenderKey();
        $rendered = $this->renderSchedule($renderKey, $domain, $signature, $width, $height, $wholeWeek);

        $data = $rendered["data"];
        $data["parsed"] = $this->parseLessonInfo($data["lessonInfo"], false);
        return $data;
    }

    function getArrayOr(array $array, $or, int $index, ?int $count)
    {
        $c = $count ?? (count($array));
        return $index >= 0 && $index < $c ? $array[$index] : $or;
    }

    function parseLessonInfo($info, $currentDay)
    {
        $timezone = new DateTimeZone("Europe/Stockholm");
        $offset   = $timezone->getOffset(new DateTime());

        $ov = ($currentDay ? ($this->getMidnight() - $offset) : 0);

        $lessons = [];
        for ($i = 0; $i < count($info); $i++) {
            $lesson = $info[$i];
            $texts = $lesson["texts"];
            $lessonName = $texts[0];

            if (in_array($lessonName, self::IGNORED_LESSONS)) {
                continue;
            }

            $textCount = count($texts);
            $item = [
                "name" => $lessonName,
                "teacher" => $this->getArrayOr($texts, "Ingen lärare", 1, $textCount),
                "room" => $this->getArrayOr($texts, "Inget rum", 2, $textCount),
                "timeStart" => $ov + $this->convertTimeToMs(($lesson["timeStart"])),
                "timeEnd" => $ov + $this->convertTimeToMs(($lesson["timeEnd"]))
            ];

            array_push($lessons, $item);
        }

        usort($lessons, function ($a, $b) {
            return $a["timeStart"] <=> $b["timeStart"];
        });

        $lessons = array_reverse($lessons);
        return $lessons;
    }

    function getMidnight()
    {
        return strtotime('today midnight UTC');
    }

    function convertTimeToMs($str)
    {
        return strtotime("1970-01-01 $str UTC");
    }

    private function renderSchedule(string $key, string $domain, string $signature, int $width, int $height, bool $wholeWeek)
    {

        $weekDay = date("w");
        $body = [
            "renderKey" => $key,
            "host" => rawurlencode($domain),
            "unitGuid" => "OWM1YWRhYTEtYTNmYi1mNzYzLWI5NDItZjkzZjE3M2VhNjA4",
            "scheduleDay" => ($weekDay == 6 || $wholeWeek) ? 0 : $weekDay,
            "blackAndWhite" => false,
            "width" => $width,
            "height" => $height,
            "selectionType" => 4,
            "selection" => $signature,
            "showHeader" => false,
            "periodText" => "",
            "week" => ($weekDay == 6 || $weekDay == 0) ? date("W") + 1 : date("W"),
            "year" => date("Y"),
            "privateFreeTextMode"  => false,
            "privateSelectionMode" => null
        ];

        $client = new Client();
        $res = $client->request('POST', 'https://web.skola24.se/api/render/timetable', [
            'json' => $body,
            'headers' => self::HEADERS
        ]);

        $body = (string) $res->getBody();
        $js = json_decode($body, true);

        return $js;
    }

    function encryptSignature(string $signature)
    {
        $client = new Client();
        $res = $client->request('POST', 'https://web.skola24.se/api/encrypt/signature', [
            'json' => [
                'signature' => $signature
            ],
            'headers' => self::HEADERS
        ]);

        $body = (string) $res->getBody();
        $js = json_decode($body, true);
        return $js["data"]["signature"];
    }
}
