<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user", name="user_")
 */
class UserController extends AbstractController
{
    private $passwordEncoder;
    private $urlGenerator;
    private $security;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder, UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    /**
     * @Route("/{id<\d+>}", name="show")
     */
    public function show(User $user)
    {
        return $this->render('user/single.html.twig', [
            'user' => $user,
            'articles' => $user->getArticles()
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/edit", methods={"GET"}, name="edit_form")
     */
    public function editForm()
    {
        return $this->render('user/edit.html.twig');
    }

    /**
     * @Route("/edit", methods={"POST"}, name="process_edit")
     */
    public function processEdit(Request $request, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();

        if (is_null($user)) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir cette page');
        }

        $user->setName($request->request->get('name'));

        $newPassword = $request->request->get('password');

        if (!empty($newPassword)) {
            $user->setPassword($this->passwordEncoder->encodePassword(
                $user,
                $newPassword
            ));
        }

        $entityManager->persist($user);

        $entityManager->flush();

        return new RedirectResponse($this->urlGenerator->generate('user_show', ['id' => $user->getId()]));
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/publish", name="publish")
     */
    public function publish(ArticleRepository $articleRepository)
    {
        $parameters = [];

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $parameters['articles'] = $articleRepository->findAll();
        }

        return $this->render('user/publish.html.twig', $parameters);
    }
}
