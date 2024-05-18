<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('تفعيل الحساب')
            ->greeting('مرحباً بك في شركة الاتحاد العربي للنقل !')
            ->line('نشكرك على استخدام تطبيقنا. الرجاء نسخ الرمز و ادخاله في التطبيق لتفعيل حسابك.')
            ->line('رمز التفعيل :' . $this->code)
            //->line('If you did not create an account, no further action is required.')
            ->salutation('أطيب التمنيات!.شركة الاتحاد العربي');
    }
}

