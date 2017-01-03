<?php
class Learn_RatingReview_Model_Layer_Filter_Decimal extends Mage_Catalog_Model_Layer_Filter_Decimal
{
    protected $_reviewLabel = 'Rating Filter';

    protected function _renderItemLabel($range, $value)
    {
        $from   = ($value - 1) * $range ;
        $to     = $value * $range;
		if($this->getName() == $this->_reviewLabel)
            return Mage::helper('catalog')->__('%s Star & Up', $from);
        else
            return Mage::helper('catalog')->__('%s - %s', $from, $to);
    }

    protected function _getItemsData()
    {
        $key = $this->_getCacheKey();

        $data = $this->getLayer()->getAggregator()->getCacheData($key);
        if ($data === null) {
            $data       = array();
            $range      = $this->getRange();
            $dbRanges   = $this->getRangeItemCounts($range);

			/*-  Reversed the Review order   -*/
			if($this->getName() == $this->_reviewLabel)
				$dbRanges = array_reverse($dbRanges, true);
			
			$array_count_sum =  array_sum($dbRanges);
            $array_new_sum = 0;
			/*-- Rating star & Up --*/
			if(trim($this->getName()) == trim($this->_reviewLabel)) {
				foreach ($dbRanges as $index => $count) {
					$array_new_sum += $count;
					$data[] = array(
						'label' => $this->_renderItemLabel($range, $index),
						'value' => $index . ',' . $range,
						/* 'count' => $array_count_sum - $array_new_sum, */
						'count' => $array_new_sum,
					);
				}
            } else {
				foreach ($dbRanges as $index => $count) {
					$data[] = array(
						'label' => $this->_renderItemLabel($range, $index),
						'value' => $index . ',' . $range,
						'count' => $count,
					);
				}
			}
			/*-- Rating star & Up --*/ 
        }
        return $data;
    }
}
