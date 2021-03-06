<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class LanguageFormType extends AbstractType {
    public function getBlockPrefix() {
        return "form_language";
    }
    
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(Array(
            'data_class' => "App\Form\Model\LanguageModel",
            'csrf_protection' => true,
            'validation_groups' => null
        ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add("codePage", HiddenType::class, Array(
            'required' => true
        ));
    }
}