<?php
namespace AppBundle\Forms\Contact;

use AppBundle\Entity\Message;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DMCAType extends AbstractType {
    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('subject',TextType::class)
            ->add('content',TextareaType::class);
    }
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
           'data_class' => Message::class,
       ));
    }

}