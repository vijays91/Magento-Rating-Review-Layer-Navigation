<?php
include_once("Mage/Adminhtml/controllers/Catalog/Product/ReviewController.php");
class Learn_RatingReview_Catalog_Product_ReviewController extends Mage_Adminhtml_Catalog_Product_ReviewController
{
    public function saveAction()
    {
        if (($data = $this->getRequest()->getPost()) && ($reviewId = $this->getRequest()->getParam('id'))) {
            $review = Mage::getModel('review/review')->load($reviewId);
            $session = Mage::getSingleton('adminhtml/session');
            if (! $review->getId()) {
                $session->addError(Mage::helper('catalog')->__('The review was removed by another user or does not exist.'));
            } else {
                try {
                    $review->addData($data)->save();

                    $arrRatingId = $this->getRequest()->getParam('ratings', array());
                    $votes = Mage::getModel('rating/rating_option_vote')
                        ->getResourceCollection()
                        ->setReviewFilter($reviewId)
                        ->addOptionInfo()
                        ->load()
                        ->addRatingOptions();
                    foreach ($arrRatingId as $ratingId=>$optionId) {
                        if($vote = $votes->getItemByColumnValue('rating_id', $ratingId)) {
                            Mage::getModel('rating/rating')
                                ->setVoteId($vote->getId())
                                ->setReviewId($review->getId())
                                ->updateOptionVote($optionId);
                        } else {
                            Mage::getModel('rating/rating')
                                ->setRatingId($ratingId)
                                ->setReviewId($review->getId())
                                ->addOptionVote($optionId, $review->getEntityPkValue());
                        }
                    }

                    $review->aggregate();

                    $session->addSuccess(Mage::helper('catalog')->__('The review has been saved.'));
                } catch (Mage_Core_Exception $e) {
                    $session->addError($e->getMessage());
                } catch (Exception $e){
                    $session->addException($e, Mage::helper('catalog')->__('An error occurred while saving this review.'));
                }
            }
			
			/*- Update review values -*/
			if($reviewId) {
				$update_rating = Mage::getModel('ratingreview/updatereview');
				$getReviewdProductId  = $update_rating->getReviwedProductId($reviewId);
				$review_rating = $update_rating->getProductReviewSummary($getReviewdProductId);
				$set_review_rating = $update_rating->updateReviewData($getReviewdProductId, $review_rating);
			}
			/*- Update review values -*/

            return $this->getResponse()->setRedirect($this->getUrl($this->getRequest()->getParam('ret') == 'pending' ? '*/*/pending' : '*/*/'));
        }
        $this->_redirect('*/*/');
    }

    public function deleteAction()
    {
        $reviewId   = $this->getRequest()->getParam('id', false);
        $session    = Mage::getSingleton('adminhtml/session');

        try {
			/*- Update review values -*/
			if($reviewId) {
				$update_rating = Mage::getModel('ratingreview/updatereview');
				$getReviewdProductId  = $update_rating->getReviwedProductId($reviewId);
			}
			/*- Update review values -*/

			
            Mage::getModel('review/review')->setId($reviewId)
                ->aggregate()
                ->delete();
			
			/*- Update review values -*/
			if($getReviewdProductId) {
				$review_rating = $update_rating->getProductReviewSummary($getReviewdProductId);
				$set_review_rating = $update_rating->updateReviewData($getReviewdProductId, $review_rating);
			}
			/*- Update review values -*/
			
            $session->addSuccess(Mage::helper('catalog')->__('The review has been deleted'));
            if( $this->getRequest()->getParam('ret') == 'pending' ) {
                $this->getResponse()->setRedirect($this->getUrl('*/*/pending'));
            } else {
                $this->getResponse()->setRedirect($this->getUrl('*/*/'));
            }
            return;
        } catch (Mage_Core_Exception $e) {
            $session->addError($e->getMessage());
        } catch (Exception $e){
            $session->addException($e, Mage::helper('catalog')->__('An error occurred while deleting this review.'));
        }
        $this->_redirect('*/*/edit/',array('id'=>$reviewId));
    }

    public function massDeleteAction()
    {
        $reviewsIds = $this->getRequest()->getParam('reviews');
        $session    = Mage::getSingleton('adminhtml/session');

        if(!is_array($reviewsIds)) {
             $session->addError(Mage::helper('adminhtml')->__('Please select review(s).'));
        } else {
            try {
                foreach ($reviewsIds as $reviewId) {
                    $model = Mage::getModel('review/review')->load($reviewId);
					$getReviewdProductId = $model->getData('entity_pk_value');	
                    $model->delete();
		
					/*- Update review values -*/
					if($getReviewdProductId) {
						$update_rating = Mage::getModel('ratingreview/updatereview');
						$review_rating = $update_rating->getProductReviewSummary($getReviewdProductId);
						$set_review_rating = $update_rating->updateReviewData($getReviewdProductId, $review_rating);
					}
					/*- Update review values -*/
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) have been deleted.', count($reviewsIds))
                );
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
            } catch (Exception $e){
                $session->addException($e, Mage::helper('adminhtml')->__('An error occurred while deleting record(s).'));
            }
        }

