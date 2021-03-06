<?php ?>
<div class="appNmVrs">
<p><span class="fa fa-shopping-cart fa-2x"></span>&nbsp;&nbsp;%APP_NAME%, Version %VERSION%</p>
</div>
<?php $this->editProductLabel = isset($this->editProductLabel) ? $this->editProductLabel : 'new_product'; ?>
<ul id="xhsTaskTabs">
    <li class="%SETTING_TASKS%">
        <form action="" method="post" class="tab">
            <input name="xhsTask" value="mainSettings" type="hidden">
            <input name="xhsTaskCat" value="setting_tasks" type="hidden">
            <input value="<?php echo $this->labels['settings'] ?>" class="tab" type="submit">
        </form>
        <ul class="xhsSubTabs" id="xhsSettings">
            <li class="%MAINSETTINGS%">
                <form action="" method="post" class="tab" name="mainSettings">
                    <input name="xhsTask" value="mainSettings" type="hidden">
                    <input name="xhsTaskCat" value="setting_tasks" type="hidden">
                    <input value="<?php echo $this->labels['shop'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%CONTACTSETTINGS%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="contactSettings" type="hidden">
                    <input name="xhsTaskCat" value="setting_tasks" type="hidden">
                    <input value="<?php echo $this->labels['contact'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%TAXSETTINGS%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="taxSettings" type="hidden">
                    <input name="xhsTaskCat" value="setting_tasks" type="hidden">
                    <input value="<?php echo $this->labels['taxes'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%SHIPPINGSETTINGS%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="shippingSettings" type="hidden">
                    <input name="xhsTaskCat" value="setting_tasks" type="hidden">
                    <input value="<?php echo $this->labels['shipping']; ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%PAYMENTSETTINGS%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="paymentSettings" type="hidden">
                    <input name="xhsTaskCat" value="setting_tasks" type="hidden">
                    <input value="<?php echo $this->labels['payments'] ?>" class="subTab" type="submit">
                </form>
            </li>
        </ul>
    </li>
    <li class="%PRODUCT_TASKS%">
        <form action="" method="post" class="tab">
            <input name="xhsTask" value="productList" type="hidden">
            <input name="xhsTaskCat" value="product_tasks" type="hidden">
            <input value="<?php echo $this->labels['products'] ?>" class="tab" type="submit">
        </form>
        <ul class="xhsSubTabs" id="xhsProducts">
           <li class="%PRODUCTLIST%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="productList" type="hidden">
                    <input name="xhsTaskCat" value="product_tasks" type="hidden">
                    <input value="<?php echo $this->labels['products_list'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%EDITPRODUCT%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="editProduct" type="hidden">
                    <input name="xhsTaskCat" value="product_tasks" type="hidden">
                    <input value="<?php echo $this->label($this->editProductLabel) ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%PRODUCTCATEGORIES%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="productCategories" type="hidden">
                    <input name="xhsTaskCat" value="product_tasks" type="hidden">
                    <input value="<?php echo $this->label('product_categories') ?>" class="subTab" type="submit">
                </form>
            </li>
        </ul>
    </li>
    <li class="%HELP_TASKS%">
        <form action="" method="post" class="tab">
            <input name="xhsTask" value="helpGettingStarted" type="hidden">
            <input name="xhsTaskCat" value="help_tasks" type="hidden">
            <input value="<?php echo $this->labels['help'] ?>" class="tab" type="submit">
        </form>
        <ul class="xhsSubTabs" id="xhsAbout">

            <li class="%HELPWHATSNEW%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="helpWhatsNew" type="hidden">
                    <input name="xhsTaskCat" value="help_tasks" type="hidden">
                    <input value="<?php echo $this->labels['whats_new'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%HELPGETTINGSTARTED%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="helpGettingStarted" type="hidden">
                    <input name="xhsTaskCat" value="help_tasks" type="hidden">
                    <input value="<?php echo $this->labels['getting_started'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%HELPUSAGE%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="helpUsage" type="hidden">
                    <input name="xhsTaskCat" value="help_tasks" type="hidden">
                    <input value="<?php echo $this->labels['usage'] ?>" class="subTab" type="submit">
                </form>
            </li>
            <li class="%HELPGIVESUPPORT%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="helpGiveSupport" type="hidden">
                    <input name="xhsTaskCat" value="help_tasks" type="hidden">
                    <input value="<?php echo $this->labels['give_support'] ?>" class="subTab" type="submit">
                </form>
            </li>
           <li class="%HELPABOUT%">
                <form action="" method="post" class="tab">
                    <input name="xhsTask" value="helpAbout" type="hidden">
                    <input name="xhsTaskCat" value="help_tasks" type="hidden">
                    <input value="<?php echo $this->labels['about'] ?>" class="subTab" type="submit">
                </form>
            </li>

        </ul>
    </li>
</ul>
