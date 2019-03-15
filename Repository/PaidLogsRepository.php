<?php
namespace Plugin\Napas\Repository;

use Plugin\Napas\Entity\PaidLogs;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;

class PaidLogsRepository extends AbstractRepository
{
    /**
     * PaidLogsRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PaidLogs::class);
    }

    /**
     * Get current config
     *
     * @param int $id
     * @return object
     */
    public function get($id = 1)
    {
        return $this->find($id);
    }

    /**
     * @param $Order
     * @param $params
     * @throws \Doctrine\ORM\ORMException
     */
    public function savePayLogs($Order, $params)
    {
        $PaidLog = new PaidLogs();
        $PaidLog->setOrder($Order);
        $PaidLog->setPayInformation(json_encode($params));
        $PaidLog->setCreatedAt(new \DateTime());
        $this->getEntityManager()->persist($PaidLog);
        $this->getEntityManager()->flush($PaidLog);
    }

    /**
     * @param $Order
     * @param $params
     * @throws \Doctrine\ORM\ORMException
     */
    public function savePaidLogs($Order, $params)
    {
        if (isset($params['vpc_Amount'])) {
            $params['vpc_Amount'] = $params['vpc_Amount'] / 100;
        }

        $PaidLog = $this->findOneBy(['Order' => $Order]);
        $PaidLog->setPaidInformation(json_encode($params));
        $PaidLog->setUpdatedAt(new \DateTime());
        $this->getEntityManager()->persist($PaidLog);
        $this->getEntityManager()->flush($PaidLog);
    }
}
