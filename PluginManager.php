<?php

namespace Plugin\Napas;

use Plugin\Napas\Service\Payment\Method\NapasGateway;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\PaymentOptionRepository;
use Eccube\Entity\PaymentOption;
use Eccube\Entity\Payment;
use Plugin\Napas\Service\Payment\Method\LinkCreditCard;
use Plugin\Napas\Service\Payment\Method\LinkDomesticCard;
use Plugin\Napas\Entity\Config;
use Plugin\Napas\Repository\ConfigRepository;

class PluginManager extends AbstractPluginManager
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function install(array $meta, ContainerInterface $container)
    {
        $this->container = $container;
        $this->setupConfig();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->container = $container;
        $this->setupPayment();
        $this->removePaymentOption();
        $this->setupPaymentOption();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $this->container = $container;
        $this->removePaymentOption();
        $this->removePayment();
    }

    /**
     * {@inheritdoc}
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $this->container = $container;
        $this->removeConfig();
    }

    /**
     * Setup payment
     */
    protected function setupPayment()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get(PaymentRepository::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => NapasGateway::class]);
        if ($Payment instanceof Payment) {
            $Payment->setVisible(true);
        } else {
            $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
            $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

            $Payment = new Payment();
            $Payment->setCharge(0);
            $Payment->setSortNo($sortNo);
            $Payment->setVisible(false);
            $Payment->setMethod('Napas - Thanh toán bằng thẻ quốc tế');
            $Payment->setMethodClass(NapasGateway::class);
        }
        $entityManager->persist($Payment);
        $entityManager->flush();
    }

    /**
     * Remove payment
     */
    protected function removePayment()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get(PaymentRepository::class);

        $Payments = $paymentRepository->findBy(['method_class' => [NapasGateway::class]]);
        foreach ($Payments as $Payment) {
            $Payment->setVisible(false);
            $entityManager->persist($Payment);
        }
        $entityManager->flush();
    }

    /**
     * Setup PaymentOption
     */
    protected function setupPaymentOption()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get(PaymentRepository::class);

        $Payments = $paymentRepository->findBy(['method_class' => [NapasGateway::class]]);

        /** @var DeliveryRepository $deliveryRepository */
        $deliveryRepository = $this->container->get(DeliveryRepository::class);
        foreach ($deliveryRepository->findAll() as $Delivery) {
            foreach ($Payments as $Payment) {
                $PaymentOption = new PaymentOption();
                $PaymentOption->setDelivery($Delivery);
                $PaymentOption->setDeliveryId($Delivery->getId());
                $PaymentOption->setPayment($Payment);
                $PaymentOption->setPaymentId($Payment->getId());
                $entityManager->persist($PaymentOption);
            }
        }
        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            // silent
        }
    }

    /**
     * Remove payment option
     */
    protected function removePaymentOption()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->container->get(PaymentRepository::class);

        $Payments = $paymentRepository->findBy(['method_class' => [NapasGateway::class]]);
        $paymentIds = [];
        foreach ($Payments as $Payment) {
            $paymentIds[] = $Payment->getId();
        }

        /** @var PaymentOptionRepository $paymentOptionRepository */
        $paymentOptionRepository = $this->container->get(PaymentOptionRepository::class);
        $PaymentOptions = $paymentOptionRepository->findBy(['payment_id' => $paymentIds]);
        foreach ($PaymentOptions as $PaymentOption) {
            $entityManager->remove($PaymentOption);
        }

        $entityManager->flush();
    }


    /**
     * Setup config
     */
    protected function setupConfig()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        $Config = new Config();
        $entityManager->persist($Config);
        $entityManager->flush();
    }

    /**
     * Remove Config
     */
    protected function removeConfig()
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();

        /** @var ConfigRepository $configRepository */
        $configRepository = $this->container->get(ConfigRepository::class);
        $Config = $configRepository->get();
        $entityManager->remove($Config);
        $entityManager->flush();
    }
}
