<?php

namespace Doublespark\IsotopeAnalyticsBundle\BrowserEvent;

use Contao\FrontendTemplate;
use Contao\System;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Interfaces\IsotopeProductCollection;
use Isotope\Isotope;
use Isotope\Model\ProductCollection\Order;

class GoogleEvent {

    /**
     * @param IsotopeProduct $objProduct
     */
    public static function viewItem(IsotopeProduct $objProduct): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_viewitem');

        $objEvent->setData([
            'value'    => $objProduct->getPrice()->getAmount(),
            'currency' => 'GBP',
            'items'    => static::getItemFromProduct($objProduct,1)
        ]);

        if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsViewItem']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsViewItem'])) {
            foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsViewItem'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($objEvent,$objProduct);
            }
        }

        BrowserEvent::add('ecom_analytics_google_viewitem',$objEvent);
    }

    /**
     * Fire an add to cart event
     * @param IsotopeProduct $objProduct
     * @param IsotopeProductCollection $objCollection
     * @param int $intQty
     */
    public static function addToCart(IsotopeProduct $objProduct, IsotopeProductCollection $objCollection, int $intQty=1): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_addtocart');

        $objEvent->setData([
            'value'    => $objProduct->getPrice()->getAmount() * $intQty,
            'currency' => 'GBP',
            'items'    => static::getItemFromProduct($objProduct,$intQty)
        ]);

        if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsAddToCart']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsAddToCart'])) {
            foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsAddToCart'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($objEvent,$objProduct,$objCollection,$intQty);
            }
        }

        BrowserEvent::add('ecom_analytics_google_addtocart',$objEvent);
    }

    /**
     * Checkout process started
     */
    public static function checkoutBegin(): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_checkoutbegin');

        // Load cart
        $objCart = Isotope::getCart();

        // Get products
        $arrProducts = static::getItemsFromCollection($objCart);

        $objEvent->setData([
            'items' => implode(',',$arrProducts),
        ]);

        if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsCheckoutBegin']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsCheckoutBegin'])) {
            foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsCheckoutBegin'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($objEvent, $objCart);
            }
        }

        BrowserEvent::add('ecom_analytics_google_checkoutbegin',$objEvent);
    }

    /**
     * Set shipping info
     */
    public static function addShippingInfo(): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_addshippinginfo');

        // Load cart
        $objCart = Isotope::getCart();

        if($objCart->hasShipping())
        {
            // Get products
            $arrProducts = static::getItemsFromCollection($objCart);

            // Ship shipping info
            $objShipping = $objCart->getShippingMethod();

            $objEvent->setData([
                'currency'      => 'GBP',
                'shipping_tier' => $objShipping->getLabel(),
                'value'         => $objShipping->getPrice(),
                'items'         => implode(',',$arrProducts),
            ]);

            if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsAddShippingInfo']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsAddShippingInfo'])) {
                foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsAddShippingInfo'] as $callback) {
                    System::importStatic($callback[0])->{$callback[1]}($objEvent, $objCart);
                }
            }

            BrowserEvent::add('ecom_analytics_google_addshippinginfo',$objEvent);
        }
    }

    /**
     * Set payment info
     */
    public static function addPaymentInfo(): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_addpaymentinfo');

        // Load cart
        $objCart = Isotope::getCart();

        if($objCart->hasPayment())
        {
            // Get products
            $arrProducts = static::getItemsFromCollection($objCart);

            // Ship shipping info
            $objPayment = $objCart->getPaymentMethod();

            $objEvent->setData([
                'currency'      => 'GBP',
                'payment_type'  => $objPayment->getLabel(),
                'value'         => $objPayment->getPrice(),
                'items'         => implode(',',$arrProducts),
            ]);

            if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsAddPaymentInfo']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsAddPaymentInfo'])) {
                foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsAddPaymentInfo'] as $callback) {
                    System::importStatic($callback[0])->{$callback[1]}($objEvent, $objCart);
                }
            }

            BrowserEvent::add('ecom_analytics_google_addpaymentinfo',$objEvent);
        }
    }

    /**
     * Order placed
     * @param Order $objOrder
     */
    public static function orderPlaced(Order $objOrder): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_orderplaced');

        // Get products
        $arrProducts = static::getItemsFromCollection($objOrder);

        $shippingPrice = 0;

        if($objOrder->hasShipping())
        {
            $shippingPrice = $objOrder->getShippingMethod()->getPrice();
        }

        $objEvent->setData([
            'transaction_id' => $objOrder->document_number,
            'value'          => $objOrder->total,
            'shipping'       => $shippingPrice,
            'currency'       => 'GBP',
            'items'          => implode(',',$arrProducts),
        ]);

        if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsOrderPlaced']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsOrderPlaced'])) {
            foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsOrderPlaced'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($objEvent, $objOrder);
            }
        }

        BrowserEvent::add('ecom_analytics_google_orderplaced',$objEvent);
    }

    /**
     * Create value of "items" field from a product collection
     * @param IsotopeProductCollection $objCollection
     * @return array
     */
    protected static function getItemsFromCollection(IsotopeProductCollection $objCollection): array
    {
        $arrProducts = [];

        // Get products
        foreach($objCollection->getItems() as $objProductCollectionItem)
        {
            $arrProducts[] = static::getItemFromProduct($objProductCollectionItem->getProduct(), $objProductCollectionItem->quantity);
        }

        // Encode product data as JSON
        return $arrProducts;
    }

    /**
     * Get item JSON from a product object
     * @param IsotopeProduct $objProduct
     * @param int $intQty
     * @return string
     */
    protected static function getItemFromProduct(IsotopeProduct $objProduct, int $intQty=0): string
    {
        $itemJson = json_encode([
            'item_id'   => $objProduct->sku,
            'item_name' => $objProduct->name,
            'quantity'  => $intQty,
            'price'     => $objProduct->getPrice()->getAmount(),
            'currency'  => 'GBP'
        ]);

        if (isset($GLOBALS['DSA_HOOKS']['onAnalyticsGetItemFromProduct']) && \is_array($GLOBALS['DSA_HOOKS']['onAnalyticsGetItemFromProduct'])) {
            foreach ($GLOBALS['DSA_HOOKS']['onAnalyticsGetItemFromProduct'] as $callback) {
                $itemJson = System::importStatic($callback[0])->{$callback[1]}($objProduct, $intQty, $itemJson);
            }
        }

        return $itemJson;
    }
}