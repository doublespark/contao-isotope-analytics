<?php

namespace Doublespark\IsotopeAnalyticsBundle\Helper;

use Contao\Environment;
use FacebookAds\Object\ServerSide\UserData;

class PixelHelper {

    /**
     * Get the current event ID, creating one if it hasn't been created yet
     * @return string
     */
    public static function getEventId($strKey=''): string
    {
        if(!isset($GLOBALS['DS_ANALYTICS']) || empty($GLOBALS['DS_ANALYTICS']))
        {
            $GLOBALS['DS_ANALYTICS'] = substr(md5(time()), 0, 8);
        }

        return $strKey.$GLOBALS['DS_ANALYTICS'];
    }

    /**
     * Get the UserData object for the current request
     * @return UserData
     */
    public static function getUserData(): UserData
    {
        $userData = new UserData();

        $fbp = $_COOKIE['_fbp'] ?? null;

        if($fbp)
        {
            $userData->setFbp($fbp);
        }

        $fbc = $_COOKIE['_fbc'] ?? null;

        if($fbc)
        {
            $userData->setFbc($fbc);
        }
        
        $userData->setClientIpAddress(Environment::get('ip'));
        $userData->setClientUserAgent(Environment::get('httpUserAgent'));

        return $userData;
    }
}