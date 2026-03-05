<?php

namespace App\Enum;

enum LoginStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
}
