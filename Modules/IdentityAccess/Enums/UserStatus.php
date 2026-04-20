<?php
namespace Modules\IdentityAccess\Enums;

enum UserStatus: int {
    case PENDING = 1;
    case ACTIVE = 2;
    case INACTIVE = 3;
    case BANNED = 4;
}
