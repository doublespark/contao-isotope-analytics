<?php

namespace Doublespark\IsotopeAnalyticsBundle\Helper;

class UserAgentValidator {

    protected ?bool $isAllowed = null;

    public function isAllowed(string $userAgent): bool
    {
        if(!is_null($this->isAllowed))
        {
            return $this->isAllowed;
        }

        $arrBanned = [
            'Baiduspider',
            'bingbot',
            'Googlebot',
            'Yahoo! Slurp',
            'MJ12bot',
            'MegaIndex',
            'AhrefsBot',
            'DotBot',
            'SemrushBot',
            'YandexBot',
            'msnbot',
            'seoscanners',
            'BLEXBot',
            'SeznamBot',
            'BingPreview',
            'Bytespider',
            'Mail.RU_Bot',
            'Pinterestbot',
            'Site24x7',
            'Go-http-client',
            'coccocbot',
            'AdsBot-Google',
            'facebookexternalhit',
            'PetalBot'
        ];

        foreach ($arrBanned as $disallowed)
        {
            if(str_contains($userAgent,$disallowed))
            {
                $this->isAllowed = false;
                return false;
            }
        }

        $this->isAllowed = true;
        return true;
    }
}
