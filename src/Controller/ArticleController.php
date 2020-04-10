<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleEditType;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/article", name="article_")
 */
class ArticleController extends AbstractController
{
    /**
     * @Route("", name="list")
     */
    public function list(ArticleRepository $articleRepository)
    {
        $articles = $articleRepository->findAll();

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
        ]);
    }

    /**
     * @Route("/{id<\d+>}", name="show")
     */
    public function show(Article $article)
    {
        return $this->render('article/single.html.twig', [
            'article' => $article
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/create", name="new")
     */
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    )
    {
        $article = new Article();

        $form = $this->createForm(ArticleEditType::class, $article);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article = $form->getData();

            $article->setAuthor($this->getUser());

            $entityManager->persist($article);
            $entityManager->flush();

            return new RedirectResponse($urlGenerator->generate('article_show', ['id' => $article->getId()]));
        }

        return $this->render('article/edit.html.twig', [
            'article' => $article,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/{id<\d+>}/edit", methods={"GET"}, name="edit_form")
     */
    public function editForm(Article $article, CategoryRepository $categoryRepository)
    {
        $this->denyAccessUnlessGranted('EDIT', $article);

        $categories = $categoryRepository->findAll();

        return $this->render('article/edit.html.twig', [
            'categories' => $categories,
            'article' => $article
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/{id<\d+>}/edit", methods={"POST"}, name="process_edit")
     */
    public function processEdit(
        Article $article,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    )
    {
        $this->denyAccessUnlessGranted('EDIT', $article);

        $category = $categoryRepository->find($request->request->get('category'));

        $article
            ->setTitle($request->request->get('title'))
            ->setCover($request->request->get('cover'))
            ->setContent($request->request->get('content'))
            ->setCategory($category)
        ;

        $entityManager->persist($article);

        $entityManager->flush();
        
        return new RedirectResponse($urlGenerator->generate('article_show', ['id' => $article->getId()]));
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/{id<\d+>}/delete", methods={"POST"}, name="delete")
     */
    public function delete(
        Article $article,
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator
    )
    {
        $this->denyAccessUnlessGranted('EDIT', $article);

        $entityManager->remove($article);

        $entityManager->flush();

        return new RedirectResponse($urlGenerator->generate('user_publish'));
    }
}
