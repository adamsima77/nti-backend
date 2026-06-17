<?php

namespace Modules\AuditCompliance\Enums;

enum SeverityType: string
{
    case CRITICAL = 'CRITICAL';
    case ERROR = 'ERROR';
    case WARNING = 'WARNING';
    case INFO = 'INFO';
}
