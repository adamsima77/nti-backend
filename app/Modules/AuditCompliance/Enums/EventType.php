<?php

namespace Modules\AuditCompliance\Enums;

enum EventType: string
{
    case AUDIT = 'AUDIT';
    case SECURITY_ALERT = 'SECURITY_ALERT';
    case SYSTEM_ERROR = 'SYSTEM_ERROR';
}