        $this->_redirect('*/*/' . $this->getRequest()->getParam('ret', 'index'));
    }

    public function massUpdateStatusAction()
    {
        $reviewsIds = $this->getRequest()->getParam('reviews');
        $session    = Mage::getSingleton('adminhtml/session');

        if(!is_array($reviewsIds)) {
             $session->addError(Mage::helper('adminhtml')->__('Please select review(s).'));
        } else {
            /* @var $session Mage_Adminhtml_Model_Session */
            try {
                $status = $this->getRequest()->getParam('status');
                foreach ($reviewsIds as $reviewId) {
                    $model = Mage::getModel('review/review')->load($reviewId);
                    $model->setStatusId($status)
                        ->save()
                        ->aggregate();
		
					/*- Update review values -*/
					if($reviewId) {
						$update_rating = Mage::getModel('ratingreview/updatereview');
						$getReviewdProductId  = $update_rating->getReviwedProductId($reviewId);
						$review_rating = $update_rating->getProductReviewSummary($getReviewdProductId);
						$set_review_rating = $update_rating->updateReviewData($getReviewdProductId, $review_rating);
					}
					/*- Update review values -*/
                }
                $session->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) have been updated.', count($reviewsIds))
                );
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
            } catch (Exception $e) {
                $session->addException($e, Mage::helper('adminhtml')->__('An error occurred while updating the selected review(s).'));
            }
        }

        $this->_redirect('*/*/' . $this->getRequest()->getParam('ret', 'index'));
    }

    public function massVisibleInAction()
    {
        $reviewsIds = $this->getRequest()->getParam('reviews');
        $session    = Mage::getSingleton('adminhtml/session');

        if(!is_array($reviewsIds)) {
             $session->addError(Mage::helper('adminhtml')->__('Please select review(s).'));
        } else {
            $session = Mage::getSingleton('adminhtml/session');
            /* @var $session Mage_Adminhtml_Model_Session */
            try {
                $stores = $this->getRequest()->getParam('stores');
                foreach ($reviewsIds as $reviewId) {
                    $model = Mage::getModel('review/review')->load($reviewId);
                    $model->setSelectStores($stores);
                    $model->save();
					/*- Update review values -*/
					if($reviewId) {
						$update_rating = Mage::getModel('ratingreview/updatereview');
						$getReviewdProductId  = $update_rating->getReviwedProductId($reviewId);
						$review_rating = $update_rating->getProductReviewSummary($getReviewdProductId);
						$set_review_rating = $update_rating->updateReviewData($getReviewdProductId, $review_rating);
					}
					/*- Update review values -*/
                }
                $session->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) have been updated.', count($reviewsIds))
                );
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
            } catch (Exception $e) {
                $session->addException($e, Mage::helper('adminhtml')->__('An error occurred while updating the selected review(s).'));
            }
        }

        $this->_redirect('*/*/pending');
    }


    public function postAction()
    {
        $productId  = $this->getRequest()->getParam('product_id', false);
        $session    = Mage::getSingleton('adminhtml/session');

        if ($data = $this->getRequest()->getPost()) {
            if (Mage::app()->isSingleStoreMode()) {
                $data['stores'] = array(Mage::app()->getStore(true)->getId());
            } else  if (isset($data['select_stores'])) {
                $data['stores'] = $data['select_stores'];
            }

            $review = Mage::getModel('review/review')->setData($data);

            $product = Mage::getModel('catalog/product')
                ->load($productId);

            try {
                $review->setEntityId(1) // product
                    ->setEntityPkValue($productId)
                    ->setStoreId($product->getStoreId())
                    ->setStatusId($data['status_id'])
                    ->setCustomerId(null)//null is for administrator only
                    ->save();

                $arrRatingId = $this->getRequest()->getParam('ratings', array());
                foreach ($arrRatingId as $ratingId=>$optionId) {
                    Mage::getModel('rating/rating')
                       ->setRatingId($ratingId)
                       ->setReviewId($review->getId())
                       ->addOptionVote($optionId, $productId);
                }

                $review->aggregate();
		
				/*- Update review values -*/
				if($productId) {
					$update_rating = Mage::getModel('ratingreview/updatereview');
					$review_rating = $update_rating->getProductReviewSummary($productId);
					$set_review_rating = $update_rating->updateReviewData($productId, $review_rating);
				}
				/*- Update review values -*/
				
                $session->addSuccess(Mage::helper('catalog')->__('The review has been saved.'));
                if( $this->getRequest()->getParam('ret') == 'pending' ) {
                    $this->getResponse()->setRedirect($this->getUrl('*/*/pending'));
                } else {
                    $this->getResponse()->setRedirect($this->getUrl('*/*/'));
                }

                return;
            } catch (Mage_Core_Exception $e) {
                $session->addError($e->getMessage());
            } catch (Exception $e) {
                $session->addException($e, Mage::helper('adminhtml')->__('An error occurred while saving review.'));
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/*/'));
        return;
    }

}
