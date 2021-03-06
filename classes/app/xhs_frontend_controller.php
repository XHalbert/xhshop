<?php
/**
 * Description of xhsfrontendcontroller
 *
 * @author Moritz
 */
class XHS_Frontend_Controller extends XHS_Controller {

    var $requiredCustomerData = array();

    function __construct() {
        parent::__construct();
        $this->requiredCustomerData = array('first_name', 'last_name',
            'street', 'zip_code', 'city', 'cos_confirmed',
            'email', 'payment_mode');
    }

    /**
     *
     * @return string returns info string about handling v.a.t
     *
     */
    function vatInfo() {
        if ($this->settings['dont_deal_with_taxes'] == 'true')
        {
            $info = 'price_info_no_vat';
        } else
        {
            $info = 'price_info_vat';
        }
        return $info;
    }

    function addToCartButton($product) {
        $params = array('productName' => $product->getName(XHS_LANGUAGE),
            'product'     => $product,
            'vatInfo'     => $this->vatInfo(),
            'vatRate'     => $this->settings['vat_' . $product->vat]);
        if ($product->hasVariants())
        {
            $params['variants'] = $product->getVariants(XHS_LANGUAGE);
        }
        return $this->render('addToCartButton', $params);
    }

    function updateCart() {
        $variant = null;

        if ($this->catalog->products[$_POST['cartItem']]->hasVariants())
        {
            if (isset($_POST['xhsVariant']))
            {
                $variant = (string) $_POST['xhsVariant'];
            } else
            {
                $variant = 0;
            }
        }

        if (!isset($_SESSION['xhsOrder']))
        {
            $_SESSION['xhsOrder'] = new XHS_Order($this->settings['vat_full'], $this->settings['vat_reduced']);
        }

        if ((int) $_POST['xhsAmount'] > 0)
        {
            $_SESSION['xhsOrder']->addItem($this->catalog->products[$_POST['cartItem']], $_POST['xhsAmount'], $variant);
        } else
        {
            $_SESSION['xhsOrder']->removeItem($this->catalog->products[$_POST['cartItem']], $variant);
        }
        $_SESSION['xhsOrder']->setShipping($this->calculateShipping());
    }

    function calculateShipping() {

        if ($this->settings['charge_for_shipping'] == 'false')
        {
            return 0;
        }
        if ($this->settings['shipping_up_to'] == 'true' &&
                $_SESSION['xhsOrder']->cartGross > (float) $this->settings['forwarding_expenses_up_to'])
        {
            return 0;
        }
        if (!isset($this->settings['weightRange']) || !count($this->settings['weightRange']) > 0)
        {
            return (float) $this->settings['shipping_max'];
        }
        $weight = $_SESSION['xhsOrder']->units;

        if (isset($this->settings['weightRange']))
        {
            foreach ($this->settings['weightRange'] as $key => $value)
            {

                if ($weight <= (float) $key)
                {
                    return (float) $value;
                }
            }
        }
        return (float) $this->settings['shipping_max'];
    }

    function calculatePaymentFee() {
        if (isset($_SESSION['xhsCustomer']->payment_mode))
        {
            if ($this->loadPaymentModule($_SESSION['xhsCustomer']->payment_mode))
            {
                return $this->paymentModules[$_SESSION['xhsCustomer']->payment_mode]->getFee();
            }
        }
        return $fee;
    }

    function cartPreview() {
        $cartItems = $this->collectCartItems();
        if ($cartItems)
        {
            $params = array();
            $params['xhs_url']   = $this->bridge->translateUrl(XHS_URL);
            $params['cartItems'] = $cartItems;
            $params['cartSum']   = $_SESSION['xhsOrder']->cartGross;
            return $this->render('cartPreview', $params);
        }
        return false;
    }

