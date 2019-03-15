<?php
namespace Plugin\Napas\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="plg_napas_config")
 * @ORM\Entity(repositoryClass="Plugin\Napas\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned": true})
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="call_url", type="string", length=1024, nullable=true)
     */
    protected $callUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=1024, nullable=true)
     */
    protected $secret;

    /**
     * @var string
     *
     * @ORM\Column(name="access_key", type="string", length=1024, nullable=true)
     */
    protected $accessKey;

    /**
     * @var string
     *
     * @ORM\Column(name="profile_id", type="string", length=1024, nullable=true)
     */
    protected $profileId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getCallUrl()
    {
        return $this->callUrl;
    }

    /**
     * @param $callUrl
     * @return $this
     */
    public function setCallUrl($callUrl)
    {
        $this->callUrl = $callUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @param $secret
     * @return $this
     */
    public function setSecret($secret)
    {
        $this->secret = $secret;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }

    /**
     * @param $accessKey
     * @return $this
     */
    public function setAccessKey($accessKey)
    {
        $this->accessKey = $accessKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * @param $profileId
     * @return $this
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }
}
