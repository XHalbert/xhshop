<?php
class XHS_Backend_Controller extends XHS_Controller {

    function __construct(){
        parent::__construct();
    }

    function handleRequest($request = null){
        $html = $this->tabs();
        $this->checkFilePermissions();
        if(count($this->errors) > 0) {
            $html .= $this->errorList();
        }
        $request = 'productList';
        if(isset($_POST['xhsTask'])){
            $request = $_POST['xhsTask'];
        }
        if(isset($_POST['xhsEPayment']) && in_array($_POST['xhsEPayment'], $this->payments)){
            $this->loadPaymentModule($_POST['xhsEPayment']);
            $this->paymentModules[$_POST['xhsEPayment']]->saveConfig();
        }
        if(method_exists($this, $request)){
            $html .=  $this->$request();
        } else{$html .=  parent::handleRequest($request);}
        return $html;
    }

    function productList($collectAll = true){
        if(isset($_POST['xhsProductSwapID']) && isset($_POST['xhsProductID'])  ){
            $myself = $this->catalog->getProduct($_POST['xhsProductID']);
            $swap = $this->catalog->getProduct($_POST['xhsProductSwapID']);
            $this->catalog->swapSortIndex($myself, $swap);
        }

        $indices    = array();
        $hints      = array();
        $errors     = array();

        $params = parent::productList();
        $params['category'] = isset($_POST['xhsCategory']) ? $_POST['xhsCategory'] : null;
        foreach($params['products'] as $index=>$product) {
            $indices[] = $index;
            if((float)$product['price'] <= 0){
                $errors[$index][] = 'no_price';
            }
            if(!$product['isAvailable']){
                $hints[$index][] = 'not_available';
            }
            if(strlen($product['previewPicture']) === 0){
                $hints[$index][] = 'no_preview_pic';
            }
            if(strlen($product['teaser']) === 0){
                $hints[$index][] = 'no_teaser';
            }
            if(strlen($product['description']) === 0 && count($product['pages']) === 0){
                $hints[$index][] = 'no_product_page';
            }
            foreach($product['pages'] as $temp){
                if(!$this->bridge->pageExists($temp)){
                    $errors[$index][] = 'page_not_found';
                }
            }
        }
        $params['indices'] = $indices;
        $params['caveats'] = $hints;
        $params['errors']  = $errors;
        $params['showCategorySelect'] = true;
        return $this->render('catalog', $params);
    }

    function editProduct($id = null){
        if(!isset($id)){
            $id = isset($_POST['xhsProductID']) ? $_POST['xhsProductID'] : 'new';
        }

        $params = array();

        $this->bridge->initProductDescriptionEditor();
        if(key_exists($id, $this->catalog->products)){
            $product = $this->catalog->products[$id];

            $params['product_ID']     = $id;
            $params['preview_selector'] = $this->viewProvider->picSelector(
				XHS_PREVIEW_PIC_PATH,
                $this->getImageFiles(XHS_PREVIEW_PIC_PATH),
                $product->getPreviewPictureName(),
				'xhsPreviewPic');
            $params['image_selector'] = $this->viewProvider->picSelector(XHS_IMAGE_PATH,
                $this->getImageFiles(XHS_IMAGE_PATH),
                $product->getImageName(),
				'xhsImage');
            $params['variants']       = $product->hasVariants() ? implode('; ', $product->getVariants(XHS_LANGUAGE)) : '';
            $params['name']           = $product->names[XHS_LANGUAGE];
            $params['teaser']         = $product->getTeaser(XHS_LANGUAGE);
            $params['description']    = $product->getDescription(XHS_LANGUAGE);
            $params['price']          = $product->getGross();
            $params['weight']         = $product->getWeight();
            $params['stockOnHand']    = is_int($product->stock_on_hand) ? $product->stock_on_hand : 1;
            $params['preview']        = $product->getPreviewPicture();
            $params['image']          = $product->getImage();
            $params['vat']            = $product->vat;
            $params['pages']          = $product->getProductPages();
            $params['productCats']    = $product->getCategories();
        }
        else{
            $params['product_ID']     = 'new';
            $params['preview_selector'] = $this->viewProvider->picSelector(
				XHS_PREVIEW_PIC_PATH,
				$this->getImageFiles(XHS_PREVIEW_PIC_PATH),
				null,
				'xhsPreviewPic');
            $params['image_selector'] = $this->viewProvider->picSelector(
				XHS_IMAGE_PATH,
				$this->getImageFiles(XHS_IMAGE_PATH),
				null,
				'xhsImage');
            $params['name']           = 'N. N.';
            $params['teaser']         = '';
            $params['description']    = '';
            $params['variants']       = '';
            $params['price']          = 0.00;
            $params['weight']         = 1.00;
            $params['stockOnHand']    = 1;
            $params['preview']        = '';
            $params['image']          = '';
            $params['vat']            = $this->settings['vat_default'];
            $params['pages']          = array();
            $params['productCats']    = array();
        }
        $params['shipping_unit']    = $this->settings[XHS_LANGUAGE]['shipping_unit'];
        $params['shipping_by_unit'] = $this->settings['shipping_by_unit'];
        $params['categories']       = $this->catalog->getCategories();

        $level = 0;
        $params['pageLinks']     = $this->bridge->getUrls($level);
        $params['pageNames']     = $this->bridge->getHeadings($level);
        $params['pageLevels']    = $this->bridge->getLevels($level);

        return $this->render('productEdit', $params);
    }

