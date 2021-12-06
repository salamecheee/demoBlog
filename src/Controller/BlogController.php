<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlogController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function home(): Response
    {
        // méthode rendu, en fonction de la route dans l'URL, la méthode render() envoie un template, un rendu sur le navigateur
        return $this->render('blog/home.html.twig', [
          'title' => 'Bienvenue sur le blog Symfony',
          'age' => 25
        ]);

    }

    # Méthode permettant d'afficher le détail des articles en BDD
    #[Route('/blog', name: 'blog')]
    public function blog(ManagerRegistry $doctrine): Response
    {
        /*
          Symfony est une application qui est capable de répondre à un navigateur lorsque celui-ci appelle une adresse (ex : localhost:8000/blog), le controller doit être capable d'envoyer un rendu, un template sur le navigateur

          Ici, lorsque l'on transmet la route '/blog' dans l'URL, cela execute la méthode index() dans le controller qui renvoie le template '/blog/index/html.twig' sur le navigateur
        */
        // $repoArticle est un objet issu de la classe ArticleRepository
        $repoArticle = $doctrine->getRepository(Article::class);

        $articles = $repoArticle->findAll(); // SELECT * FROM article + FETCH_ALL
        dump($article);

        return $this->render('blog/blog.html.twig');
    }

    # Méthode permettant d'afficher le détail d'un article
    #[Route('/blog/12', name: 'blog_show')]
    public function blogShow(): Response
    {
        return $this->render('blog/blog_show.html.twig');
    }
}
