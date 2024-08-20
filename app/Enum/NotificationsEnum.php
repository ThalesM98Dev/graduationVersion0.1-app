<?php

namespace App\Enum;

enum NotificationsEnum: string
{
    case TITLE = 'شركة الاتحاد العربي للنقل البرّي';
    case WELCOME = 'اهلاً بك في تطبيق شركة الاتحاد العربي..نأمل ان تنال خدماتنا رضاك.';
    case TRIP_RESERVATION_ACCEPTANCE = 'تم حجز المقاعد المحددة في الرحلة رقم {tripNumber} الرحلة المتجهة الى {destination} . يرجى مراجعة مكتب الشركة لتثبيت الحجز ودفع الرسوم خلال مدة اقصاها يومين.';
    case TRIP_RESERVATION_REJECT = 'عذراً.لم لتم الموافقة على حجزك في الرحلة {tripNumber} .';
    case ENVELOPE_ORDER_ACCEPTANCE = 'تمت الموافقة على طلب الامانة من قبل السائق {driverName} يرجى التواصل مع السائق وتسليم الامانة قبل موعد الرحلة بيوم واحد.';
    case ENVELOPE_ORDER_REJECT = 'عذراً.تم رفض طلب الامانة الخاص بك من قبل السائق {driverName} .';
    case SUBSCRIBE_ORDER = 'تم ارسال طلب الاشتراك الشهري في الرحلة الجامعية رقم {tripNumber} بنجاح. يرجى مراجعة مكتب الشركة وتثبيت الاشتراك في الرحلة خلال مدة اقصاها اربعة ايام من تاريخ اليوم.';

    case SHIPMENT_ORDER = 'نم ارسال طلب الشحن بنجاح. سيتم ارسال اشعار اخر في حال الموافقة على الطلب من قبل قسم الشحن. ';
    case SHIPMENT_ACCEPTANCE = 'تمت الموافقة على طلب الشحنة المرسل. يرجى مراجعة مكتب الشركة لاتمام العملية ودفع الرسوم خلال مدة اقصاها يومين.';

    public function formatMessage(string $message, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $message = str_replace("{" . $key . "}", $value, $message);
        }
        return $message;
    }
}