    function deleteProduct(){
        if(!isset($_POST['xhsProductID'])){
            return false;
        }
        $this->catalog->deleteProduct($_POST['xhsProductID']);
        return $this->productList();
    }

    function productCategories(){
        $params['categories'] =  parent::categories();
        $params['leftOverCat'] = $this->catalog->category_for_the_left_overs[XHS_LANGUAGE];
        $params['xhsDefaultCat'] = $this->catalog->default_category[XHS_LANGUAGE];
        $params['xhsUseCats'] = $this->settings['use_categories'];
        $params['xhsAllowShowAll'] = $this->settings['allow_show_all'];

        return $this->render('categories', $params);
    }

    function tabs(){
        $params['SHOP_URL'] = $this->settings[XHS_LANGUAGE]['url'];
        $params['app_name'] = $this->appName;
        $params['version'] = $this->version;
        $params['setting_tasks'] = 'xhsTaskTab';
        $params['product_tasks'] = 'xhsTaskTab';
        $params['help_tasks'] = 'xhsTaskTab';
        if(isset($_POST['xhsTaskCat'])){
            $params[$_POST['xhsTaskCat']] = 'xhsTaskTabActive';
        }else {$params['product_tasks'] = 'xhsTaskTabActive';}

		$screen = isset($_POST['xhsTask']) ? $_POST['xhsTask'] : 'productList'; 

       switch ($screen) {
            case 'editProduct' : $params['editProduct'] = 'xhsActiveSubtab';
                $params['editProductLabel'] = isset($_POST['xhsProductID']) ? 'edit_product' : 'new_product';
                break;

            default: $params[$screen] = 'xhsActiveSubtab';
                break;
        }
        return $this->render('tabs', $params);
    }

    function taxSettings(){
        $params['vat_full']             = (float)str_replace(",", ".", $this->settings['vat_full']);
        $params['vat_reduced']          = (float)str_replace(",", ".", $this->settings['vat_reduced']);
        $params['vat_default']          = $this->settings['vat_default'];
        $params['dont_deal_with_taxes'] = $this->settings['dont_deal_with_taxes'];

        return $this->render('taxSettings', $params);
    }

    function paymentSettings(){
        $params = array();
        foreach($this->payments as $name){
            $this->loadPaymentModule($name);
        }
        if(isset($_POST['xhsPaymentTask']) && $_POST['xhsPaymentTask'] == 'updateSettings'){
            foreach($this->paymentModules as $name => $module){
                $module->saveSettings();
            }
        }
        $params['modules'] = $this->paymentModules;
        return $this->render('paymentSettings', $params);
    }

