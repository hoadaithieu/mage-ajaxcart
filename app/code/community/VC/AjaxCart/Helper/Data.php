<?php
class VC_AjaxCart_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getAddCartUrl($args = array()) {
		if (($id = Mage::app()->getRequest()->getParam('id')) > 0) {
			//return $this->getUpdateItemOptionsUrl($id);
		}
	
		$params = array('_secure' => true);
		if (count($args) > 0) {
			$params += $args;
		}
		return $this->_getUrl('vc_ajaxcart/cart/add', $params);
	}
	
	
	public function getUpdateItemUrl($id) {
        return Mage::getUrl(
            'vc_ajaxcart/cart/updateItem',
            array(
                'id' => $id,
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl(),
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}
	
	public function getUpdateItemOptionsUrl($id) {
        return Mage::getUrl(
            'vc_ajaxcart/cart/updateItemOptions',
            array(
                'id' => $id,
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}
	
	
	public function getUpdateItemsUrl() {
        return Mage::getUrl(
            'vc_ajaxcart/cart/updateItems',
            array(
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl(),
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}

	public function getEmptyAllItemsUrl() {
        return Mage::getUrl(
            'vc_ajaxcart/cart/emptyAllItems',
            array(
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl(),
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}
	
	
	public function getDeleteItemUrl($id) {
        return Mage::getUrl(
            'vc_ajaxcart/cart/deleteItem',
            array(
                'id' => $id,
                Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl(),
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}
	
	public function getEditItemUrl($id) {
        return Mage::getUrl(
            'checkout/cart/configure',
            array(
                'id' => $id,
                '_secure' => Mage::app()->getStore()->isCurrentlySecure(),
            )
        );
	}
	
	public function getFormKey(){
		return Mage::getSingleton('core/session')->getFormKey();
	}
	
	
    public function isOnCheckoutPage()
    {
        $module = Mage::app()->getRequest()->getModuleName();
        $controller = Mage::app()->getRequest()->getControllerName();
        return $module == 'checkout' && ($controller == 'onepage' || $controller == 'multishipping');
    }
	
}