<?php

namespace nk\ApiBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class InputTokenType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('grant_type', 'choice', array(
                'choices' => array(
                    'password',
                    'social_access_token',
                ),
                'choices_as_values' => true,
            ))
            ->add('client_id')
            ->add('client_secret')
            ->add('username', 'text', array(
                'required' => false,
            ))
            ->add('password', 'text', array(
                'required' => false,
            ))
            ->add('social_id', 'text', array(
                'required' => false,
            ))
            ->add('social_token', 'text', array(
                'required' => false,
            ))
        ;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
