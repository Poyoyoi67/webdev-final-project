<?php

namespace App\Form;

use App\AppointmentStatus;
use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $appointment = $event->getData();
            $statusChoices = AppointmentStatus::CHOICES;

            if ($appointment instanceof Appointment && $appointment->getStatus()) {
                $current = $appointment->getStatus();
                if (!\in_array($current, AppointmentStatus::TRACKED, true)) {
                    $statusChoices = [ucfirst($current) . ' (update required)' => $current] + $statusChoices;
                }
            }

            $form = $event->getForm();
            if ($form->has('status')) {
                $form->remove('status');
            }

            $form->add('status', ChoiceType::class, [
                'choices' => $statusChoices,
                'placeholder' => 'Select status',
            ]);
        });

        $builder
            ->add('patientName')
            ->add('appointmentDate', DateTimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'attr' => [
                    'class' => 'datetime-input',
                    'min' => (new \DateTime())->format('Y-m-d\TH:i'),
                ],
            ])
            ->add('notes')
            ->add('doctor', EntityType::class, [
                'class' => Doctor::class,
                'choice_label' => 'id',
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Appointment::class,
        ]);
    }
}
