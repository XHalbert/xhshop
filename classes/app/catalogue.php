<?php
class Catalogue {
    var $products, $dataFile, $cf, $separator, $categories, $category_for_the_left_overs, $default_category, $version, $cms;
    function __construct($separator) {
        $this->version = '1alpha-preview';
        $this->cms = 'CMSimple_XH';
        $this->dataFile = XHS_CATALOG;
        $this->separator = $separator;
        $this->products = array();
        $this->categories = array();

        $this -> load();
    }

    function loadArray(){

        include XHS_BASE_PATH . 'data/catalog.php';

        $this->categories = $categories;
        $this->category_for_the_left_overs = $category_for_the_left_overs;
        $this->default_category = $default_category;

        if(!isset($products) || !is_array($products)){
            $products = array();
        }
        $i = count($products);
        foreach($products as $temp) {

            $product = new Product();
            $product->names = $temp['names'];
            $product->price = $temp['price'];
            $product->vat = $temp['vat'];
            $product->variants = isset($temp['variants']) ? $temp['variants'] : array(XHS_LANGUAGE => '');
            $product->previewPicture = isset($temp['previewPicture']) ? $temp['previewPicture'] : '';
            $product->image = isset($temp['image']) ? $temp['image'] : '';;
            $product->weight = $temp['weight'];
            $product->setStockOnHand(isset($temp['stock_on_hand']) ? $temp['stock_on_hand'] : 1 );
            $product->teasers = isset($temp['teasers']) ? $temp['teasers'] : array(XHS_LANGUAGE => '');
            $product->descriptions = isset($temp['descriptions']) ? $temp['descriptions'] :array(XHS_LANGUAGE => '');
            $product->categories = isset($temp['categories']) ? $temp['categories'] : array(XHS_LANGUAGE => '');
            $product->productPages = isset($temp['productPages']) ? $temp['productPages'] : array(XHS_LANGUAGE => array());

            if($temp['separator'] <> $this->separator) {
                $new_links = array();
                foreach($temp['productPages'][XHS_LANGUAGE] as $page) {
                    $new_links[] = str_replace($temp['separator'], $this->separator, $page);
                }
                $product->productPages[XHS_LANGUAGE] = $new_links;
            }


            $product->sortIndex = isset($temp['sortIndex']) ? $temp['sortIndex'] : $i;
            $i--;
            $product->uid = isset($temp['uid']) ? $temp['uid'] :uniqid('p');
            $product->separator = $this->separator;
            $this->products[$product->uid] = $product;

        }

    }

    function load() {
        if(XHS_SAVE_FORMAT == 'array' && file_exists(XHS_BASE_PATH . 'data/catalog.php')){
            return $this->loadArray();
        }
        if(file_exists($this->dataFile)){
            $temp = implode("" , file($this->dataFile));
            $temp = unserialize($temp);
        }
        else{
            $this->save();
            return;
        }

        if(!isset($temp->products) || !is_array($temp->products)){
            $temp->products = array();
        }
        $i = count($temp->products);
        foreach($temp->products as $product) {
            if($product -> separator <> $this -> separator) {
                $new_links = array();
                foreach($product -> productPages[XHS_LANGUAGE] as $page) {
                    $new_links[] = str_replace($product->separator, $this->separator, $page);
                }
                $product -> productPages[XHS_LANGUAGE] = $new_links;
            }

            $product->counter = null;
            if(!isset($product->sortIndex)){$product->sortIndex = $i;}
            $i--;
            if(!isset($product->uid)){$product->uid = uniqid('p');}
            if(!isset($product->stock_on_hand)){$product->setStockOnHand(1);}
            $product->separator = $this->separator;
            $this->products[$product->uid] = $product;

        }

        $this->categories = $temp->categories;
        $this -> category_for_the_left_overs = $temp -> category_for_the_left_overs;
        $this -> default_category = $temp -> default_category;
        if(!isset($temp->version)){
            $this->save();
        }

    }

