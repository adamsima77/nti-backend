<?php
namespace Modules\IdentityAccess\Enums;

enum UserStatus: int {
    case PENDING_EMAIL = 1;
    case PENDING_ONBOARDING = 2;
    case ACTIVE = 3;
    case INACTIVE = 4;
    case BANNED = 5;
}
