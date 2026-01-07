<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class ImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FileType::class, [
                'label' => 'Image',
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez sélectionner un fichier image.',
                    ]),
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Seuls les fichiers image (JPG, PNG, WEBP) sont autorisés.',
                        'maxSizeMessage' => 'Le fichier est trop volumineux. Taille maximum : {{ limit }}.',
                    ])
                ],
                'attr' => [
                    'class' => 'block w-full text-sm text-gray-700 border border-gray-300 rounded-lg p-2.5',
                    'accept' => 'image/jpeg,image/jpg,image/png,image/webp'
                ]
            ])
            ->add('save', SubmitType::class, [
                'attr' => ['class' => 'bg-blue-500 hover:bg-blue-700 text-white font-bold p-2 rounded']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configurez ici d'autres options par défaut si nécessaire
        ]);
    }
}