    function saveCatalogArray() {
        $string = "<?php \n";
        //      $string .= '$separator = \'' . $this->separator . "';\n\n";
        $string .= '################### Catalog ###############################' . ";\n";
        $string .= '$version = \'' . $this->version . "';\n\n";
        $string .= '################### Categories ###############################' . ";\n";
        foreach($this->categories as $lang => $categories){
            if(!is_array($categories)) {
                $string .= '$categories[\'' . $lang . '\'] = array();' . ";\n";
            }
            else{
                foreach($categories as $key => $category){

                    $string .= '$categories[\'' . $lang . '\'][' . $key . '] = \'' . $this->cleanString($category) . "';\n";
                }
            }
        }
        foreach($this->default_category as $lang => $cat){
            $string .= '$default_category[\'' . $lang . '\'] = \'' . $this->cleanString($cat) . "';\n";
        }
        foreach($this->category_for_the_left_overs as $lang => $cat){
            $string .= '$category_for_the_left_overs[\'' . $lang . '\'] = \'' . $this->cleanString($cat) . "';\n";
        }
        $string .= "\n\n" . '################### Products ######################' . ";\n";
        foreach($this->products as $uid => $product){
            foreach($product->names as $lang => $name){
                $string .= '$products[\'' . $uid . '\'][\'names\'][\'' . $lang . '\'] = \'' .  $this->cleanString($name) . "';\n";
            }
            $string .= '$products[\'' . $uid . '\'][\'price\'] = ' . (float)$product->price . ";\n";
            $string .= '$products[\'' . $uid . '\'][\'vat\'] = \'' . $this->cleanString($product->vat) . "';\n";
            $string .= '$products[\'' . $uid . '\'][\'sortIndex\'] = ' . (int)$product->sortIndex . ";\n";
            $string .= '$products[\'' . $uid . '\'][\'previewPicture\'] = \'' . $product->previewPicture . "';\n";
            $string .= '$products[\'' . $uid . '\'][\'image\'] = \'' . $product->image . "';\n";
            $string .= '$products[\'' . $uid . '\'][\'weight\'] = ' . (float)$product->weight . ";\n";
            $string .= '$products[\'' . $uid . '\'][\'stock_on_hand\'] = ' . (int)$product->stock_on_hand . ";\n";
            if(!isset($product->teasers)){$product->teasers = array(XHS_LANGUAGE => '');}
            foreach($product->teasers as $lang => $teaser){
                $string .= '$products[\'' . $uid . '\'][\'teasers\'][\'' . $lang . '\'] = \'' . $this->cleanString($teaser) . "';\n";
            }
            foreach($product->descriptions as $lang => $description){
                $string .= '$products[\'' . $uid . '\'][\'descriptions\'][\'' . $lang . '\'] = \'' . $this->cleanString($description) . "';\n";
            }
            
            if(!isset($product->variants) || !is_array($product->variants)){
                $string .= '$products[\'' . $uid . '\'][\'variants\'] = array('.XHS_LANGUAGE .' => \'\')' . ";\n";
            }
            else {
                foreach($product->variants as $lang => $variants){

                    $string .= '$products[\'' . $uid . '\'][\'variants\'][\'' . $lang . '\'] = array(';
                    if(is_array($variants)){
                        foreach($variants as $variant){
                            $string .= "'" . $this->cleanString($variant) . "', ";
                        }
                    }
                    $string .= ');' . "\n";
                }
            }

            if(!isset($product->categories) || !is_array($product->categories)){
                $string .= '$products[\'' . $uid . '\'][\'categories\'] = array()' . ";\n";
            }
            else {
                foreach($product->categories as $lang => $categories){

                    $string .= '$products[\'' . $uid . '\'][\'categories\'][\'' . $lang . '\'] = array(';
                    if(is_array($categories)){
                        foreach($categories as $cat){
                            $string .= "'" . $this->cleanString($cat) . "', ";
                        }
                    }
                    $string .= ');' . "\n";
                }
            }

            if(!isset($product->productPages)){
                $string .= '$products[\'' . $uid . '\'][\'productPages\'] = array()' . ";\n";
            }
            else {
                foreach($product->productPages as $lang => $pages){

                    $string .= '$products[\'' . $uid . '\'][\'productPages\'][\'' . $lang . '\'] = array(';
                    if(is_array($pages)){
                        foreach($pages as $page){
                            $string .= "'" . $this->cleanString($page) . "', ";
                        }
                    }
                    $string .= ');' . "\n";
                }
            }
            $string .= '$products[\'' . $uid . '\'][\'separator\'] = \'' . $this->cleanString($product->separator) . "';\n";
            $string .= '$products[\'' . $uid . '\'][\'uid\'] = \'' . $this->cleanString($uid) . "';\n";
            $string .= "\n#-----------------------------------------------------\n\n";
        }

        $string .= '?>';
        if(!file_exists(XHS_BASE_PATH . 'data/catalog.php')) {
            $handle = fopen(XHS_BASE_PATH . 'data/catalog.php', 'w');

            if ($handle) {
                fwrite($handle, $string);
                chmod(XHS_BASE_PATH . 'data/catalog.php', 0666);
                fclose($handle);
            } else {
                trigger_error('Catalogue::saveCatalogArray() - failed to create data/catalog.php');
            }
        }
        $handle = fopen(XHS_BASE_PATH . 'data/catalog.php', 'w');
        if(!is_writeable(XHS_BASE_PATH . 'data/catalog.php')){
            if(!chmod(XHS_BASE_PATH . 'data/catalog.php', 0666)){
                trigger_error('Catalogue::saveCatalogArray() - can\'t write to data/catalog.php');
                fclose($handle);
                return false;
            }
        }
        fwrite($handle, $string);
        fclose($handle);
        return true;
    }

