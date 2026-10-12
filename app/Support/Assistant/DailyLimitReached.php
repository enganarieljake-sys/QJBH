<?php

namespace App\Support\Assistant;

class DailyLimitReached extends AssistantUnavailable
{
    public function __construct()
    {
        parent::__construct('The free AI limit for today is used up.');
    }
}
