<?php

namespace App\Enum;

enum NotificationsEnum: string
{
    case WELCOME = 'اهلاً بك في تطبيق شركة الاتحاد العربي..نأمل ان تنال رضاك خدماتنا.';
    case TRIP_RESERVATION_ACCEPTANCE = 'تمت الموافقة على حجزك في رحلة ..() .يرجى الحضور في مركز الانطلاق خلال ثلاثة ايام لتثبيت الحجز و دفع الرسوم';
    case TRIP_RESERVATION_REJECT = 'عذراً.لم لتم الموافقة على حجزك في الرحلة ()..اضغط لمعرفة التفاصل.';

    public function formatMessage(string $text): string
    {
        return sprintf($this->value, $text);
    }
}
