<?php

namespace PersonalSchedule\Util;

class TimeUtil {

    function getMidnight()
    {
        return strtotime('today midnight UTC');
    }

    function convertTimeToMs($str)
    {
        return strtotime("1970-01-01 $str UTC");
    }

}