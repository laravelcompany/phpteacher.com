// src/Controller/UserController.php
public function editUser(?int $id, Request $request)
{
  $user = $this->userRepository->findOneBy([
    'id' => $id
  ]);
  if (is_null($user)) {
    $user = new User();
  }

  $form = $this->createForm(
    UserFormType::class,
    $user
  );
  $form->handleRequest($request);

  if ($form->isSubmitted() && $form->isValid()) {

    $imageFile = $form->get('imageFile')->getData();
    $user->setImageFile($imageFile);

    // ... save the entity.
  }
  
  // ... Render the template with the form.
}