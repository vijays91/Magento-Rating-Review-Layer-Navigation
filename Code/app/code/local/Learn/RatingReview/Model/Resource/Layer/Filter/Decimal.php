<?php
 
class Learn_RatingReview_Model_Resource_Layer_Filter_Decimal extends Mage_Catalog_Model_Resource_Layer_Filter_Decimal
{
    public function applyFilterToCollection($filter, $range, $index)
    {
        $collection = $filter->getLayer()->getProductCollection();
        $attribute  = $filter->getAttributeModel();
        $connection = $this->_getReadAdapter();
        $tableAlias = sprintf('%s_idx', $attribute->getAttributeCode());
        $conditions = array(
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $collection->getStoreId())
        );

        $collection->getSelect()->join(
            array($tableAlias => $this->getMainTable()),
            implode(' AND ', $conditions),
            array()
        );

        $collection->getSelect()->where("{$tableAlias}.value >= ?", ($range * ($index - 1)));
        /*-- Rating star & Up --*/
        if(trim($attribute->getAttributeCode()) == trim("rating_filter"))
            $collection->getSelect()->where("{$tableAlias}.value < ?", 6);
        else             
            $collection->getSelect()->where("{$tableAlias}.value < ?", ($range * $index));
        /*-- Rating star & Up --*/
        return $this;
    }
}
