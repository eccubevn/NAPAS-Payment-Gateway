<?php
namespace Plugin\Napas\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Plugin\Napas\Entity\Config;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

class ConfigType extends AbstractType
{
    /** @var EccubeConfig */
    private $eccubeConfig;
    /**
     * ConfigType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('call_url', TextType::class, [
                'label' => trans('napas.config.call_url.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                    new Url(),
                ],
            ])
            ->add('profile_id', TextType::class, [
                'label' => trans('napas.config.merchant_id.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                ],
            ])
            ->add('access_key', TextType::class, [
                'label' => trans('napas.config.merchant_access_code.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                ],
            ])
            ->add('secret', TextType::class, [
                'label' => trans('napas.config.secret.label'),
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => $this->eccubeConfig->get('eccube_stext_len')]),
                ],
            ]);
    }

    /**
     * {@inheritdoc}
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
