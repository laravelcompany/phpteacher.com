$msg = 'Please upload a valid image (jpg, png, gif).';
$builder
      // ...
      ->add('imageFile', VichFileType::class, [
          'label' => 'Profile Picture',
          'label_attr' => ['class' => 'form-label'],
          'required' => false,
          'allow_delete' => true,
          'download_uri' => false,
          'constraints' => [
              new File([
                  'maxSize' => '500k',
                  'mimeTypes' => [
                      'image/jpeg',
                      'image/gif',
                      'image/png',
                  ],
                  'mimeTypesMessage' => $msg,
              ])
          ],
      ]);