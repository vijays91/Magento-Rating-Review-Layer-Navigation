<?php
class Learn_RatingReview_Model_UpdateReview extends Mage_Core_Model_Abstract
{
	
	/*
	 * Geting reviewed product id
	 */
	public function getReviwedProductId($reviewId) {
		$review = Mage::getModel('review/review')->load($reviewId);
		return $review->getData('entity_pk_value');		
	}	
	
	/*
	 * Geting product review summary
	 */
	public function getProductReviewSummary($product_id, $storeId = null) {
		$summaryData = Mage::getModel('review/review_summary')->setStoreId(Mage::app()->getStore()->getId())->load($product_id);
		$rating = (float)($summaryData->getRatingSummary()/20);
		$rating = ($rating >=5 ) ? 4.99 : $rating;
		return $rating;
		/* return (float)($summaryData->getRatingSummary()/20); */
		/* return $summaryData->getRatingSummary(); */
	}
	
	/*
	 * Update review status in product attribute
	 */
	public function updateReviewData($productid, $rating_summary) {
		$product = Mage::getModel('catalog/product')->load($productid);
		/* $product->setRatingFilter($rating_summary); */
		$product->setData('rating_filter', $rating_summary);
		/* $product->setData('review_rating', $rating_summary); */
		try {
			$product->save();
			return true;
		} catch (Exception $ex) {
			echo "<pre>".$ex."</pre>";
			return false;
		}
	}
		
}