    function shippingSettings(){
        if(isset($_POST['newWeightRange'])){
            if(!isset($_POST['shipping_up_to'])){$this->settings['shipping_up_to'] = 'false';}
            unset($this->settings['weightRange']);
            if(isset($_POST['weightRange'])){
                foreach($_POST['weightRange'] as $key => $range){
                    $tempRange = (float)str_replace(",", ".", $range);
                    if($tempRange > 0){
                        $temp = (float)str_replace(",", ".", $_POST['weightFee'][$key]);
                        $this->settings['weightRange'][(string)$tempRange] = $temp;
                    }
                }
            }
            if((float)$_POST['newWeightRange'] > 0){
                $temp = (float)str_replace(",", ".", $_POST['newWeightFee']);
                $tempKey = (float)str_replace(",", ".", $_POST['newWeightRange']);
                $this->settings['weightRange'][(string)$tempKey] = $temp;
            }
            if(isset($this->settings['weightRange'])){
                ksort($this->settings['weightRange']);
            }
            $this->saveSettings();
        }

        $params['shipping_up_to']      = $this->settings['shipping_up_to'];
        $params['shipping_limit']      = (float)str_replace(",", ".", $this->settings['forwarding_expenses_up_to']);
        $params['charge_for_shipping'] = $this->settings['charge_for_shipping'];
        $params['shipping_max']        = (float)str_replace(",", ".", $this->settings['shipping_max']);
        $params['weightRanges']        = isset($this->settings['weightRange']) ? $this->settings['weightRange'] : array();
        $params['shipping_unit']       = $this->settings[XHS_LANGUAGE]['shipping_unit'];
        $params['shipping_by_unit']    = $this->settings['shipping_by_unit'];

        if(isset($this->settings['weightRange'])&& count($this->settings['weightRange']) > 0)
        {
            $params['shipping_mode'] = 'shipping_graded';
        }
        else{
            $params['shipping_mode'] = 'shipping_flat';
        }
        if($this->settings['charge_for_shipping'] == 'false'){
            $params['disabled'] = array('disabled' => 'disabled');
        }else{$params['disabled'] = '';}

        return $this->render('shippingSettings', $params);
    }

    function mainSettings(){
        $level     = 0;
        $headings  = $this->bridge->getHeadings($level);
        $urls      = $this->bridge->getUrls($level);
        $pages     = array();
        foreach($urls as $index => $url){
            $pages[$this->bridge->translateUrl($url)] = $headings[$index];
        }
        $params['pages']            = $pages;
        $params['published']        = $this->settings['published'];
        $params['email']            = $this->settings['order_email'];
        $params['cos_page']         = $this->settings[XHS_LANGUAGE]['cos_page'];
        $params['minimum_order']    = $this->settings['minimum_order'];

        if(!in_array($this->settings['default_currency'], array('€', '£', '¥', '$'))){
            $params['default_currency'] = 'other';
            $params['other_currency'] = $this->settings['default_currency'];
        }else{
            $params['default_currency'] = $this->settings['default_currency'];
            $params['other_currency'] = '';
        }
        if(isset($this->settings[XHS_LANGUAGE]['cos_page'])){
            $params['cos_label'] = 'cos_page';
        } else {
            $params['cos_label'] = 'warn_missing_cos';
        }
        $this->viewProvider->setCurrency(html_entity_decode($this->settings['default_currency']));
        return $this->render('mainSettings', $params);
    }

    function contactSettings(){
        $params['email']        = stripslashes($this->settings['order_email']);
        $params['company_name'] = stripslashes($this->settings['company_name']);
        $params['name']         = stripslashes($this->settings['name']);
        $params['street']       = stripslashes($this->settings['street']);
        $params['zip_code']     = stripslashes($this->settings['zip_code']);
        $params['city']         = stripslashes($this->settings['city']);

        return $this->render('contactSettings', $params);
    }

