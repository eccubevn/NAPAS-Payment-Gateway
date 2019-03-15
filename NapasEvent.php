<?php
namespace Plugin\Napas;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\PaymentRepository;
use Plugin\Napas\Entity\PaidLogs;
use Plugin\Napas\Repository\PaidLogsRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NapasEvent implements EventSubscriberInterface
{
    /** @var PaidLogsRepository */
    protected $paidLogsRepository;

    /** @var PaymentRepository */
    protected $paymentRepository;

    /** @var EccubeConfig */
    protected $eccubeConfig;

    /**
     * @var \Twig_Environment
     */
    protected $twigEnvironment;

    /**
     * NapasEvent constructor.
     * @param PaidLogsRepository $paidLogsRepository
     * @param PaymentRepository $paymentRepository
     * @param EccubeConfig $eccubeConfig
     * @param \Twig_Environment $twigEnvironment
     */
    public function __construct(
        PaidLogsRepository $paidLogsRepository,
        PaymentRepository $paymentRepository,
        EccubeConfig $eccubeConfig,
        \Twig_Environment $twigEnvironment
    ) {
        $this->paidLogsRepository = $paidLogsRepository;
        $this->paymentRepository = $paymentRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->twigEnvironment = $twigEnvironment;
    }


    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/edit.twig' => 'adminOrderEditIndexInitialize'
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function adminOrderEditIndexInitialize(TemplateEvent $event)
    {
        $parameter = $event->getParameters();
        /** @var Order $Order */
        $Order = $parameter['Order'];

        /** @var PaidLogs $PaidLogs */
        $PaidLogs = $this->paidLogsRepository->findOneBy(["Order" => $Order]);
        if ($PaidLogs) {
            $parameter['payment'] = $this->paymentRepository->find($Order->getPayment()->getId());
            $paidLog = $PaidLogs->getPaidInformation(true);

            $locale = $this->eccubeConfig->get('locale');
            $currency = $this->eccubeConfig->get('currency');
            $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);

            $paidLog['vpc_Amount'] = $formatter->formatCurrency($paidLog['vpc_Amount'], $currency);

            if(isset($paidLog['AgainLink'])){
                $paidLog['AgainLink'] = urldecode($paidLog['AgainLink']);
            }

            $parameter['paidLog'] = $paidLog;
            $event->setParameters($parameter);

            $twig = '@Napas/admin/paid_log.twig';
            $event->addSnippet($twig);
        }
    }
}
