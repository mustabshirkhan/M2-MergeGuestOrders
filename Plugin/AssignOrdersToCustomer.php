<?php

namespace MkModules\MergeGuestOrders\Plugin;

use Magento\Framework\App\Helper\Context;
use Psr\Log\LoggerInterface;

class AssignOrdersToCustomer
{
    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        private Context                                                    $context,
        private LoggerInterface                                            $logger,
        private \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        private \Magento\Customer\Api\CustomerRepositoryInterface          $customerRepository

    )
    {
    }

    /**
     * @param \Magento\Customer\Api\AccountManagementInterface $subject
     * @param \Magento\Customer\Api\Data\CustomerInterface $result
     * @param $customer
     * @param $password
     * @return \Magento\Customer\Api\Data\CustomerInterface|string|void
     */
    public function afterCreateAccount(
        \Magento\Customer\Api\AccountManagementInterface $subject,
        \Magento\Customer\Api\Data\CustomerInterface     $result,
                                                         $customer,
                                                         $password = null
    )
    {

        $email = $customer->getEmail();
        $customerObj = $this->customerRepository->get($email, 1); // fetching customer id from Customer Repository
        $customerId = $customerObj->getId();

        if ($email) {
            $ordersCollection = $this->orderCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addFieldToFilter('customer_is_guest', '1')
                ->addFieldToFilter('customer_email', $email);
            try {
                if ($ordersCollection->getSize()) {
                    foreach ($ordersCollection as $order) {
                        $order->setCustomerId($customerId);
                        $order->save();
                    }
                }
            } catch (\Exception $exception) {
                return $exception->getMessage();
            }
            return $result;
        }
    }

}
