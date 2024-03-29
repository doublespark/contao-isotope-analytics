<?php

namespace Doublespark\IsotopeAnalyticsBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Environment;
use Contao\Input;
use Contao\PageRegular;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\System;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\GoogleEvent;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\PixelEvent;
use Doublespark\IsotopeAnalyticsBundle\Helper\PixelHelper;
use Doublespark\IsotopeAnalyticsBundle\Helper\UserAgentValidator;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\CustomData;
use Isotope\Isotope;
use Isotope\Model\ProductCollection\Order;
use Isotope\Module\Checkout;
use Psr\Log\LogLevel;

/**
 * @Hook("generatePage")
 */
class GeneratePageListener
{
    /**
     * @param PageModel $pageModel
     * @param LayoutModel $layout
     * @param PageRegular $pageRegular
     */
    public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
    {
        // Only track FE pages
        if(TL_MODE !== 'FE')
        {
            return;
        }

        // Do not track 404s
        if($pageModel->type === 'error_404')
        {
            return;
        }

        /**
         * Checkout
         */
        if(intval($pageModel->id) === intval(Config::get('ds_analytics_checkout_page')))
        {
            $this->checkoutView();
        }

        /**
         * Order complete
         */
        // See if we're on the "order placed" screen shown after a confirmed order
        if(intval($pageModel->id) === intval(Config::get('ds_analytics_complete_page')))
        {
            $this->orderPlaced();
        }

        if(System::getContainer()->get('ds.analytics.user_agent_validator')->isAllowed(Environment::get('httpUserAgent')))
        {
            $this->sendPixelPageView();
        }
    }

