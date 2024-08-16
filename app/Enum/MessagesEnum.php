<?php


namespace App\Enum;

enum MessagesEnum: string
{
    case VERIFICATION = 'إن رمز تفعيل حسابك هو : %s الرجاء عدم ارساله لأحد';
    case RECOVER_PASSWORD = 'إن رمز استعادة حسابك هو : %s الرجاء عدم ارساله لأحد';

    public function formatMessage(string $code): string
    {
        return sprintf($this->value, $code);
    }
}
