<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case Valid = 'VALID';
    case Used = 'USED';
    case Expired = 'EXPIRED';
}
