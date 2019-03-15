<?php
namespace Plugin\Napas\Controller\Admin;

use Plugin\Napas\Service\Payment\Method\LinkCreditCard;
use Plugin\Napas\Service\Payment\Method\LinkDomesticCard;
use Plugin\Napas\Service\Payment\Method\NapasGateway;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Controller\AbstractController;
use Plugin\Napas\Repository\ConfigRepository;
use Plugin\Napas\Form\Type\Admin\ConfigType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var NapasGateway
     */
    protected $napasGateway;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     * @param NapasGateway $napasGateway
     */
    public function __construct(
        ConfigRepository $configRepository,
        NapasGateway $napasGateway
    )
    {
        $this->configRepository = $configRepository;
        $this->napasGateway = $napasGateway;
    }

    /**
     * @Route("/%eccube_admin_route%/napas/config", name="napas_admin_config")
     * @Template("@Napas/admin/config.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $typeCheckCard = $request->get('typeCheckCard');
            if ($request->isXmlHttpRequest() && $typeCheckCard) {
                $urlCheck = $this->napasGateway->checkConn($Config);

                return $this->json(['error' => false, 'url' => $urlCheck]);
            }

            if ($request->get('saveConfig')) {
                $this->entityManager->persist($Config);
                $this->entityManager->flush();

                $this->addSuccess('admin.common.save_complete', 'admin');
            }
        }

        return [
            'form' => $form->createView(),
            'urlCheckCredit' => $this->napasGateway->checkConn($Config),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/napas/config/check_return", name="napas_admin_config_check_return")
     * @Template("@Napas/admin/config.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkConfig(Request $request)
    {
        // TODO:
        die;
        $orderId = intval($request->get('vpc_OrderInfo'));

        if ($orderId != NapasGateway::CREDIT_CHECK_ORDER_ID || $orderId != NapasGateway::DOMESTIC_CHECK_ORDER_ID) {
            throw new NotFoundHttpException();
        }

        if ($orderId == NapasGateway::CREDIT_CHECK_ORDER_ID) {
            $PaymentMethod = $this->container->get(LinkCreditCard::class);
        } else {
            $PaymentMethod = $this->container->get(LinkDomesticCard::class);
        }

        $result = $PaymentMethod->handleRequest($request);
        if ($result['status'] === 'success') {
            $this->session->set('eccube.front.shopping.order.id', $orderId);
            return $this->redirectToRoute('napas_admin_config');
        } else {
            $this->addError($result['message'], 'admin');
            return $this->redirectToRoute('napas_admin_config');
        }
    }

    /**
     * @Route("/%eccube_admin_route%/napas/config/check_cancel", name="napas_admin_config_check_cancel")
     * @Template("@Napas/admin/config.twig")
     * @param Request $request
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function checkCancel(Request $request)
    {
        $this->addError('napas.response.msg.cancel', 'admin');
        return $this->redirectToRoute('napas_admin_config');
    }
}