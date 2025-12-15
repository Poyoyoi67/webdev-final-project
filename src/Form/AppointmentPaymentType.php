<?php

namespace App\Form;

use App\Entity\AppointmentPayment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentPaymentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'currency' => 'PHP',
                'label' => 'Amount Paid',
            ])
            ->add('changeAmount', NumberType::class, [
                'label' => 'Change',
                'required' => false,
                'scale' => 2,
            ])
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash' => 'cash',
                    'GCash' => 'gcash',
                    'Bank Transfer' => 'bank_transfer',
                ],
                'required' => false,
                'placeholder' => 'Select a method',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AppointmentPayment::class,
        ]);
    }
}


