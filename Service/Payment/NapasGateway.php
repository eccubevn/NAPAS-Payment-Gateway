<?php

namespace Plugin\Napas\Service\Payment;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\OrderRepository;
use Plugin\Napas\Entity\Config;
use Plugin\Napas\Repository\PaidLogsRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\Napas\Repository\ConfigRepository;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class NapasGateway implements PaymentMethodInterface
{

    /**
     * @var bool
     */
    protected $isCheck = false;

    /**
     * @var \Eccube\Entity\Order
     */
    protected $Order;

    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var Config
     */
    protected $NapasConfig;

    /**
     * @var PaidLogsRepository
     */
    protected $PaidLogsRepo;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var BaseInfoRepository
     */
    protected $BaseInfo;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * NapasGateway constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param ConfigRepository $configRepository
     * @param PaidLogsRepository $paidLogsRepository
     * @param BaseInfoRepository $BaseInfo
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        ConfigRepository $configRepository,
        PaidLogsRepository $paidLogsRepository,
        BaseInfoRepository $BaseInfo,
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        ContainerInterface $container
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->NapasConfig = $configRepository->get();
        $this->PaidLogsRepo = $paidLogsRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->BaseInfo = $BaseInfo->get();
        $this->orderRepository = $orderRepository;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentResult
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @return PaymentDispatcher
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function apply()
    {
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        $url = $this->getCallUrl();

        $response = new RedirectResponse($url);
        $dispatcher = new PaymentDispatcher();
        $dispatcher->setResponse($response);
        return $dispatcher;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Form\FormInterface $form
     * @return $this
     */
    public function setFormType(\Symfony\Component\Form\FormInterface $form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Eccube\Entity\Order $Order
     * @return $this
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;
        return $this;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function getParameters()
    {
        return [
            'Title' => $this->BaseInfo->getShopName(),
            'vpc_AccessCode' => $this->NapasConfig->getAccessKey(),
            'vpc_Amount' => $this->Order->getTotal() * 100,
            'vpc_BackURL' => $this->getCancelURL().'?vpc_OrderInfo='.$this->Order->getPreOrderId(),
            'vpc_Command' => 'pay',
            'vpc_Currency' => $this->eccubeConfig->get('currency'),
            'vpc_Locale' => 'vn',
            'vpc_MerchTxnRef' => $this->getMerchTxnRef(),
            'vpc_Merchant' => $this->NapasConfig->getProfileId(),
            'vpc_OrderInfo' => $this->getOrderInfo(),
            'vpc_ReturnURL' => $this->getReturnURL(),
            'vpc_Version' => $this->eccubeConfig->get('napas_vpc_Version')
        ];
    }

    /**
     * Order info
     *
     * @return string
     */
    protected function getMerchTxnRef()
    {
        if ($this->isCheck){
            return rand(111111111, 999999999);
        }

        return $this->Order->getId();
    }

    /**
     * Order info
     *
     * @return string
     */
    protected function getOrderInfo()
    {
        if ($this->isCheck){
            return md5(date('YmdHis'));
        }

        return $this->Order->getPreOrderId();
    }

    /**
     * Check connect to Napas input card page
     *
     * @param Config $Config
     * @return string
     * @throws \Exception
     */
    public function checkConn(Config $Config){
        $this->isCheck = true;
        $this->NapasConfig = $Config;
        $Order = new Order();
        $Order->setTotal(10000);

        $this->Order = $Order;

        return $this->getCallUrl();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getCallUrl(){
        $vpcURL         = $this->NapasConfig->getCallUrl()."?";
        $SECURE_SECRET  = $this->NapasConfig->getSecret();
        $md5HashData    = $SECURE_SECRET;
        $params         = $this->getParameters();

        $appendAmp = 0;
        foreach($params as $key => $value) {
            // create the md5 input and URL leaving out any fields that have no value
            if (strlen($value) > 0) {

                // this ensures the first paramter of the URL is preceded by the '?' char
                if ($appendAmp == 0) {
                    $vpcURL .= urlencode($key).'='.urlencode($value);
                    $appendAmp = 1;
                } else {
                    $vpcURL .= '&' . urlencode($key)."=".urlencode($value);
                }
                $md5HashData .= $value;
            }
        }

        if (strlen($SECURE_SECRET) > 0) {
            $vpcURL .= "&vpc_SecureHash=".strtoupper(md5($md5HashData));
        }

        $obj = new \stdClass();
        $obj->vpcURL = $vpcURL;

        // Save logs before send to Napas system
        if (!$this->isCheck) {
            $sendUrl = clone $obj;
            $query_str = parse_url($sendUrl->vpcURL, PHP_URL_QUERY);
            parse_str($query_str, $query_params);
            $query_params['vpc_Amount'] = $query_params['vpc_Amount'] / 100;
            $this->PaidLogsRepo->savePayLogs($this->Order, $query_params);
        }

        return $obj->vpcURL;
    }

    /**
     * @return mixed
     */
    protected function getReturnURL()
    {
        if ($this->isCheck){
            return $this->container->get('router')->generate('napas_admin_config_check_return', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->container->get('router')->generate('napas_return', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @return mixed
     */
    protected function getCancelURL()
    {
        if ($this->isCheck){
            return $this->container->get('router')->generate('napas_admin_config_check_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $this->container->get('router')->generate('napas_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Handle response via Request object
     *
     * @param Request $request
     * @return mixed
     */
    public function handleRequest(Request $request){
        $params = $request->query->all();
        $result = [
            'status' => 'failure',
            'message' => trans('napas.response.msg.failure')
        ];

        if (isset($params['vpc_ResponseCode'])) {
            $code = intval($params['vpc_ResponseCode']);
            if ($code === 0){
                $result['status'] = 'success';
            }

            $result['message'] = $this->getMessageResponse($code);
        }

        return $result;
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getMessageResponse($code)
    {
        $msgArrayCode =
            [0, 1, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33];
        if (in_array($code, $msgArrayCode)) {
            $msg = 'napas.response.msg.' . $code;
        } else {
            $msg = 'napas.response.msg.failure';
        }

        return trans($msg);
    }
}
