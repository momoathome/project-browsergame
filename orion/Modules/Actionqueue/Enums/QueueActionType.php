<?php

namespace Orion\Modules\Actionqueue\Enums;

enum QueueActionType: string
{
    case ACTION_TYPE_MINING = 'mining';
    case ACTION_TYPE_BUILDING = 'building';
    case ACTION_TYPE_PRODUCE = 'produce';
    case ACTION_TYPE_TRADE = 'trade';
    case ACTION_TYPE_COMBAT = 'combat';
    case ACTION_TYPE_RESEARCH = 'research';
}
