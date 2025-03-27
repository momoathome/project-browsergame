<?php

namespace Orion\Modules\Spacecraft\Enums;

enum SpacecraftType: string
{
    case TYPE_MINER = 'miner';
    case TYPE_SALVAGER = 'salvager';
    case TYPE_FIGHTER = 'fighter';
}
