<?php

namespace App\Enum;

enum RulesEnum: string
{
    case ADMIN = 'Admin';
    case USER = 'User';
    case DRIVER = 'Driver';
    case SHIPMENT = 'Shipment Employee';
    case TRAVEL = 'Travel Trips Employee';
    case UNIVERSITY = 'University trips Employee';

}
