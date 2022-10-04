<?php

namespace Doublespark\IsotopeAnalyticsBundle\BrowserEvent;

use Contao\FrontendTemplate;

class BrowserEvent
{
    /**
     * Add a browser event
     * @param string $name
     * @param FrontendTemplate $objEventTemplate
     */
    public static function add(string $name, FrontendTemplate $objEventTemplate)
    {
        $_SESSION['DS_ANALYTICS_BROWSER_EVENTS'][$name] = $objEventTemplate->parse();
    }

    /**
     * @return string
     */
    public static function getHtml(): string
    {
        if(isset($_SESSION['DS_ANALYTICS_BROWSER_EVENTS']) AND is_array($_SESSION['DS_ANALYTICS_BROWSER_EVENTS']))
        {
            return trim(implode(PHP_EOL,$_SESSION['DS_ANALYTICS_BROWSER_EVENTS']));
        }

        return '';
    }

    /**
     * Reset actions
     */
    public static function reset(): void
    {
        $_SESSION['DS_ANALYTICS_BROWSER_EVENTS'] = [];
    }
}