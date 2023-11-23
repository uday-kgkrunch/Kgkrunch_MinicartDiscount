<?php
namespace Kgkrunch\MinicartDiscount\Plugin\Checkout\CustomerData;

class Cart
{
    protected $checkoutSession;
    protected $checkoutHelper;
    protected $quote;

    protected $_ruleFactory;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->_ruleFactory = $ruleFactory;
    }
    
    /**
     * Get active quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    protected function getQuote()
    {
        if (null === $this->quote) {
            $this->quote = $this->checkoutSession->getQuote();
        }
        return $this->quote;
    }

    protected function getDiscountAmount()
    {
        $discountAmount = 0;
		$discountRuleNames = array();
		$itemAppliedRuleIds=$this->getQuote()->getAppliedRuleIds();
		if (!empty($itemAppliedRuleIds)) {
                $appliedRuleIds = explode(',', $itemAppliedRuleIds);
                foreach ($appliedRuleIds as $ruleId) {
                    $rule = $this->_ruleFactory->create()->load($ruleId);
                    $discountRuleNames[]=$rule->getName();
                }
         }
			
        foreach($this->getQuote()->getAllVisibleItems() as $item){
            $discountAmount += ($item->getDiscountAmount() ? $item->getDiscountAmount() : 0);
        }
		
		if($discountRuleNames){
			$array_unique_discountRuleNames=array_unique($discountRuleNames);
			$array_unique_discountRuleNames=array_filter($array_unique_discountRuleNames);
			return array('discount_amount'=>$discountAmount,'discount_description'=>implode(',',$array_unique_discountRuleNames) );
		}else{
			return array('discount_amount'=>$discountAmount,'discount_description'=>'');
		}
    }
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, $result)
    {
		$getDiscountAmount=$this->getDiscountAmount();
        $result['discount_amount_no_html'] = -$getDiscountAmount['discount_amount'];
		if($getDiscountAmount['discount_description']){
				 $result['discount_amount'] = '<span>('.$getDiscountAmount['discount_description'].')</span> '.$this->checkoutHelper->formatPrice(-$getDiscountAmount['discount_amount']);
		}else{
			 $result['discount_amount'] = $this->checkoutHelper->formatPrice(-$getDiscountAmount['discount_amount']);
		}

        return $result;
    }
}