    /**
     *
     * @return array|bool either an array of products or false if cart is empty
     */
    function collectCartItems() {

        $cartItems = array();
        if (isset($_SESSION['xhsOrder']) && $_SESSION['xhsOrder']->hasItems())
        {

            $i = 1;
            foreach ($_SESSION['xhsOrder']->items as $index => $product)
            {
                $test = explode('_', $index);  // variants are marked as uid_variant
                if (!key_exists($test[0], $this->catalog->products))
                {
                    continue;
                } // if someone comes from another xhshopShop
                $cartItems[$index]['itemCounter'] = $i;
                $cartItems[$index]['id']          = $index;
                $cartItems[$index]['amount']      = $product['amount'];
                $productKey                       = $index;
                $variantName                      = '';
                $variantKey                       = '';
                if (strstr($index, '_'))
                {
                    $array       = explode('_', $index);
                    $productKey  = $array[0];
                    $variantKey  = $array[1];
                    $variantName = $this->catalog->products[$productKey]->getVariantName($variantKey);
                }

                $name       = $this->catalog->products[$productKey]->getName(XHS_LANGUAGE);
                $detailLink = '';
                $page       = $this->catalog->products[$productKey]->getDetailsLink(XHS_LANGUAGE);
                if ($page)
                {
                    $page                             = $this->bridge->translateUrl($page);
                    $name                             = $this->viewProvider->link($page, $name);
                    $detailLink                       = $this->viewProvider->link($page, $this->viewProvider->labels['product_info']);
                }
                $vatRate                          = 'vat_' . $this->catalog->products[$productKey]->vat;
                $vatRate                          = $this->settings[$vatRate];
                $cartItems[$index]['name']        = $name;
                $cartItems[$index]['key']         = $productKey;
                $cartItems[$index]['variantName'] = $variantName;
                $cartItems[$index]['variantKey']  = $variantKey;
                $cartItems[$index]['productPage'] = $this->catalog->products[$productKey]->getPage(XHS_LANGUAGE);
                $cartItems[$index]['description'] = $this->catalog->products[$productKey]->getTeaser(XHS_LANGUAGE);
                $cartItems[$index]['detailLink']  = $detailLink;
                $cartItems[$index]['price']       = $product['gross'];
                $cartItems[$index]['vatRate']     = $vatRate;
                $cartItems[$index]['sum']         = $product['gross'] * $product['amount'];
                if ($this->catalog->products[$productKey]->previewPicture)
                {
                    $cartItems[$index]['previewPicture'] = XHS_IMAGE_PATH . $this->catalog->products[$productKey]->previewPicture;
                }

                $i++;
            }
            return $cartItems;
        }

        return false;
    }

    function cart() {
        $cartItems = $this->collectCartItems();
        if (!$cartItems)
        {
            return $this->productList();
        }
        foreach ($cartItems as $key => $item)
        {
            if (strlen(trim($item['variantName'])) > 0)
            {
                $cartItems[$key]['variantName'] = ', ' . $item['variantName'];
            }
        }
        if ($cartItems)
        {
            $params = array();
            $params['cartItems']        = $cartItems;
            $params['shipping_limit']   = $this->settings['shipping_up_to'];
            $params['cartSum']          = $_SESSION['xhsOrder']->cartGross;
            $params['units']            = $_SESSION['xhsOrder']->units;
            $params['unitName']         = $this->settings[XHS_LANGUAGE]['shipping_unit'];
            $params['shipping']         = $_SESSION['xhsOrder']->shipping;
            $params['total']            = $_SESSION['xhsOrder']->shipping + $_SESSION['xhsOrder']->cartGross;
            $params['vatTotal']         = $_SESSION['xhsOrder']->getVat();
            $params['vatFull']          = $_SESSION['xhsOrder']->getVatFull();
            $params['vatReduced']       = $_SESSION['xhsOrder']->getVatReduced();
            $params['minimum_order']    = $this->settings['minimum_order'];
            $params['no_shipping_from'] = $this->settings['forwarding_expenses_up_to'];
            $params['canOrder']         = (float) $this->settings['minimum_order'] <= $_SESSION['xhsOrder']->getTotal();

            return $this->render('cart', $params);
        }
        return false;
    }