    function save() {

        $sortOrder = array();
        $products = array();

        foreach($this->products as $product) {
            if(is_a($product, 'Product')){
                $product->separator = $this->separator;
                if(!isset($product->uid)){$product->uid = uniqid('p');}
                $products[$product->uid] = $product;
                $sortOrder[$product->uid] = $product->sortIndex;

            }
        }

        asort($sortOrder);


        $i = 1;
        foreach($sortOrder as $key => $sort){
            //     $products[$key]->sortIndex = $sort;
            $products[$key]->sortIndex = $i;
            $i++;

        }

        $this->products = isset($products) ? $products : array();

        if(XHS_SAVE_FORMAT === 'array'){
            $this->saveCatalogArray();
            $this->loadArray();
            return;

        }
        $fh = fopen($this -> dataFile, "w+");
        if(!$fh) {die("could not open " . $this->dataFile);}
        $temp = serialize($this);

        fwrite($fh, $temp) or die("could not write");
        fclose($fh);

        $this->load();
    }

    function renameCategory($name = null, $index = null) {
        if(!isset($index) || !isset($name)){return;}
        $products = $this->getProducts($this->categories[XHS_LANGUAGE][$index]);
        foreach($products as $product){
            foreach($product->categories[XHS_LANGUAGE] as $key => $value){
                if($value == $this->categories[XHS_LANGUAGE][$index]){
                    $product->categories[XHS_LANGUAGE][$key] = $name;
                }
            }
        }
        if($this->default_category[XHS_LANGUAGE] == $this->categories[XHS_LANGUAGE][$index]){
            $this->default_category[XHS_LANGUAGE] = $name;
        }
        $this->categories[XHS_LANGUAGE][$index] = $name;
        $this->save();
    }

    function deleteCategory($index = null) {
        if(!isset($index)){return;}
        if(key_exists($index, $this->categories[XHS_LANGUAGE])){
            unset($this->categories[XHS_LANGUAGE][$index]);
        }
        $this->categories[XHS_LANGUAGE] = array_values($this->categories[XHS_LANGUAGE]);
        $this->save();
    }

    function setLeftOverCategory($name){
        $this->category_for_the_left_overs[XHS_LANGUAGE] = $name;
        $this->save();
    }

    function setDefaultCategory($name){
        $this->default_category[XHS_LANGUAGE] = $name;
        $this->save();
    }

    function hasUncategorizedProducts(){
        foreach($this->products as $product){
            if(!isset($product->categories[XHS_LANGUAGE]) || count($product->categories[XHS_LANGUAGE]) == 0){
                return true;
            }
        }
        return false;
    }

