<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class MicroserviceDeployFormType extends AbstractType {
    public function getBlockPrefix() {
        return "form_microservice_deploy";
    }
    
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(Array(
            'data_class' => "App\Entity\MicroserviceDeploy",
            'csrf_protection' => true,
            'validation_groups' => null
        ));
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add("name", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_1"
        ))
        ->add("description", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_2"
        ))
        ->add("systemUser", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_3"
        ))
        ->add("sshUsername", TextType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_4"
        ))
        ->add("sshPassword", PasswordType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_5"
        ))
        ->add('keyPublic', FileType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_6",
            'data_class' => null
        ))
        ->add('removeKeyPublic', CheckboxType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_7"
        ))
        ->add('keyPrivate', FileType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_8",
            'data_class' => null
        ))
        ->add('removeKeyPrivate', CheckboxType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_9"
        ))
        ->add("ip", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_10"
        ))
        ->add("gitUserEmail", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_11"
        ))
        ->add("gitUserName", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_12"
        ))
        ->add("gitCloneUrl", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_13"
        ))
        ->add("gitClonePath", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_14"
        ))
        ->add("userGitScript", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_15"
        ))
        ->add("userWebScript", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_16"
        ))
        ->add("rootWebPath", TextType::class, Array(
            'required' => true,
            'label' => "microserviceDeployFormType_17"
        ))
        ->add("command", TextareaType::class, Array(
            'required' => false,
            'label' => "microserviceDeployFormType_18"
        ))
        ->add("active", ChoiceType::class, Array(
            'required' => true,
            'placeholder' => "microserviceDeployFormType_19",
            'choices' => Array(
                "microserviceDeployFormType_20" => "0",
                "microserviceDeployFormType_21" => "1"
            )
        ))
        ->add("submit", SubmitType::class, Array(
            'label' => "microserviceDeployFormType_22"
        ));
        
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $formEvent) {
            $data = $formEvent->getData();
            $form = $formEvent->getForm();
            
            if ($data->getRemoveKeyPublic() == false)
                $data->setRemoveKeyPublic("0");
            
            if ($data->getRemoveKeyPrivate() == false)
                $data->setRemoveKeyPrivate("0");
        });
    }
}