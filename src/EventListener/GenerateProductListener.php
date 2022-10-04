<?php

namespace Doublespark\IsotopeAnalyticsBundle\EventListener;

use Contao\Input;
use Contao\Template;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\GoogleEvent;
use Isotope\Model\Product;
use Isotope\ServiceAnnotation\IsotopeHook;

/**
 * @IsotopeHook("generateProduct")
 */
class GenerateProductListener
{
    /**
     * @param Template $objTemplate
     * @param Product $objProduct
     */
    public function __invoke(Template $objTemplate, Product $objProduct): void
    {
        if(Input::get('auto_item') === $objProduct->alias)
        {
            GoogleEvent::viewItem($objProduct);
        }
    }
}