    /**
     * Send page view
     */
    public function sendPixelPageView()
    {
        $eventId = PixelHelper::getEventId('pv');

        PixelEvent::pageView($eventId);

        $pixelEnabled  = Config::get('ds_analytics_enable_pixel');
        $access_token  = Config::get('ds_analytics_pixel_token');
        $pixel_id      = Config::get('ds_analytics_pixel_id');

        // Make sure pixel tracking is enabled and that we have the required fields
        if($pixelEnabled && !empty($access_token) && !empty($pixel_id))
        {
            Api::init(null,null,$access_token);

            $url = Environment::get('uri');

            $event = (new Event())
                ->setEventId($eventId)
                ->setEventName('PageView')
                ->setEventTime(time())
                ->setEventSourceUrl($url)
                ->setUserData(PixelHelper::getUserData())
                ->setActionSource(ActionSource::WEBSITE);

            $arrEvents = [$event];

            $request = (new EventRequest($pixel_id))->setEvents($arrEvents);

            if(Config::get('ds_analytics_pixel_debug'))
            {
                $code = Config::get('ds_analytics_pixel_testcode');

                if(!empty($code))
                {
                    $request->setTestEventCode($code);
                }
            }

            try {

                $response = $request->execute();

            } catch(\Exception $e) {

                System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, "Failed to send Conversion API 'PageView' event: ". $e->getMessage(), array('contao' => new ContaoContext('GeneratePageListener::orderPlaced', TL_ERROR)));

            }
        }
    }

    /**
     * Checkout page
     */
    protected function checkoutView()
    {
        $step = Input::get('auto_item');

        $googleEnabled = Config::get('ds_analytics_enable_google');
        $pixelEnabled  = Config::get('ds_analytics_enable_pixel');

        // Checkout steps
        if($step === Checkout::STEP_ADDRESS)
        {
            if($googleEnabled)
            {
                GoogleEvent::checkoutBegin();
            }

            if($pixelEnabled)
            {
                $eventId = PixelHelper::getEventId('ic');

                PixelEvent::initiateCheckout($eventId);

                $access_token  = Config::get('ds_analytics_pixel_token');
                $pixel_id      = Config::get('ds_analytics_pixel_id');

                if(!empty($access_token) && !empty($pixel_id))
                {
                    Api::init(null,null,$access_token);

                    $arrContent = [];

                    // Load cart
                    $objCart = Isotope::getCart();

                    // Get products
                    foreach ($objCart->getItems() as $objProductCollectionItem)
                    {
                        $content = new Content();
                        $content->setProductId($objProductCollectionItem->sku)
                            ->setQuantity($objProductCollectionItem->quantity)
                            ->setItemPrice($objProductCollectionItem->price)
                            ->setTitle($objProductCollectionItem->name);

                        // Group by SKU
                        if(isset($arrContent[$objProductCollectionItem->sku]))
                        {
                            $arrContent[$objProductCollectionItem->sku]->setQuantity($arrContent[$objProductCollectionItem->sku]->getQuantity() + $objProductCollectionItem->quantity);
                        }
                        else
                        {
                            $arrContent[$objProductCollectionItem->sku] = $content;
                        }
                    }

                    // Convert from keyed to indexed array
                    $arrContent = array_values($arrContent);

                    $objConfig = Isotope::getConfig();

                    $customData = (new CustomData())
                        ->setContents($arrContent)
                        ->setCurrency($objConfig->currency)
                        ->setValue($objCart->getTotal());

                    $url = strtok(Environment::get('uri'), '?');

                    $event = (new Event())
                        ->setEventId($eventId)
                        ->setEventName('InitiateCheckout')
                        ->setEventTime(time())
                        ->setEventSourceUrl($url)
                        ->setUserData(PixelHelper::getUserData())
                        ->setCustomData($customData)
                        ->setActionSource(ActionSource::WEBSITE);

                    $arrEvents = [$event];

                    $request = (new EventRequest($pixel_id))->setEvents($arrEvents);

                    if(Config::get('ds_analytics_pixel_debug'))
                    {
                        $code = Config::get('ds_analytics_pixel_testcode');

                        if(!empty($code))
                        {
                            $request->setTestEventCode($code);
                        }
                    }

                    try {

                        $response = $request->execute();

                        System::getContainer()->get('monolog.logger.contao')->log(LogLevel::INFO, "Sent Conversion API 'InitiateCheckout' event", array('contao' => new ContaoContext('GeneratePageListener::checkoutView', TL_GENERAL)));

                    } catch(\Exception $e) {

                        System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, "Failed to send Conversion API 'InitiateCheckout' event: ". $e->getMessage(), array('contao' => new ContaoContext('GeneratePageListener::checkoutView', TL_ERROR)));

                    }
                }

            }
        }

        // If we're on the payment step, customer has just set their shipping info
        if($step === Checkout::STEP_PAYMENT)
        {
            if($googleEnabled)
            {
                GoogleEvent::addShippingInfo();
            }
        }

        // If we're on the review step, customer has set their payment info
        if($step === Checkout::STEP_REVIEW)
        {
            if($googleEnabled)
            {
                GoogleEvent::addPaymentInfo();
            }
        }
    }

    /**
     * Order placed page
     */
    protected function orderPlaced()
    {
        $orderUid = Input::get('uid');

        if($orderUid)
        {
            $objOrder = Order::findOneBy('uniqid',$orderUid);

            if($objOrder)
            {
                $googleEnabled = Config::get('ds_analytics_enable_google');
                $pixelEnabled  = Config::get('ds_analytics_enable_pixel');
                $access_token  = Config::get('ds_analytics_pixel_token');
                $pixel_id      = Config::get('ds_analytics_pixel_id');

                // Make sure pixel tracking is enabled and that we have the required fields
                if($pixelEnabled && !empty($access_token) && !empty($pixel_id))
                {
                    Api::init(null,null,$access_token);

                    $arrContent = [];

                    // Get products
                    foreach ($objOrder->getItems() as $objProductCollectionItem)
                    {
                        $content = new Content();
                        $content->setProductId($objProductCollectionItem->sku)
                            ->setQuantity($objProductCollectionItem->quantity)
                            ->setItemPrice($objProductCollectionItem->price)
                            ->setTitle($objProductCollectionItem->name);

                        // Group by SKU
                        if(isset($arrContent[$objProductCollectionItem->sku]))
                        {
                            $arrContent[$objProductCollectionItem->sku]->setQuantity($arrContent[$objProductCollectionItem->sku]->getQuantity() + $objProductCollectionItem->quantity);
                        }
                        else
                        {
                            $arrContent[$objProductCollectionItem->sku] = $content;
                        }
                    }

                    // Convert from keyed to indexed array
                    $arrContent = array_values($arrContent);

                    $customData = (new CustomData())
                        ->setContents($arrContent)
                        ->setCurrency($objOrder->currency)
                        ->setValue($objOrder->getTotal());

                    $url = strtok(Environment::get('uri'), '?');

                    $eventId = PixelHelper::getEventId('op');

                    $event = (new Event())
                        ->setEventId($eventId)
                        ->setEventName('Purchase')
                        ->setEventTime(time())
                        ->setEventSourceUrl($url)
                        ->setUserData(PixelHelper::getUserData())
                        ->setCustomData($customData)
                        ->setActionSource(ActionSource::WEBSITE);

                    $arrEvents = [$event];

                    $request = (new EventRequest($pixel_id))->setEvents($arrEvents);

                    if(Config::get('ds_analytics_pixel_debug'))
                    {
                        $code = Config::get('ds_analytics_pixel_testcode');

                        if(!empty($code))
                        {
                            $request->setTestEventCode($code);
                        }
                    }

                    try {

                        $response = $request->execute();

                        System::getContainer()->get('monolog.logger.contao')->log(LogLevel::INFO, "Sent Conversion API 'AddToCart' event", array('contao' => new ContaoContext('GeneratePageListener::orderPlaced', TL_GENERAL)));

                    } catch(\Exception $e) {

                        System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, "Failed to send Conversion API 'Purchase' event: ". $e->getMessage(), array('contao' => new ContaoContext('GeneratePageListener::orderPlaced', TL_ERROR)));

                    }

                    // Fire the browser event
                    PixelEvent::orderPlaced($eventId,$objOrder);
                }

                if($googleEnabled)
                {
                    // Fire browser event
                    GoogleEvent::orderPlaced($objOrder);
                }
            }
        }
    }
}