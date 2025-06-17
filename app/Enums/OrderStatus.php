<?php

namespace App\Enums;

enum OrderStatus: string
{
    case NEW = 'new';
    case PAID = 'paid';
    case DECLINE = 'decline';
    case CLOSED = 'closed';
} 