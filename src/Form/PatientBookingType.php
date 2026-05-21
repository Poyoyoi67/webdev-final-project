<?php

namespace App\Form;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PatientBookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a service',
                'constraints' => [new NotBlank()],
            ])
            ->add('doctor', EntityType::class, [
                'class' => Doctor::class,
                'choice_label' => fn (Doctor $doctor) => $doctor->getName() . ' — ' . $doctor->getSpecialization(),
                'placeholder' => 'Select a date first, then choose a doctor',
                'choices' => $options['available_doctors'],
                'constraints' => [new NotBlank()],
            ])
            ->add('appointmentDate', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'label' => 'Appointment date & time',
                'attr' => [
                    'class' => 'form-input datetime-input',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ],
                'constraints' => [new NotBlank()],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Notes (optional)',
                'attr' => ['class' => 'form-textarea', 'rows' => 3, 'placeholder' => 'Symptoms, preferences, or questions for the clinic'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
            'available_doctors' => [],
        ]);
        $resolver->setAllowedTypes('available_doctors', 'array');
    }
}