    function getUncategorizedProducts(){
        $products = array();
        foreach($this->products as $index => $product){
            if(
                !isset($product->categories[XHS_LANGUAGE]) || !$product->categories[XHS_LANGUAGE])
            {
                $products[$index] = $product;
            }
        }
        return $products;
    }
    function getFallbackCategory(){
        return isset($this->category_for_the_left_overs[XHS_LANGUAGE]) ? $this->category_for_the_left_overs[XHS_LANGUAGE] : 'N.N.';
    }

    function getProducts($category = null){

        if(isset($category)){
            if(in_array($category, $this->categories[XHS_LANGUAGE])){
                $products = array();
                foreach($this->products as $index => $product){

                    if(   isset($product->categories[XHS_LANGUAGE])

                        && is_array($product->categories[XHS_LANGUAGE])
                        && in_array($category, $product->categories[XHS_LANGUAGE])){
                        $products[$index] = $product;
                    }
                }

                return $products;
            }
            if($category == 'left_overs'){
                return $this->getUncategorizedProducts();
            }
        }
        return $this->products;
    }
    function swapSortIndex($productA, $productB){
        if(!is_a($productA, 'Product') || !is_a($productB, 'Product')){
            trigger_error('Catalogue::swapSortIndex() expects two Products  as parameter');
            return;
        }
        //     echo $productA->getName() . ' (' . $productA->sortIndex . ') tauscht mit ' . $productB->getName() . ' (' . $productB->sortIndex . ')';
        $swap = $productA->sortIndex;

        $productA->sortIndex = $productB->sortIndex;
        $productB->sortIndex = $swap;
        $this->save();
    }

    function getProduct($id){
        if(!key_exists($id, $this->products)){
            trigger_error('Catalogue::getProduct($id): No product with this id.');
            return false;
        }
        else{
            return $this->products[$id];
        }
    }

    function getCategories($language = null){
        if($language === null){$language = XHS_LANGUAGE; }
        return $this->categories[$language];
    }

    function moveCategory($direction = null,  $index = null){
        if(!isset($index)){return; }
        $swap = null;
        if($direction == 'up'){
            $swap = $this->categories[XHS_LANGUAGE][$index - 1];
            $this->categories[XHS_LANGUAGE][$index - 1] = $this->categories[XHS_LANGUAGE][$index];
        }
        if($direction == 'down'){
            $swap = $this->categories[XHS_LANGUAGE][$index + 1 ];
            $this->categories[XHS_LANGUAGE][$index + 1] = $this->categories[XHS_LANGUAGE][$index];
        }
        if(!isset($swap)){return;}
        $this->categories[XHS_LANGUAGE][$index] = $swap;
        $this->save();
    }

    function addCategory($name = null){
        if(!isset($name)){return;}
        $this->categories[XHS_LANGUAGE][] = $name;
        $this->save();
    }

    function cleanString($string, $writeEntities = false){
        $string = str_replace(array('./', '<?php', '<?', '?>'), '', $string);
        if($writeEntities === true){
            $string = htmlspecialchars($string);
        }
        if(get_magic_quotes_gpc() === 1){
            $string  = rtrim(stripslashes($string));
        }

        return addslashes($string);
    }

    function addProduct($product){
        if(!is_a($product, 'Product')){
            trigger_error('Catalog::addProduct() - attempt to save some "No-Product" as Product.');
            return;
        }
        $product->sortIndex = 0; 
        $this->products[] = $product;
        $this->save();
    }
    /**
     *
     * @param <type> $uid
     * @param <type> $product
     * @return <type>
     *
     * @TODO: Why do we have to pass the changed product on live server? On local xampp it is not necessary
     */
    function updateProduct($uid = null, $product = null){
        if(!key_exists($uid, $this->products)){
            trigger_error('Catalogue::updateProduct($uid, $product) - no Product with this $uid');
            return;
        }
        if(is_a($product, 'Product')){

            $this->products[$uid] = $product;
        }
        else{
            trigger_error('Catalogue::updateProduct($uid, $product) - It\'s better to pass the changed "Product", otherwise the changes may get lost. (But why? - Something has to be changed');
        }
        $this->save();

    }

    function deleteProduct($id = null){
        if(!key_exists($id, $this->products)){
            trigger_error('Catalogue::deleteProduct($id): No product with this id.');
            return false;
        }
        unset($this->products[$id]);
        $this->save();
    }
}
?>