    function customersData($missingData = array()) {

        if (!isset($_SESSION['xhsCustomer']))
        {
            $customer                = new XHS_Customer();
            $_SESSION['xhsCustomer'] = $customer;
        }

        foreach ($this->payments as $name)
        {
            $this->loadPaymentModule($name);
        }
        if (!isset($_SESSION['xhsCustomer']->payment_mode))
        {
            foreach ($this->paymentModules as $module)
            {
                if ($module->isActive())
                {
                    $_SESSION['xhsCustomer']->payment_mode = $module->getName();
                    break;
                }
            }
        }
        $params['payments']    = $this->paymentModules;
        $params['missingData'] = $missingData;

        //  $params['cosUrl'] = uenc($this->settings[XHS_LANGUAGE]['cos_page']);
        $params['cosUrl'] = ($this->settings[XHS_LANGUAGE]['cos_page']);

        return $this->render('customersData', $params);
    }

    function checkCustomersData() {
        $missingData = array();
        $postArray = array();
        foreach ($_POST as $key => $value)
        {
            $postArray[$key] = trim($value);
        }
        foreach ($_SESSION['xhsCustomer'] as $field => $value)
        {
            if (key_exists($field, $postArray))
            {
                $_SESSION['xhsCustomer']->$field = $postArray[$field];
                if (in_array($field, $this->requiredCustomerData)
                        && (strlen($postArray[$field]) == 0 || !isset($postArray[$field])))
                {
                    $missingData[] = $field;
                }
            }
        }
        if (!isset($_SESSION['xhsCustomer']->cos_confirmed))
        {
            $missingData[] = 'cos_confirmed';
        }
        if (!isset($_SESSION['xhsCustomer']->payment_mode))
        {
            $missingData[] = 'payment_mode';
        }
        if (count($missingData) > 0)
        {
            return $this->customersData($missingData);
        } else
        {
            return $this->finalConfirmation();
        }
    }

    function htmlConfirmation() {
        foreach ($_SESSION['xhsCustomer'] as $field => $value)
        {
            $params[$field]       = $value;
        }
        $params['fee']        = $this->calculatePaymentFee();
        $params['cartItems']  = $this->collectCartItems();
        $params['cartSum']    = $_SESSION['xhsOrder']->getCartSum();
        $params['shipping']   = $_SESSION['xhsOrder']->getShipping();
        $params['total']      = $_SESSION['xhsOrder']->getTotal();
        $params['vatTotal']   = $_SESSION['xhsOrder']->getVat();
        $params['vatFull']    = $_SESSION['xhsOrder']->getVatFull();
        $params['vatReduced'] = $_SESSION['xhsOrder']->getVatReduced();
        $params['company']    = $this->settings['company_name'];
        $params['payment']    = $this->paymentModules[$_SESSION['xhsCustomer']->payment_mode]->getLabelString();
        if ($this->settings['dont_deal_with_taxes'] == 'true')
        {
            $params['hideVat'] = true;
        } else
        {
            $params['hideVat']     = false;
            $params['fullRate']    = $this->settings['vat_full'];
            $params['reducedRate'] = $this->settings['vat_reduced'];
        }
        return $this->render('confirmation_email/html', $params);
    }

    function textConfirmation() {
        foreach ($_SESSION['xhsCustomer'] as $field => $value)
        {
            $params[$field]       = $value;
        }
        $params['fee']        = $this->calculatePaymentFee();
        $params['cartItems']  = $this->collectCartItems();
        $params['cartSum']    = $_SESSION['xhsOrder']->getCartSum();
        $params['shipping']   = $_SESSION['xhsOrder']->getShipping();
        $params['total']      = $_SESSION['xhsOrder']->getTotal();
        $params['vatTotal']   = $_SESSION['xhsOrder']->getVat();
        $params['vatFull']    = $_SESSION['xhsOrder']->getVatFull();
        $params['vatReduced'] = $_SESSION['xhsOrder']->getVatReduced();
        $params['company']    = $this->settings['company_name'];
        $params['payment']    = $this->paymentModules[$_SESSION['xhsCustomer']->payment_mode]->getLabelString();
        if ($this->settings['dont_deal_with_taxes'] == 'true')
        {
            $params['hideVat'] = true;
        } else
        {
            $params['hideVat']     = false;
            $params['fullRate']    = $this->settings['vat_full'];
            $params['reducedRate'] = $this->settings['vat_reduced'];
        }
        return $this->render('confirmation_email/text', $params);
    }

