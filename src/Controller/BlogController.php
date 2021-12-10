<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ArticleRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Form\CommentType;
use App\Entity\Comment;




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
    public function blog(ArticleRepository $repoArticle): Response
    {
        /*

          Injections de dépendances : c'est un des fondements de Symfony, ici notre méthode dépend de la classe ArticleRepository pour fonctionner correctement
          Ici Symfony comprend que la méthode blog() attend un argument/objet issu de la classe ArticleRepository, automatiquement Symfony envoie un instance de cette classe en argument de cette classe
          $repoArticle est un objet issu de la classe ArticleRepository, nous n'avons plus qu'à piocher dnas l'objet pour atteindre des méthodes de la classe

          Symfony est une application qui est capable de répondre à un navigateur lorsque celui-ci appelle une adresse (ex : localhost:8000/blog), le controller doit être capable d'envoyer un rendu, un template sur le navigateur

          Ici, lorsque l'on transmet la route '/blog' dans l'URL, cela execute la méthode index() dans le controller qui renvoie le template '/blog/index/html.twig' sur le navigateur
        */

        // Pour séléctionner des données en BDD, nous devons passer par une classe Repository, ces classes permettent uniquement 'dexecuter des requetes de séléction SELECT en BDD. Elles contiennent des méthodes mis à disposition par Symfony (findAll(), find(), findBy() etc...)'
        // $repoArticle est un objet issu de la classe ArticleRepository
        // getRepository() est une méthode issue de l'objet Doctine permettant ici d'importer la classe ArticleRepository
        // $repoArticle = $doctrine->getRepository(Article::class);

        // dump() / dd() : outil de debug Symfony


        // findAll() : méthode issue de la classe ArticleRepository permettant de séléctionner l'ensemble de la table SQL et de récupérer un tableau mutli contenant l'ensemble des articles stockés en BDD
        $articles = $repoArticle->findAll(); // SELECT * FROM article + FETCH_ALL
        // dump($articles);
        // dd($articles);

        return $this->render('blog/blog.html.twig', [
          'articles' => $articles // On transmet au template les articles séléctionnés en BDD afin que twig traite l'affichage
        ]);
    }



    # Méthode permettant d'insérer / modifier un article en bdd
    #[Route('/blog/new', name: 'blog_create')]
    #[Route('/blog/{id}/edit', name: 'blog_edit')]
    public function blogCreate(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
    //
    //   // la classe request de symphony contient toutes les données véhiculées par les superglobales ($_GET, $_POST, $_COOKIE etc...)
    //   // $request->request : la propriété 'request' de l'objet $request conteint toutes les données de $_POST
    //
    //   // si les donnés dans le tableau ARRAY $_POST sont supérieures à 0, alors on entre dans la condition if
    //   // if(count($_POST) > 0)
    //   if($request->request->count() > 0)
    //   {
    //     // $request->$_POST;
    //     // dd($request);
    //
    //     // pour insérer dans la table SQL 'article', nous avons besoin d'un objet et de son entité correspondante
    //     $article = new Article;
    //
    //     //
    //     $article->setTitre($request->request->get('titre'))
    //             ->setContenu($request->request->get('contenu'))
    //             ->setPhoto($request->request->get('photo'))
    //             ->setDate(new \DateTime());
    //
    //     // dd($article);
    //
    //     // persist() : méthode issue de l'interface EntityManagerInterface permettant de préparer la requete d'insertion et de garder en mémoire l'objet / la requete
    //     $manager->persist($article);
    //
    //     // flush() : méthode issue de l'interface EntityManagerInterface permettant véritablement d'executer la requet INSERT en bdd (ORM doctrine)
    //     $manager->flush();
    //
    //   }

      // Si la condition IF retourne TRUE, cela veut dire que $article contient un article stocké en BDD, on stocke la version actuelle de l'article dans la variable $photoActuelle
      if($article)
      {
        $photoActuelle = $article->getPhoto();
      }




      // Si la variable $article est nulle, cela veut ire que nous sommes sur la route 'blog/new', on entre dans le if et on crée une nouvelle instance de l'entité article
      // Si la variable $article n'est pas nulle, cela veut dire que nous sommes sur la route '/blog/{id}/edit', nous n'entrons pas dans le if car $article contient un article de la bdd
      if(!$article)
      {
        $article = new Article;
      }

      // $article->setTitre("Titre lambda")
      //         ->setContenu("Contenu lambda");

      $formArticle = $this->createForm(ArticleType::class, $article);

      $formArticle->handleRequest($request);

      // Si le formulaire a bien été validé (isSubmitted) et que l'objet entité est correctement rempli (isValid) alors on entre dans la condition IF
      if($formArticle->isSubmitted() && $formArticle->isValid())
      {
          // le seul setter que l'on appelle de l'entité, c'est celui de la date puisqu'il n'y a pas de champ 'date' dans le formulaire
          // Si l'article ne possède pas d'id, c'est une insertion,   lors on entre dans la condition IF et on génère une date d'article
              if(!$article->getId())
              $article->setDate(new \DateTime());

          // dd($article);


          /////// DEBUT TRAITEMENT PHOTO

          // On récupère toutes les informations de l'image uploadée dans le formulaire
          $photo = $formArticle->get('photo')->getData();
          if($photo) // si une photo est uploadée dans le formulaire, on entre dnas le if et on traite l'image
          {
              // On récupère le nom d'origine de la photo
              $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
              // dd($nomOriginePhoto);

              // cela est nécessaire pour inclure en toute sécurité le nom du fichier dans l'url
              $secureNomPhoto = $slugger->slug($nomOriginePhoto);

              $nouveauNomFichier = $secureNomPhoto . '-' . uniqid() . '.' . $photo->guessExtension();
              // dd($nouveauNomFichier);

              try // on tente de recopier l'image dans le dossier
              {
                  // On copie l'image vers le bon chemin, vers le bon dossier 'public/uploads/photos'
                  $photo->move(
                      $this->getParameter('photo_directory'),
                      $nouveauNomFichier
                    );
              }

              catch (FileException $e)
              {

              }

              // on insère le nom de l'image dans la BDD
              $article->setPhoto($nouveauNomFichier);
          }

          else // Sinon aucune image n'a été uploadé, on renvoie dans la bdd la photo actuelle de l'article
          {
            // si la photo actuelle est définie en bdd, alors en cas de modifications, si on ne change pas de photo, on renvoie la photo actuelle en bdd
            if(isset($photoActuelle))
                $article->setPhoto($photoActuelle);
            else
               // sinon, si aucune photo n'a été uploadée, on envoie la valeur NULL en BDD pour la photo
               $article->setPhoto(null);
          }


          /////// FIN TRAITEMENT PHOTO


          // Message de validation en session
          if(!$article->getId())
            $txt = "enregistré";
          else
            $txt = "modifié";

          // Méthode permettant d'enregistrer des messages utilisateurs accessibles en session
          $this->addFlash('success', "L'article a été $txt avec succès !");

          $this->addFlash('success', "L'article a été enregistré avec succès !");

          $manager->persist($article);
          $manager->flush();

          // Une fois l'insertion/modification executée en BDD, on redirige l'internaute vers le détail de l'article, on transmet l'id à fournir dans l'url en 2ème paramètre de la méthode redirectToRoute()
          return $this->redirectToRoute('blog_show', [
            'id' => $article->getId()
          ]);
      }




      return $this->render('blog/blog_create.html.twig', [
        'formArticle' => $formArticle->createView(), // on transmet le formulaire au template afin de pouvoir l'afficher avec Twig
        // createView() retourne un petit objet qui représente l'affichage du formulaire, on le récupère dans le template blog_create.html.twig
        'editMode' => $article->getId(),
        'photoActuelle' => $article->getPhoto()
      ]);
    }


    # Méthode permettant d'afficher le détail d'un article
    # On définit une route 'paramétrée' {id}, ici la route permet de recevoir l'id d'un artcile stocké en bdd
    #[Route('/blog/{id}', name: 'blog_show')]
    public function blogShow(Article $article, Request $request, EntityManagerInterface $manager): Response
    {

        $user = $this -> getUser();
        // dd($user);


        // Cette méthode mise à disposition retourne un objet App/
        /*
            Ici, nous envoyons un ID dans l'url et nous imposons en argument un objet issu de l'entité Article donc la table SQL
            Symfony est donc capable de séléctionner en BDD l'article en fonction de l'id passé dans l'url et de l'envoyer automatiquement en argument de la méthode blogShow() dans la variable de récéption $article
        */

        // On importe la classe ArticleRepository dans la méthode blogShow pour séléctionner (SELECT) dans la table SQL 'article'
        // $repoArticle = $doctrine->getRepository(Article::class);

        // find() : méthode issue de la classe ArticleRepository permettant de séléctionner un élément par son ID qu'on lui fournit en argument
        // $article = $repoArticle->find($id);
        // dd($article);


        $comment = new Comment;

        $formComm = $this->createForm(CommentType::class, $comment);

        $formComm->handleRequest($request);

        if($formComm->isSubmitted() && $formComm->isValid())
        {
          $comment->setDate(new \DateTime())
                  ->setArticle($article); // on relie le commentaire à l'article

          // dd($comment);

          $manager->persist($comment);
          $manager->flush();

          $this->addFlash('success', "Félicitations ! Votre commentaire a bien été posté !");

          return $this->redirectToRoute('blog_show', [
            'id' => $article->getId()
          ]);

        }

        return $this->render('blog/blog_show.html.twig', [
          'article' => $article,
          'formComm' => $formComm->createView()
        ]);

    }


}
