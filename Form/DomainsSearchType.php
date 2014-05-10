<?php
/**
 * powerdns-api
 * @author   M. Seifert <m.seifert@syseleven.de>
  * @package SysEleven\PowerDnsBundle\Form
 */
namespace SysEleven\PowerDnsBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Defines the base form for searching in the domains table
 *
 * @author M. Seifert <m.seifert@syseleven.de>
 * @package SysEleven\PowerDnsBundle\Form
 */
class DomainsSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search','text', array('required' => false))
            ->add('name', 'text', array('required' => false))
            ->add('account','text', array('required' => false))
            ->add('type','choice', array('choices' => array('MASTER' => 'MASTER', 'NATIVE' => 'NATIVE', 'SLAVE' => 'SLAVE', 'SUPERSLAVE' => 'SUPERSLAVE'),'multiple' => true))
            ->add('master','text', array('required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'csrf_protection' => false,
                'data_class' => 'SysEleven\PowerDnsBundle\Query\DomainsQuery',
            ));
    }

    public function getName()
    {
        return '';
    }

}
 