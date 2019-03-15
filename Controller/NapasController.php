<?php
namespace Plugin\Napas\Controller;

use Plugin\Napas\Repository\PaidLogsRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Controller\AbstractController;
use Eccube\Service\CartService;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\OrderStateMachine;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;

class NapasController extends AbstractController
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var PaidLogsRepository
     */
    protected $paidLogsRepository;

    /**
     * NapasController constructor.
     *
     * @param CartService $cartService
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param OrderStateMachine $orderStateMachine
     * @param PaidLogsRepository $paidLogsRepository
     */
    public function __construct(
        CartService $cartService,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        OrderStateMachine $orderStateMachine,
        PaidLogsRepository $paidLogsRepository
    ) {
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->paidLogsRepository = $paidLogsRepository;
    }

    /**
     * @Route("/napas/return", name="napas_return")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function back(Request $request)
    {
        $orderId =  intval($request->get('vpc_MerchTxnRef'));
        $Order = $this->orderRepository->find($orderId);
        if (!$Order instanceof Order) {
            throw new NotFoundHttpException();
        }

        $this->paidLogsRepository->savePaidLogs($Order, $request->query->all());

        if ($this->getUser() != $Order->getCustomer()) {
            throw new NotFoundHttpException();
        }

        $PaymentMethod = $this->container->get($Order->getPayment()->getMethodClass());

        $result = $PaymentMethod->handleRequest($request);
        if ($result['status'] === 'success') {
            $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::NEW));
            $Order->setOrderDate(new \DateTime());

            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                $this->orderStateMachine->apply($Order, $OrderStatus);
                $Order->setPaymentDate(new \DateTime());
            }

            $this->cartService->clear();

            $this->session->set('eccube.front.shopping.order.id', $Order->getId());
            $this->entityManager->flush();
            return $this->redirectToRoute('shopping_complete');
        } else {
            $this->addError($result['message']);
            return $this->redirectToRoute('shopping_error');
        }
    }

    /**
     * The customer clicked on canceling the transaction while paying
     *
     * @Route("/napas/cancel", name="napas_cancel")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Doctrine\ORM\ORMException
     */
    public function cancel(Request $request)
    {
        $OrderInfo = $request->get('vpc_OrderInfo');
        $Order = $this->orderRepository->findOneBy(['pre_order_id' => $OrderInfo]);
        if (!$Order instanceof Order) {
            throw new NotFoundHttpException();
        }
        $params = $request->query->all();
        $params['message'] = trans('napas.response.msg.cancel');
        $this->paidLogsRepository->savePaidLogs($Order, $params);
        $this->addError('napas.response.msg.cancel');

        return $this->redirectToRoute('shopping_error');
    }
}