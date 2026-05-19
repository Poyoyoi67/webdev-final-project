<?php

namespace App\Form;

use App\Model\ContactMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your name.']),
                    new Length(['max' => 120]),
                ],
                'attr' => ['autocomplete' => 'name', 'placeholder' => 'Your name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your email.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                ],
                'attr' => ['autocomplete' => 'email', 'placeholder' => 'you@example.com'],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Subject',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a subject.']),
                    new Length(['max' => 200]),
                ],
                'attr' => ['placeholder' => 'What is this about?'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a message.']),
                    new Length(['max' => 8000]),
                ],
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Write your message…',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactMessage::class,
        ]);
    }
}
