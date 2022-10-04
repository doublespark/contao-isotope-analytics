<?php

namespace Doublespark\IsotopeAnalyticsBundle\EventListener;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\BrowserEvent;

/**
 * @Hook("modifyFrontendPage")
 */
class ModifyFrontendPageListener
{
    /**
     * @param string $buffer
     * @param string $templateName
     * @return string
     */
    public function __invoke(string $buffer, string $templateName): string
    {
        if (!empty($templateName) && 0 !== strncmp($templateName, 'fe_', 3))
        {
            return $buffer;
        }

        $eventsHTML = BrowserEvent::getHtml();

        if(!empty($eventsHTML))
        {
            $buffer = str_replace('</body>', "$eventsHTML</body>", $buffer);
        }

        BrowserEvent::reset();

        return $buffer;
    }
}