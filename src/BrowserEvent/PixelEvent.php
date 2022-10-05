<?php

namespace Doublespark\IsotopeAnalyticsBundle\BrowserEvent;

use Contao\FrontendTemplate;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollection\Order;

class PixelEvent {

    /**
     * Page view
     * @param string $eventId
     */
    public static function pageView(string $eventId)
    {
        $objEvent = new FrontendTemplate('ecom_analytics_pixel_pageview');

        $objEvent->setData([
            'event_id' => $eventId
        ]);

        BrowserEvent::add('ecom_analytics_pixel_pageview',$objEvent);
    }

    /**
     * Fire an add to cart event
     * @param string $eventId
     * @param IsotopeProduct $objProduct
     * @param int $intQty
     */
    public static function addToCart(string $eventId, IsotopeProduct $objProduct, int $intQty=1): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_pixel_addtocart');

        $arrContents = [
            [
                'id'         => $objProduct->getSku(),
                'quantity'   => $intQty,
                'item_price' => $objProduct->getPrice()->getAmount()
            ]
        ];

        $objEvent->setData([
            'event_id'     => $eventId,
            'content_type' => 'product',
            'contents'     => json_encode($arrContents),
            'content_ids'  => "['".$objProduct->getSku()."']",
            'content_name' => $objProduct->getName(),
            'currency'     => 'GBP',
            'value'        => $objProduct->getPrice()->getAmount() * $intQty
        ]);

        BrowserEvent::add('ecom_analytics_pixel_addtocart',$objEvent);
    }

    /**
     * Initiate checkout
     * @param string $eventId
     */
    public static function initiateCheckout(string $eventId)
    {
        $objEvent = new FrontendTemplate('ecom_analytics_pixel_initiatecheckout');

        $arrContents   = [];
        $arrContentIds = [];
        $itemCount     = 0;

        // Load cart
        $objCart = Isotope::getCart();

        // Get products
        foreach ($objCart->getItems() as $objProductCollectionItem)
        {
            // Group by SKU
            if(isset($arrContents[$objProductCollectionItem->sku]))
            {
                $arrContents[$objProductCollectionItem->sku]['quantity']++;
            }
            else
            {
                $arrContents[$objProductCollectionItem->sku] = [
                    'id'         => $objProductCollectionItem->sku,
                    'quantity'   => $objProductCollectionItem->quantity,
                    'item_price' => $objProductCollectionItem->price
                ];
            }

            $itemCount = $itemCount + $objProductCollectionItem->quantity;

            $arrContentIds[$objProductCollectionItem->sku] = "'".$objProductCollectionItem->sku."'";
        }

        // Convert from keyed to indexed array
        $arrContents = array_values($arrContents);

        $objEvent->setData([
            'event_id'         => $eventId,
            'contents'         => json_encode($arrContents),
            'content_ids'      => "[".implode(',',$arrContentIds)."]",
            'num_items'        => $itemCount,
            'currency'         => 'gbp',
            'value'            => $objCart->getTotal()
        ]);

        BrowserEvent::add('ecom_analytics_pixel_initiatecheckout',$objEvent);
    }

    /**
     * @param string $eventId
     * @param Order $objOrder
     */
    public static function orderPlaced(string $eventId, Order $objOrder): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_pixel_orderplaced');

        $arrContents   = [];
        $arrContentIds = [];
        $itemCount     = 0;

        // Get products
        foreach ($objOrder->getItems() as $objProductCollectionItem)
        {
            // Group by SKU
            if(isset($arrContents[$objProductCollectionItem->sku]))
            {
                $arrContents[$objProductCollectionItem->sku]['quantity']++;
            }
            else
            {
                $arrContents[$objProductCollectionItem->sku] = [
                    'id'         => $objProductCollectionItem->sku,
                    'quantity'   => $objProductCollectionItem->quantity,
                    'item_price' => $objProductCollectionItem->price
                ];
            }

            $itemCount = $itemCount + $objProductCollectionItem->quantity;

            $arrContentIds[$objProductCollectionItem->sku] = "'".$objProductCollectionItem->sku."'";
        }

        // Convert from keyed to indexed array
        $arrContents = array_values($arrContents);

        $objEvent->setData([
            'event_id'     => $eventId,
            'content_type' => 'product',
            'contents'     => json_encode($arrContents),
            'content_ids'  => "[".implode(',',$arrContentIds)."]",
            'num_items'    => $itemCount,
            'content_name' => 'Order Placed',
            'currency'     => 'GBP',
            'value'        => $objOrder->getTotal()
        ]);

        BrowserEvent::add('ecom_analytics_pixel_orderplaced',$objEvent);
    }
}