    function finalConfirmation() {

        $fee           = $this->calculatePaymentFee();
        $paymentModule = $this->paymentModules[$_SESSION['xhsCustomer']->payment_mode];
        if ($paymentModule->wantsCartItems() !== false)
        {
            $paymentModule->setCartItems($this->collectCartItems());
            $paymentModule->setShipping($_SESSION['xhsOrder']->getShipping());
        }

        foreach ($_SESSION['xhsCustomer'] as $field => $value)
        {
            $params[$field]       = isset($value) ? $value : '';
        }
        $_SESSION['xhsOrder']->setFee($fee);
        $params['payment']    = $paymentModule;
        $params['fee']        = $fee;
        $params['cartItems']  = $this->collectCartItems();
        $params['cartSum']    = $_SESSION['xhsOrder']->getCartSum();
        $params['shipping']   = $_SESSION['xhsOrder']->getShipping();
        $params['total']      = $_SESSION['xhsOrder']->getTotal();
        $params['vatTotal']   = $_SESSION['xhsOrder']->getVat();
        $params['vatFull']    = $_SESSION['xhsOrder']->getVatFull();
        $params['vatReduced'] = $_SESSION['xhsOrder']->getVatReduced();
        if ($this->settings['dont_deal_with_taxes'] == 'true')
        {
            $params['hideVat'] = true;
        } else
        {
            $params['hideVat']     = false;
            $params['fullRate']    = $this->settings['vat_full'];
            $params['reducedRate'] = $this->settings['vat_reduced'];
        }

        return $this->render('finalConfirmation', $params);
    }

    /**
     *
     * @return <string>
     */
    function finishCheckOut() {

        $bill = $this->writeBill();
        if (!$bill)
        {
            $error = '<p>Sorry! Your order could not be processed!';
            $error .= '<p>Please try again inform us by email: <a href="mailto:' . $this->settings['order_email'] . '">' . $this->settings['order_email'] . '</a></p>';

            return $error;
        }

        $sent = $this->sendEmails($bill);

        if ($sent === true)
        {
            return $this->thankYou();
        } else
        {
            return $sent;
        }
    }

    function writeBill() {

        require_once('billwriter.php');
        $writer = new XHS_BillWriter();
        $rows   = '';
        if (XHS_LANGUAGE == 'de')
        {
            setlocale(LC_ALL, "de_DE", "ge", "de", "DE", "de_DE@euro", "deu_deu");
        }
        $datum = strftime('%A, %d. %B %Y');
        //   var_dump(utf8_encode($datum));

        $writer->setCurrency($this->settings['default_currency']);
        $currency = ' ' . $writer->getCurrency();
        foreach ($this->collectCartItems() as $product)
        {
            $name    = strip_tags($product['name']) . ' ' . $product['variantName'];
            $price   = $this->viewProvider->formatFloat($product['price']) . $currency;
            $sum     = $this->viewProvider->formatFloat($product['sum']) . $currency;
            $amount  = $product['amount'] . ' ';
            $vatRate = '(' . $this->viewProvider->labels['get_vat'] . $product['vatRate'] . ' %)';
            if ($this->settings['dont_deal_with_taxes'] == 'true')
            {
                $vatRate = '';
            }
            $rows .= $writer->writeProductRow($name, $amount, $price, $sum, $vatRate);
        }
        $fee     = $this->calculatePaymentFee();

        if ($fee < 0)
        {
            $feeLabel = $this->viewProvider->labels['reduction'];
        } else
        {
            $feeLabel = $this->viewProvider->labels['fee'];
        }

        if ($this->settings['dont_deal_with_taxes'] == 'true')
        {
            $vat_hint = $this->viewProvider->hints['no_vat_bill'];
        } else
        {
            $currency = ' ' . $writer->getCurrency();
            $vat_hint = $this->viewProvider->labels['included_vat'] . ' ' . $this->viewProvider->formatFloat($_SESSION['xhsOrder']->getVat()) . $currency;
            $vat_hint .= ' (' . $this->settings['vat_reduced'] . '%: ' . $this->viewProvider->formatFloat($_SESSION['xhsOrder']->getVatReduced()) . $currency . ' - ';
            $vat_hint .= $this->settings['vat_full'] . '%: ' . $this->viewProvider->formatFloat($_SESSION['xhsOrder']->getVatFull()) . $currency . ')';
        }

        $subtotal     = $_SESSION['xhsOrder']->getCartSum();
        $shipping     = $_SESSION['xhsOrder']->getShipping();
        $replacements = array('%Datum%'          => utf8_encode(strftime('%A, %d. %B %Y')),
            '%Vorname%'        => $_SESSION['xhsCustomer']->first_name,
            '%Nachname%'       => $_SESSION['xhsCustomer']->last_name,
            '%Strasse%'        => $_SESSION['xhsCustomer']->street,
            '%PLZ%'            => $_SESSION['xhsCustomer']->zip_code,
            '%Ort%'            => $_SESSION['xhsCustomer']->city,
            '%COMPANY_NAME%'   => $this->settings['company_name'],
            '%COMPANY_STREET%' => $this->settings['street'],
            '%COMPANY_ZIP%'    => $this->settings['zip_code'],
            '%COMPANY_CITY%'   => $this->settings['city'],
            '%SUMME%'          => $this->viewProvider->formatFloat($subtotal),
            '%SHIPPING%'       => $this->viewProvider->formatFloat($shipping),
            '%rows%'           => $rows,
            '%FEE_LABEL%'      => $feeLabel,
            '%FEE%'            => $this->viewProvider->formatFloat($fee),
            '%ENDSUMME%'       => $this->viewProvider->formatFloat($subtotal + $shipping + $fee),
            '%MWST_HINWEIS%'   => $vat_hint
        );

        if (!$writer->loadTemplate(XHS_BILLS_PATH . 'template.rtf'))
        {
            return 'template for bill not found';
        }
        $writer->replace($replacements);

        $writer->saveBill();
        return $writer->replace($replacements);
    }

