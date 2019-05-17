<?php

namespace App\Enums;

enum Queue: string
{
    case GANG_SHEET = 'gangsheet';

    case GANG_SHEET_THREE = 'gangsheet-3';

    case DEFAULT = 'default';

    case HIGH = 'high';

    case LOW = 'low';

    case LOG = 'log';
}
