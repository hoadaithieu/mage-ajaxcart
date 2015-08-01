<?php
include "app/code/core/Mage/Checkout/controllers/CartController.php";
class VC_AjaxCart_CartController extends Mage_Checkout_CartController{
	
    public function indexAction() {
		$this->loadLayout();
		$this->renderLayout(); 
    }
	
	public function addAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '',
			'redirect' => '',
			'product' => 0);
		
        $cart   = $this->_getCart();
        $params = $this->getRequest()->getParams();
        try {
			$result['product'] = $params['product'];
			if (!$this->_validateFormKey()) {
				Mage::throwException($this->__('Invalid form key.'));
			}
		
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
			
			if (isset($params['list']) && in_array($product->getTypeId(), array(Mage_Catalog_Model_Product_Type::TYPE_BUNDLE,
			Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE,
			Mage_Catalog_Model_Product_Type::TYPE_GROUPED,
			Mage_Catalog_Model_Product_Type::TYPE_VIRTUAL,
			'downloadable'))) {
				$result['redirect'] = $product->getProductUrl();
				//$this->_getSession()->addNotive($this->__(ucfirst($product->getTypeId()).' product must be choiced at detail page.'));
				Mage::throwException($this->__(ucfirst($product->getTypeId()).' product must be chosen at detail page.'));
			}
            $related = $this->getRequest()->getParam('related_product');

            /**
             * Check product availability
             */
            if (!$product) {
                Mage::throwException($this->__('Product is not exist.'));
            }
			
            $cart->addProduct($product, $params);
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            /**
             * @todo remove wishlist observer processAddToCart
             */
            Mage::dispatchEvent('checkout_cart_add_product_complete',
                array('product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );

            if (!$this->_getSession()->getNoCartRedirect(true)) {
                if (!$cart->getQuote()->getHasError()) {
                    $message = $this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName()));
                    //$this->_getSession()->addSuccess($message);
					
					$result['code'] = 'success';
					$result['msg'] = $message;
                }
            }
			
			Mage::register('remove_container', true);
			
			$update = $this->getLayout()->getUpdate();
			$update->addHandle('vc_ajaxcart_minicart');
			$this->loadLayoutUpdates();
			$this->generateLayoutXml()->generateLayoutBlocks();			
			
			//$result['cart_label'] = $this->__('Cart');
			$result['cart_qty'] =  $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());
			$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
			
        } catch (Mage_Core_Exception $e) {
			$result['code'] = 'error';
			$result['msg'] = str_replace("\n", "<br/>", $e->getMessage());
			 
        } catch (Exception $e) {
            Mage::logException($e);
            $result['code'] = 'error';
			$result['msg'] = $this->__('Cannot add the item to shopping cart.');
        }	
        
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		
	}
	
	public function updateItemOptionsAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '');
	
		$cart   = $this->_getCart();
        $id = (int) $this->getRequest()->getParam('id');
        $params = $this->getRequest()->getParams();

        if (!isset($params['options'])) {
            $params['options'] = array();
        }
        try {
            if (isset($params['qty'])) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $quoteItem = $cart->getQuote()->getItemById($id);
            if (!$quoteItem) {
                Mage::throwException($this->__('Quote item is not found.'));
            }

            $item = $cart->updateItem($id, new Varien_Object($params));
            if (is_string($item)) {
                Mage::throwException($item);
            }
            if ($item->getHasError()) {
                Mage::throwException($item->getMessage());
            }

            $related = $this->getRequest()->getParam('related_product');
            if (!empty($related)) {
                $cart->addProductsByIds(explode(',', $related));
            }

            $cart->save();

            $this->_getSession()->setCartWasUpdated(true);

            Mage::dispatchEvent('checkout_cart_update_item_complete',
                array('item' => $item, 'request' => $this->getRequest(), 'response' => $this->getResponse())
            );
			
			if (!$cart->getQuote()->getHasError()) {
				$this->_getSession()->addSuccess($message);
				$result['msg'] =  $this->__('%s was updated in your shopping cart.', Mage::helper('core')->escapeHtml($item->getProduct()->getName()));
			}
			
			$update = $this->getLayout()->getUpdate();
			$update->addHandle('vc_ajaxcart_minicart');
			$this->loadLayoutUpdates();
			$this->generateLayoutXml()->generateLayoutBlocks();			
			
			//$result['cart_label'] = $this->__('Cart');
			$result['cart_qty'] =  $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());
			$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
			
			
            $result['code'] = 'success';
			
			
        } catch (Mage_Core_Exception $e) {
  
            $result['code'] = 'error';
			$result['msg'] =  $e->getMessage();

            
        } catch (Exception $e) {
            Mage::logException($e);
            $result['code'] = 'error';
			$result['msg'] =  $this->__('Cannot update the item.');
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));	
        	
	}
	
	public function updateItemAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '',
			'checkout_content' => '');
	
        $id = (int)$this->getRequest()->getParam('id');
        $qty = $this->getRequest()->getParam('qty');
		$blockType = $this->getRequest()->getParam('block_type');
		try {
			
			if (!$this->_validateFormKey()) {
				Mage::throwException($this->__('Invalid form key.'));
			}
			if ($id) {
				//$this->_getSession()->setCartWasUpdated(true);
				$cart = $this->_getCart();
				
				if (isset($qty)) {
					$filter = new Zend_Filter_LocalizedToNormalized(
						array('locale' => Mage::app()->getLocale()->getLocaleCode())
					);
					$qty = $filter->filter($qty);
				}

				$quoteItem = $cart->getQuote()->getItemById($id);
				if (!$quoteItem) {
					Mage::throwException($this->__('Quote item is not found.'));
				}
				if ($qty == 0) {
					$cart->removeItem($id);
				} else {
					$quoteItem->setQty($qty)->save();
				}
				//
				$cart->save();
				//$cart->init();
				$this->_getSession()->setCartWasUpdated(true);
				Mage::register('remove_container', true);
				//if ($blockType == 'checkout') {
					$update = $this->getLayout()->getUpdate();
					$update->addHandle('vc_ajaxcart_checkoutcart');
					$this->loadLayoutUpdates();
					$this->generateLayoutXml()->generateLayoutBlocks();			
					$result['checkout_content'] = $this->getLayout()->getBlock('vc_ajaxcart.checkout.cart')->toHtml();				
				
				//} else {
					$update = $this->getLayout()->getUpdate();
					$update->addHandle('vc_ajaxcart_minicart');
					$this->loadLayoutUpdates();
					$this->generateLayoutXml()->generateLayoutBlocks();			
					$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
				//}

				

				$result['cart_qty'] =  $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());

				$result['msg'] = $this->__('Item was updated successfully.');
				$result['code'] = 'success';
			} else {
				Mage::throwException($this->__('Invalid product.'));
				
			}
		} catch (Exception $e) {
			$result['code'] = 'error';
			//$result['msg'] = $this->__('Can not save item.');
			$result['msg'] = $this->__($e->getMessage());
		}
      

        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));	
	}
	
	public function deleteItemAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '',
			'checkout_content' => '');
		try {
			if (!$this->_validateFormKey()) {
				Mage::throwException($this->__('Invalid form key'));
			}
			$id = (int) $this->getRequest()->getParam('id');
			$blockType = $this->getRequest()->getParam('block_type');
			if ($id) {
				$this->_getCart()->removeItem($id)->save();
				Mage::register('remove_container', true);
				//if ($blockType == 'checkout') {
					$update = $this->getLayout()->getUpdate();
					$update->addHandle('vc_ajaxcart_checkoutcart');
					$this->loadLayoutUpdates();
					$this->generateLayoutXml()->generateLayoutBlocks();			
					$result['checkout_content'] = $this->getLayout()->getBlock('vc_ajaxcart.checkout.cart')->toHtml();				
				//} else {
					
					$update = $this->getLayout()->getUpdate();
					$update->addHandle('vc_ajaxcart_minicart');
					$this->loadLayoutUpdates();
					$this->generateLayoutXml()->generateLayoutBlocks();			
					$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
					
				//}
				$result['cart_qty'] =  $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());
				$result['code'] = 'success';
				$result['msg'] = $this->__('Item was removed successfully.');
				
			} else {
				Mage::throwException('Invalid product');
			}

		} catch (Exception $e) {
			$result['code'] = 'error';
			//$result['msg'] = $this->__('Can not remove the item.');	
			$result['msg'] = $e->getMessage();	
		}
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		
	}
	
	public function updateItemsAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '',
			'checkout_content' => '');
	
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new Zend_Filter_LocalizedToNormalized(
                    array('locale' => Mage::app()->getLocale()->getLocaleCode())
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                $cart = $this->_getCart();
                if (! $cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }

                $cartData = $cart->suggestItemsQty($cartData);
                $cart->updateItems($cartData)
                    ->save();
					
				Mage::register('remove_container', true);
					
				$update = $this->getLayout()->getUpdate();
				$update->addHandle('vc_ajaxcart_checkoutcart');
				$this->loadLayoutUpdates();
				$this->generateLayoutXml()->generateLayoutBlocks();			
				$result['checkout_content'] = $this->getLayout()->getBlock('vc_ajaxcart.checkout.cart')->toHtml();	
				
				$update = $this->getLayout()->getUpdate();
				$update->addHandle('vc_ajaxcart_minicart');
				$this->loadLayoutUpdates();
				$this->generateLayoutXml()->generateLayoutBlocks();			
				$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
				
				$result['cart_qty'] = $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());			
				$result['code'] = 'success';
				$result['msg'] = $this->__('Items was updated successfully');	
            }
            $this->_getSession()->setCartWasUpdated(true);
        } catch (Mage_Core_Exception $e) {
			$result['code'] = 'error';
			$result['msg'] = $this->__(Mage::helper('core')->escapeHtml($e->getMessage()));	
			
        } catch (Exception $e) {
			$result['code'] = 'error';
			$result['msg'] = $this->__('Cannot update shopping cart.');	

            Mage::logException($e);
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
		
	
	}
	
	public function emptyAllItemsAction() {
		$result = array('code' => '', 
			'msg' => '', 
			//'cart_label' => '', 
			'cart_qty' => 0, 
			'mini_content' => '',
			'checkout_content' => '');
	
        try {
            $this->_getCart()->truncate()->save();
            $this->_getSession()->setCartWasUpdated(true);
			
			Mage::register('remove_container', true);
			
			$update = $this->getLayout()->getUpdate();
			$update->addHandle('vc_ajaxcart_checkoutcart');
			$this->loadLayoutUpdates();
			$this->generateLayoutXml()->generateLayoutBlocks();			
			$result['checkout_content'] = $this->getLayout()->getBlock('vc_ajaxcart.checkout.cart')->toHtml();
			
			$update = $this->getLayout()->getUpdate();
			$update->addHandle('vc_ajaxcart_minicart');
			$this->loadLayoutUpdates();
			$this->generateLayoutXml()->generateLayoutBlocks();			
			$result['mini_content'] = $this->getLayout()->getBlock('vc_ajaxcart_minicart_head')->toHtml();
			$result['cart_qty'] = $this->qtyLabelMask(Mage::getSingleton('checkout/cart')->getSummaryQty());
			
			$result['code'] = 'success';				
			
        } catch (Mage_Core_Exception $exception) {
			$result['code'] = 'error';
			$result['msg'] = $exception->getMessage();
        } catch (Exception $exception) {
			$result['code'] = 'error';
			$result['msg'] = $this->__('Cannot update shopping cart.');
			
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
	
	}
	
	protected function qtyLabelMask($qty = 0) {
		$labelAr = explode(',', Mage::getStoreConfig('vc_ajaxcart/general/tinycart_qty_label'));
		if ($qty == 1) {
			$text = $this->__(isset($labelAr[1]) ? $labelAr[1]: 'My Cart (%s item)', $qty);
		} elseif ($qty > 0) {
			$text = $this->__(isset($labelAr[2]) ? $labelAr[2]: 'My Cart (%s items)', $qty);
		} else {
			$text = $this->__(isset($labelAr[0]) ? $labelAr[0]: 'My Cart');
		}
		return $text;
	}
	
}