    function sendEmails($bill) {
        require_once(XHS_BASE_PATH . 'classes/phpmailer/class.phpmailer.php');
        $mail = new PHPMailer();
        $mail->WordWrap = 60;
        $mail->IsHTML(true);
        $mail->set('CharSet', 'UTF-8');

        $customer     = $_SESSION['xhsCustomer']->email;
        $customerName = $_SESSION['xhsCustomer']->first_name . ' ' . $_SESSION['xhsCustomer']->last_name;

        $mail->From = $this->settings['order_email'];
        $mail->FromName = $this->settings['company_name'];
        $mail->AddReplyTo($this->settings['order_email'], $this->settings['company_name']);
        $mail->AddAddress($customer, $customerName);
        $mail->Subject = $this->viewProvider->mail['email_subject'] . ' - ' . $this->settings['company_name'];

        //     $mail->AddStringAttachment($bill, "bill.rtf");
        $mail->Body = $this->htmlConfirmation();
        $mail->AltBody = $this->textConfirmation();
        if (!$mail->Send())
        {
            $error = "<p>Sorry! Your message could not be sent. <p>";
            $error .= '<p>Please inform us by email: <a href="mailto:' . $this->settings['order_email'] . '">' . $this->settings['order_email'] . '</a></p>';
            $error .= "<p>Mailer Error: " . $mail->ErrorInfo . '</p>';
            return $error;
        }

        $mail->ClearAddresses();
        $mail->AddAddress($this->settings['order_email'], $this->settings['company_name']);
        $mail->Subject = $this->viewProvider->mail['notify'];
        $mail->AddStringAttachment($bill, "bill.rtf");
        $mail->Body = $this->htmlConfirmation();
        $mail->AltBody = $this->textConfirmation();
        if (!$mail->Send())
        {
            $error = "<p>Sorry! Although an email confirmation has been sent to you, your order was not transmitted to our shop! <p>";
            $error .= '<p>Please inform us by email: <a href="mailto:' . $this->settings['order_email'] . '">' . $this->settings['order_email'] . '</a></p>';
            $error .= "Mailer Error: " . $mail->ErrorInfo;
            return $error;
        }

        //  echo "Message has been sent";
        return true;
    }

    function thankYou() {
        $params['name'] = $_SESSION['xhsCustomer']->first_name . ' ' . $_SESSION['xhsCustomer']->last_name;

        $_SESSION['xhsCustomer'] = false;
        $_SESSION['xhsOrder'] = false;
        unset($_SESSION['xhsCustomer']);
        unset($_SESSION['xhsOrder']);

        return $this->render('thankYou', $params);
    }

