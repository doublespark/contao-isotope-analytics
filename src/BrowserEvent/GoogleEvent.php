<?php

namespace Doublespark\IsotopeAnalyticsBundle\BrowserEvent;

use Contao\FrontendTemplate;
use Isotope\Interfaces\IsotopeProduct;
use Isotope\Isotope;
use Isotope\Model\ProductCollection;
use Isotope\Model\ProductCollection\Order;

class GoogleEvent {

    /**
     * @param IsotopeProduct $objProduct
     */
    public static function viewItem(IsotopeProduct $objProduct): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_viewitem');

        $objEvent->setData([
            'value' => $objProduct->getPrice()->getAmount(),
            'currency' => 'GBP',
            'items' => json_encode([
                'item_id'   => $objProduct->getSku(),
                'item_name' => $objProduct->getName(),
                'price'     => $objProduct->getPrice()->getAmount(),
                'currency'  => 'GBP'
            ])
        ]);

        BrowserEvent::add('ecom_analytics_google_viewitem',$objEvent);
    }

    /**
     * Fire an add to cart event
     * @param IsotopeProduct $objProduct
     * @param int $intQty
     */
    public static function addToCart(IsotopeProduct $objProduct, int $intQty=1): void
    {
        $objEvent = new FrontendTemplate('ecom_analytics_google_addtocart');

        $objEvent->setData([
            'value' => $objProduct->getPrice()->getAmount() * $intQty,
            'currency' => 'GBP',
            'items' => json_encode([
                'item_id'   => $objProduct->getSku(),
                'item_name' => $objProduct->getName(),
                'quantity'  => $intQty,
                'price'     => $objProduct->getPrice()->getAmount(),
                'currency'  => 'GBP'
            ])
        ]);

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

        BrowserEvent::add('ecom_analytics_google_orderplaced',$objEvent);
    }

    /**
     * Create value of "items" field from a product collection
     * @param ProductCollection $objCollection
     * @return array
     */
    protected static function getItemsFromCollection(ProductCollection $objCollection): array
    {
        $arrProducts = [];

        // Get products
        foreach($objCollection->getItems() as $objProductCollectionItem)
        {
            $product = [
                'item_id'   => $objProductCollectionItem->sku,
                'item_name' => $objProductCollectionItem->name,
                'quantity'  => $objProductCollectionItem->quantity,
                'price'     => $objProductCollectionItem->price,
                'currency'  => 'GBP'
            ];

            // Group products by SKU
            if(isset($arrProducts[$objProductCollectionItem->sku]))
            {
                $arrProducts[$objProductCollectionItem->sku]['quantity']++;
            }
            else
            {
                $arrProducts[$objProductCollectionItem->sku] = $product;
            }
        }

        // Encode product data as JSON
        return array_map(function($item){return json_encode($item);},$arrProducts);
    }
}