    function updateSettings($changes = null){
        $needSave = false;
        if(!$changes){
            $changes = $_POST;
        }
        if(isset($_POST['xhsPage'])){
            if($_POST['xhsPage'] == 'taxSettings'){
                if(!isset($_POST['dont_deal_with_taxes'])){
                    $changes['dont_deal_with_taxes'] = 'false';
                }
            }
            if($_POST['xhsPage'] == 'shippingSettings'){
                if(isset($_POST['forwarding_expenses_up_to'])){
                    $changes['forwarding_expenses_up_to'] = str_replace(',', '.', $_POST['forwarding_expenses_up_to']);
                }
                if(isset($_POST['shipping_max'])){
                    $changes['shipping_max'] = str_replace(',', '.', $_POST['shipping_max']);
                }
                if(isset($_POST['newWeightRange'])){
                    $changes['newWeightRange'] = str_replace(',', '.', $_POST['newWeightRange']);
                }
                if(isset($_POST['newWeightFee'])){
                    $changes['newWeightFee'] = str_replace(',', '.', $_POST['newWeightFee']);
                }
            }
        }
        
        foreach($changes as $key => $value){
            if(is_array($value) && count($value) > 0){
                foreach($value as $subKey => $subValue){
                    if(    isset($this->settings[$key]) &&
                        $this->settings[$key][$this->tidyPostString($subKey)] != $this->tidyPostString($subValue)
                    ){
                        $needSave = true;
                        $this->settings[$key][$subKey] = $this->tidyPostString($subValue);
                    }
                }
            }
            else{
                if(    key_exists($key, $this->settings)
                    && $this->settings[$key] != $this->tidyPostString($value)
                ){
                    $needSave = true;
                   
                   // $this->settings[$key] = $this->tidyPostString($value, $key == 'default_currency');
                    $this->settings[$key] = $this->tidyPostString($value, false);
                }
            }
        }
        if(isset($_POST['default_currency']) && $_POST['default_currency'] == 'other'){
            $this->settings['default_currency'] = $this->tidyPostString($_POST['other_currency'], true);
        }
        if($needSave){
            $this->saveSettings();
        }
        if(isset($_POST['xhsPage']) && method_exists($this, $_POST['xhsPage'])){
            return  $this->{$_POST['xhsPage']}();
        }
        return;
    }

    function saveSettings(){
        $save = "<?php\n";
        foreach($this->settings as $key => $value){
            $row = '$zShopSettings' . "['" . $key . "']";
            if(!is_array($value)){
                $value = $this->tidyPostString($value, false);
                $row .= " = '" . $value . "';\n";
            }
            else {
                $row = '';
                foreach($value as $k => $t){
                    $row .= '$zShopSettings' . "['" . $key. "']" . "['$k'] = '" . addslashes($t) . "';\n";
                }
            }
            $save .= $row;
        }
        $save .= '?>';
        $fh = fopen(XHS_CONFIG_FILE, 'w');
        fwrite($fh, $save);
        fclose($fh);
    }

    function saveProductCategories(){
        if(isset($_POST['xhsMoveCat'])){
            $this->catalog->moveCategory($_POST['xhsMoveDirection'], $_POST['xhsMoveCat']);
        }
        if(isset($_POST['xhsRenameCat'])){
            $newName = $this->tidyPostString($_POST['xhsCatName']);
            (strlen($newName) > 0) ?
            $this->catalog->renameCategory($newName, $_POST['xhsCatIndex']) : $this->catalog->deleteCategory($_POST['xhsCatIndex']) ;
        }
        if(isset($_POST['xhsAddCat'])){
            $newName = $this->tidyPostString($_POST['xhsAddCat']);
            if(strlen($newName) > 0) {
                $this->catalog->addCategory($newName);
            }
        }

        if(isset($_POST['xhsLeftOverCat'])){
            $leftOver = $this->tidyPostString($_POST['xhsLeftOverCat']);
            if(strlen($leftOver) > 0) {
                $this->catalog->setLeftOverCategory($leftOver);
            }
        }
        if(isset($_POST['xhsDefaultCat'])){
            $this->catalog->setDefaultCategory($_POST['xhsDefaultCat']);
        }
        if(isset($_POST['xhsUseCats'])){
            $this->settings['allow_show_all'] = $_POST['xhsAllowShowAll'];
            $this->settings['use_categories'] = $_POST['xhsUseCats'];
            $this->saveSettings();
        }

        return $this->productCategories();
    }