    function productList($collectAll = true) {
        $params                       = parent::productList(false);
        $params['showCategorySelect'] = $this->settings['use_categories'] !== 'false';

        return $this->render('catalog', $params);
    }

    /**
     *
     * @param <string> $needle
     * @return <string> the product list rendered in catalog.tpl
     */
    function productSearchList($needle = '') {
        $params                       = parent::productSearchList($needle);
        $params['showCategorySelect'] = $this->settings['use_categories'] !== 'false';

        return $this->render('catalog', $params);
    }

    function productDetails() {
        $product = $this->catalog->getProduct($_GET['xhsProduct']);
        if (!$product)
        {
            return $this->productList();
        }
        $params = array();
        $params['name']        = $product->getName();
        $params['teaser']      = $product->getTeaser();
        $params['description'] = $product->getDescription();
        $params['button']      = $this->addToCartButton($product);
        $params['variants']    = count($product->getVariants() > 0) ? $product->getVariants() : false;
        $params['price']       = $product->price;
        $params['uid']         = $product->uid;
        $params['vatRate']     = $this->settings['vat_' . $product->vat];
        $params['vatInfo']     = $this->vatInfo();
        $params['image'] = '';
        $pic             = $product->getBestPicture();
        if ($pic)
        {
            $info            = getimagesize($pic);
            $params['image'] = '<a href="' . $pic . '" ' . $info[3] . ' class="zoom"><img src="' . $pic . '" ' . $info[3] . '></a>';
        }
        $this->bridge->setTitle($params['name']);
        $this->bridge->setMeta('description', $params['teaser']);
        return $this->render('productDetails', $params);
    }

    function closed() {
        $params = array();

        return $this->render('closed', $params);
    }

    function shopToc($level = 6) {
        if ($this->settings['use_categories'] == 'false')
        {
            return;
        }
        $params = array();
        $url                   = $this->bridge->translateUrl(XHS_URL);
        $params['shopUrl']     = $url;
        $params['shopHeading'] = $this->bridge->getHeadingOfUrl(XHS_URL);
        $params['categories']  = array();
        if ($this->settings['allow_show_all'] == 'true')
        {
            $params['categories'][0]['url']  = urlencode($this->viewProvider->labels['all_categories']);
            $params['categories'][0]['name'] = $this->viewProvider->labels['all_categories'];
        }
        $cats                            = $this->categories();
        $i                               = 1;
        foreach ($cats as $cat)
        {
            $params['categories'][$i]['url']  = urlencode($cat);
            $params['categories'][$i]['name'] = $cat;
            $i++;
        }

        if ($this->catalog->hasUncategorizedProducts())
        {
            $i++;
            $params['categories'][$i]['url']  = 'left_overs';
            $params['categories'][$i]['name'] = $this->catalog->getFallbackCategory();
        }

        return $this->render('shopToc', $params);
    }

    function handleRequest($request = null) {
         if (isset($_POST['ipn_track_id']))
        {
            $this->loadPaymentModule('paypal');
            $this->paymentModules['paypal']->ipn();
        }
        if (file_exists(XHS_BASE_PATH . 'classes/paymentmodules/paypal/tmp_orders/pp_' . session_id() . '.sent'))
        {
            unlink(XHS_BASE_PATH . 'classes/paymentmodules/paypal/tmp_orders/pp_' . session_id() . '.sent');
            return $this->thankYou();
        }
        if ($this->settings['published'] == 'false')
        {
            return $this->closed();
        }
        if (isset($_GET['xhsProduct']))
        {
            return $this->productDetails();
        }
        if (isset($_POST['xhsProductSearch']))
        {
            return $this->productSearchList($_POST['xhsProductSearch']);
        }
        $checkOut = '';
        if (isset($_POST['xhsCheckout']))
        {
            $checkOut = $_POST['xhsCheckout'];
        }

        switch ($checkOut)
        {
            case 'cart': return $this->cart();
                break;
            case 'customersData': return $this->customersData();
                break;
            case 'checkCustomersData': return $this->checkCustomersData();
                break;
            case 'finish': return $this->finishCheckOut();
                break;
            default: return $this->productList();
                break;
        }

        return;
    }
}
?>