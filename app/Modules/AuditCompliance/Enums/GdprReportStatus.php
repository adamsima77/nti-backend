<?php

namespace Modules\AuditCompliance\Enums;

enum GdprReportStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case PROCESSING = 'processing';
}
