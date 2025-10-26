<?php

namespace App\Enum;

enum PlayStatus: string
{
    case BACKLOG = 'BACKLOG';
    case PLAYING = 'PLAYING';
    case COMPLETED = 'COMPLETED';
    case ABANDONED = 'ABANDONED';
}
