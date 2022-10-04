<?php

namespace Doublespark\IsotopeAnalyticsBundle\EventListener;

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Environment;
use Contao\System;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\GoogleEvent;
use Doublespark\IsotopeAnalyticsBundle\BrowserEvent\PixelEvent;
use Doublespark\IsotopeAnalyticsBundle\Helper\PixelHelper;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\Content;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\EventRequest;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Model\ProductCollection;
use Isotope\ServiceAnnotation\IsotopeHook;
use Psr\Log\LogLevel;

/**
 * @IsotopeHook("addProductToCollection")
 */
class AddProductToCollectionListener
{
    /**
     * @param IsotopeProduct $objProduct
     * @param int $intQuantity
     * @param ProductCollection $collection
     * @param array $arrConfig
     * @return int
     */
    public function __invoke(IsotopeProduct $objProduct, int $intQuantity, ProductCollection $collection, array $arrConfig): int
    {
        // See if we are adding to cart
        if($collection instanceof ProductCollection\Cart)
        {
            $googleEnabled = Config::get('ds_analytics_enable_google');
            $pixelEnabled  = Config::get('ds_analytics_enable_pixel');
            $access_token  = Config::get('ds_analytics_pixel_token');
            $pixel_id      = Config::get('ds_analytics_pixel_id');

            // Make sure pixel tracking is enabled and that we have the required fields
            if($pixelEnabled && !empty($access_token) && !empty($pixel_id))
            {
                Api::init(null,null,$access_token);

                $content = (new Content())
                    ->setProductId($objProduct->getSku())
                    ->setQuantity($intQuantity)
                    ->setTitle($objProduct->getName())
                    ->setItemPrice($objProduct->getPrice()->getAmount());

                $customData = (new CustomData())
                    ->setContents([$content])
                    ->setCurrency('gbp')
                    ->setValue($objProduct->getPrice()->getAmount() * $intQuantity);

                $url = Environment::get('uri');

                $eventId = PixelHelper::getEventId();

                $event = (new Event())
                    ->setEventId($eventId)
                    ->setEventName('AddToCart')
                    ->setEventTime(time())
                    ->setEventSourceUrl($url)
                    ->setUserData(PixelHelper::getUserData())
                    ->setCustomData($customData)
                    ->setActionSource(ActionSource::WEBSITE);

                $arrEvents = [$event];

                $request = (new EventRequest($pixel_id))->setEvents($arrEvents);

                try {
                    $response = $request->execute();
                } catch(\Exception $e) {
                    System::getContainer()->get('monolog.logger.contao')->log(LogLevel::ERROR, "Failed to send Conversion API 'AddToCart' event", array('contao' => new ContaoContext('AddProductToCollectionListener::__invoke', TL_ERROR)));
                }

                // Fire the browser event
                PixelEvent::addToCart($eventId,$objProduct,$intQuantity);
            }

            if($googleEnabled)
            {
                // Fire browser event
                GoogleEvent::addToCart($objProduct,$intQuantity);
            }
        }

        return $intQuantity;
    }
}