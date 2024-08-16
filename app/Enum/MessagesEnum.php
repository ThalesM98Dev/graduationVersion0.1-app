<?php


namespace App\Enum;

enum MessagesEnum: string
{
    case VERIFICATION = 'إن رمز تفعيل حسابك هو : %s \nالرجاء عدم ارساله لأحد.\nشركة الاتحاد العربي للنقل البرّي.';
    case RECOVER_PASSWORD = 'إن رمز استعادة حسابك هو : %s \nالرجاء عدم ارساله لأحد.\nشركة الاتحاد العربي للنقل البرّي.';

    public function formatMessage(string $code): string
    {
        return sprintf($this->value, $code);
    }
}
