<?php

namespace Orion\Modules\User\Enums;

enum UserAttributeType: string
{
    case BASE_DEFENSE = 'base_defense';
    case CREDITS = 'credits';
    case CREW_LIMIT = 'crew_limit';
    case INFLUENCE = 'influence';
    case PRODUCTION_SPEED = 'production_speed';
    case RESEARCH_POINTS = 'research_points';
    case SCAN_RANGE = 'scan_range';
    case STORAGE = 'storage';
    case TOTAL_UNITS = 'total_units';
    case UPGRADE_SPEED = 'upgrade_speed';
}
