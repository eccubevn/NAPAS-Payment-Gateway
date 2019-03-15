<?php
namespace Plugin\Napas\Repository;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\Napas\Entity\Config;

class ConfigRepository extends AbstractRepository
{
    /**
     * ConfigRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Config::class);
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
}
