<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace MkModules\MergeGuestOrders\Observer\Backend\Sales;

use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class OrderPlaceAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function __construct(
        private \Magento\Sales\Api\OrderRepositoryInterface       $orderRepository,
        private \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        private LoggerInterface                                   $logger
    )
    {
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return false|void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        $this->logger->debug("Order Id=" . $order->getId());

        $customerEmail = $order->getCustomerEmail();
        $this->logger->debug("Order Customer =" . $order->getCustomerEmail());

        if (!empty($customerEmail)) {
            // Check if the customer account with the email address exists
            try {
                $customer = $this->customerRepository->get($customerEmail, 1);
                if ($customer->getId()) {
                    $this->logger->debug("Exists" . $customer->getId());

                    // Assign the guest order to the existing customer account
                    $order->setCustomerId($customer->getId());
                    $order->setCustomerIsGuest(false);
                    $this->orderRepository->save($order);
                }

            } catch (NoSuchEntityException $e) {
                $this->logger->debug($e->getMessage());
                return false;
            }
        }
    }
}