    function saveProduct(){
        $id = isset($_POST['xhsProductID']) ? $_POST['xhsProductID'] : 'new';
        if(key_exists($id, $this->catalog->products)){
            $product = $this->catalog->getProduct($id);
        }
        else{
            $product = new Product();
        }
        if(isset($_POST['xhsName'])){
            $product->setName($this->tidyPostString($_POST['xhsName']));
        }
        if(isset($_POST['xhsWeight'])){
            $product->setWeight($this->tidyPostString($_POST['xhsWeight']));
        }
        if(isset($_POST['xhsPrice'])){
            $product->setPrice($this->tidyPostString($_POST['xhsPrice']));
        }
      //  var_dump($_POST['xhsTeaser']);
        if(isset($_POST['xhsTeaser'])){
         //   $product->setTeaser($this->tidyPostString($_POST['xhsTeaser'], false));
         $product->setTeaser(addslashes(stripslashes($_POST['xhsTeaser'])));
        }
        if(isset($_POST['xhsDescription'])){
            $product->setDescription($this->tidyPostString($_POST['xhsDescription'], false));
        }
        if(isset($_POST['stockOnHand'])){
            $product->setStockOnHand($_POST['stockOnHand']);
        }
        if(isset($_POST['xhsCategories']) && is_array(($_POST['xhsCategories']))){
            $temp = array();
            foreach(($_POST['xhsCategories']) as $cat){
                $temp[] = $this->tidyPostString($cat);
            }
            $product->setCategories($temp);
        }else{
            $product->setCategories(array());
        }

        if(isset($_POST['xhsProductPages']) && is_array(($_POST['xhsProductPages']))){
            $temp = array();
            foreach(($_POST['xhsProductPages']) as $page){
                $temp[] = $page;
            }
            $product->setProductPages($temp);
        }else{
            $product->setProductPages(array());
        }

        if(isset($_POST['xhsPreviewPic'])){
            if($this->isAllowedImageFile($_POST['xhsPreviewPic'])){
                $product->setPreviewPic($_POST['xhsPreviewPic']);
            }else{
                $product->setPreviewPic();
            }
        }
        if(isset($_POST['xhsImage'])){

            if($this->isAllowedImageFile($_POST['xhsImage'])){
                $product->setImage($_POST['xhsImage']);
            }else{
                $product->setImage();
            }
        }

        if(isset($_POST['vat'])){
            $product->setVat($_POST['vat']);
        }
        if(isset($_POST['xhsVariants'])){
            $variants = array();
            $temp = explode(';', $_POST['xhsVariants']);
            foreach($temp as $variant){
                if(strlen($this->tidyPostString($variant)) > 0){
                    $variants[] = $this->tidyPostString($variant);
                }
            }
            $product->setVariants($variants);
        }

        if($id === 'new'){
            $this->catalog->addProduct($product);
            //$id = end($this->catalog->products)->uid;  // Parse error bei server4you ???
            $temp = end($this->catalog->products);
            $id = $temp->uid;
        }
        else {
            $this->catalog->updateProduct($id, $product);
        }

        return $this->editProduct($id);
    }

    function setShopUrl($url){
        $this->settings[XHS_LANGUAGE]['url'] = $url;
        $this->saveSettings();
    }

    function helpAbout(){
        $params['appName'] = $this->appName;
        $params['version'] = $this->version;
        return $this->render('help_about', $params);
    }

    function helpUsage(){
        return $this->render('help_usage');
    }

    function helpInstallation(){
        return $this->render('help_install');
    }
    function helpWhatsNew(){
        return $this->render('help_whatsNew');
    }

    function helpGettingStarted(){
        return $this->render('help_gettingStarted');
    }
    function helpGiveSupport(){
        return $this->render('help_giveSupport');
    }

    function checkFilePermissions(){
        $writeables = array(XHS_CONFIG_FILE, XHS_CATALOG);
        foreach($this->payments as $payment){
            $writeables[] = XHS_BASE_PATH . 'classes/paymentmodules/' . $payment . '/settings.php';
        }

        foreach($writeables as $file){
            if(!file_exists($file)){
                $this->errors['file_errors'][] = array($file, 'file_not_found');
                continue;
            }
            if(!is_writeable($file)){
                if(!chmod($file, 0666)){
                    $this->errors['file_errors'][] = array($file, 'needs_write_permission');
                }
            }
        }
    }

    function errorList(){
        $params['errors'] = $this->errors;
        return $this->render('errorList', $params );
    }
}
?>