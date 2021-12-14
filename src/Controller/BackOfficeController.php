<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Form\ArticleType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Entity\Comment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Form\RegistrationFormType;



class BackOfficeController extends AbstractController
{

    # Méthode qui affiche la page Home du BACKOFFICE
    #[Route('/admin', name: 'app_admin')]
    public function adminHome(): Response
    {
        return $this->render('back_office/index.html.twig');
    }

    # Méthode qui affiche la page Home du BACKOFFICE
    #[Route('/admin/articles', name: 'app_admin_articles')]
    #[Route('/admin/article/{id}/remove', name: 'app_admin_article_remove')]
    public function adminArticles(EntityManagerInterface $manager, ArticleRepository $repoArticle, Article $artRemove = null): Response
    {
        // dd($artRemove);
        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);

    /*
        Exo : afficher sous forme de tableau HTML l'ensemble des articles stockés en BDD
        1. Séléctionner en BDD l'ensemble de la table 'article' et transmettre le résultat à la méthode render()
        2. Dans le template 'admin_articles.html.twig', mettre en forme l'affichage des articles dans un tableau HTML
        3. Afficher le nom de la catégorie de chaque article
        4. Afficher le nombre de commentaire de chaque articles
        5. Prévoir un lien modification/suppression pour chaque article
    */


        $articles = $repoArticle->findAll(); // SELECT * FROM article + FETCH_ALL
        // dump($articles);
        // dd($articles);

        // Traitement suppression article en BDD
        if($artRemove)
        {

          // Avant de supprimer l'article dans la bdd, on stocke son ID afin de l'intégrer dans le message de validation de suppression (addFlash)
          $id = $artRemove->getId();

          $manager->remove($artRemove);
          $manager->flush();

          $this->addFlash('success', "l'article a été supprimé avec succès");

          return $this->redirectToRoute('app_admin_articles');
        }

        return $this->render('back_office/admin_articles.html.twig', [
          'colonnes' => $colonnes,
          'articles' => $articles

        ]);
    }

    /*
        Exo : création d'une nouvelle méthode permettant d'insérer et de modifier l'article en BDD
        1. Créer une route '/admin/article/add' (name:app_admin_article_add)
        2. Créer la méthode adminArticleForm()
        3. Créer un nouveau template 'admin_article_form.html.twig'
        4. Importer et créer le formulaire au sein de la méthode adminArticleForm() (createForm)
        5. Afficher le formulaire sur le template
        6. Gérer l'upload de la photo
        7. Dans la méthode adminArticleForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD persist() / flush()
    */


    #[Route('/admin/article/add', name: 'app_admin_article_add')]
    #[Route('/admin/article/{id}/modif', name: 'app_admin_article_modif')]
    public function adminArticleForm(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
      if($article)
      {
        $photoActuelle = $article->getPhoto();
      }

      if(!$article)
      {
        $article = new Article;
      }


      $formArticle = $this->createForm(ArticleType::class, $article);

      $formArticle->handleRequest($request);

      if($formArticle->isSubmitted() && $formArticle->isValid())
      {
              if(!$article->getId())
              $article->setDate(new \DateTime());

              $photo = $formArticle->get('photo')->getData();

              if($photo)
              {
                  $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);


                  $secureNomPhoto = $slugger->slug($nomOriginePhoto);

                  $nouveauNomFichier = $secureNomPhoto . '-' . uniqid() . '.' . $photo->guessExtension();

                  try
                  {
                      $photo->move(
                          $this->getParameter('photo_directory'),
                          $nouveauNomFichier
                        );
                  }

                  catch (FileException $e)
                  {

                  }

                  $article->setPhoto($nouveauNomFichier);
              }

              else
              {
                if(isset($photoActuelle))
                    $article->setPhoto($photoActuelle);
                else
                   $article->setPhoto(null);
              }

              if(!$article->getId())
                $txt = "enregistré";
              else
                $txt = "modifié";

              $this->addFlash('success', "L'article a été $txt avec succès !");

              $this->addFlash('success', "L'article a été enregistré avec succès !");

              $manager->persist($article);
              $manager->flush();

              return $this->redirectToRoute('blog_show', [
                'id' => $article->getId()
              ]);


    }

      return $this->render('back_office/admin_article_form.html.twig', [
        'formArticle' => $formArticle->createView(),
        'editMode' => $article->getId(),
        'photoActuelle' => $article->getPhoto()
      ]);

  }

  /*
          Exo : affichage et suppression catégorie
          1. Création d'une nouvelle route '/admin/categories' (name: app_admin_categories)
          2. Création d'une nouvelle méthode adminCategories()
          3. Création d'un nouveau template 'admin_categories.html.twig'
          4. Selectionner les noms des champs/colonnes de la table Category, les transmettre au template et les afficher
          5. Selectionner dans le controller l'ensemble de la table 'category' (findAll) et transmettre au template (render) et les afficher sur le template (Twig), afficher également le nombre d'article liés à chaque catégorie
          6. Prévoir un lien 'modifier' et 'supprimer' pour chaque categorie
          7. Réaliser le traitement permettant de supprimer une catégorie de la BDD
      */


  #[Route('/admin/categorie/add', name: 'app_admin_categories_add')]
  #[Route('/admin/categorie/show', name: 'app_admin_categories_show')]
  public function adminCategoryForm(CategoryRepository $repoCategory, Request $request, EntityManagerInterface $manager, Category $category = null): Response
  {

      $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

      $allCategory = $repoCategory->findAll();
      // dd($allCategory);

      if(!$category)
      {
        $category = new Category;
      }

      $formCategory = $this->createForm(CategoryType::class, $category);

      $formCategory->handleRequest($request);

      if($formCategory->isSubmitted() && $formCategory->isValid())
      {
        $manager->persist($category);
        $manager->flush();

        $this->addFlash('success', "La catégorie a été enregistré avec succès !");

        return $this->redirectToRoute('app_admin_categories_show');
      }

    return $this->render('back_office/admin_categories.html.twig', [
      'formCategory' => $formCategory->createView(),
      'editMode' => $category->getId(),
      'colonnes' => $colonnes,
      'allCategory' => $allCategory
    ]);

}

  #[Route('/admin/categorie/{id}/remove', name: 'app_admin_categorie_remove')]
  public function adminRemoveCategories(EntityManagerInterface $manager, CategoryRepository $repoCategory, Category $category = null): Response
  {
     if($category)
     {
         // On récupère le titre de la catègorie avant la suppression pour l'intégrer dans le message utilisateur
         $titreCat = $category->getTitre();

         // getArticles() retourne tout les articles liés à la catégorie, si le resultat est vide, cela veut dire qu'aucun article n'est lié à la catégorie, on entre dans le IF et on supprime la catégorie
         if($category->getArticles()->isEmpty())
         {
             $this->addFlash('success', "La catégorie '$titreCat' a été supprimé avec succès.");

             $manager->remove($category);
             $manager->flush();
         }
         else // Sinon, des articles sont encore liés à la catégorie, alors on affiche un message d'erreur à l'utilisateur
         {
             $this->addFlash('danger', "Impossible de supprimer la catégorie '$titreCat' car des articles y sont toujours associés.");
         }

         return $this->redirectToRoute('app_admin_categories_show');
      }
   }

   #[Route('/admin/categorie/{id}/update', name: 'app_admin_categorie_update')]
   public function adminUpdateCategories (Request $request, EntityManagerInterface $manager, CategoryRepository $repoCategory, Category $category = null): Response
   {

     $colonnes = $manager->getClassMetadata(Category::class)->getFieldNames();

     $allCategory = $repoCategory->findAll();

     $formCategory = $this->createForm(CategoryType::class, $category);

     $formCategory->handleRequest($request);

     if($formCategory->isSubmitted() && $formCategory->isValid())
     {
       $manager->persist($category);
       $manager->flush();

       $this->addFlash('success', "La catégorie a été modifiée avec succès !");

       return $this->redirectToRoute('app_admin_categories_show');
     }

     return $this->render('back_office/admin_categories_update.html.twig', [
       'formCategory' => $formCategory->createView(),
       'editMode' => $category->getId(),
       'colonnes' => $colonnes,
       'allCategory' => $allCategory
     ]);
   }


   /*
        Exo : Affichage et suppression des commentaires
        1. Création d'une nouvelle route '/admin/commentaires' (name : app_admin_commentaires)
        2. Création d'une nouvelle méthode adminCommentaires()
        3. Création d'un nouveau template 'admin_commentaires.html.twig'
        4. Séléctionner les noms/champs/colonnes de la table 'Comment' et les afficher sur le template
        5. Séléctionner l'ensemble de la table 'Comment' et afficher les données sous forme de tableau (prévoir un lien modification/suppression)
        6. Mettre en place 'dataTable' pour pouvoir filtrer/rechercher des commentaires
        7. Créer une nouvelle route (sur la même méthode) '/admin/comment/{id}/remove' (name : app_admin_comment_remove)
        8. Réaliser le traitement permettant de supprimer un commentaire dans la BDD
   */


   #[Route('/admin/commentaires', name: 'app_admin_commentaires')]
   #[Route('/admin/commentaires/{id}/remove', name: 'app_admin_commentaires_remove')]
   public function adminCommentaires(EntityManagerInterface $manager, CommentRepository $repoComment, Comment $comment = null): Response
   {
     $a = $manager->getClassMetadata(Comment::class)->getFieldNames();

     $comments = $repoComment->findAll();

     if($comment)
     {
         $idComm = $comment->getId();

         $manager->remove($comment);
         $manager->flush();

         $this->addFlash('success', "Le commentaire '$idComm' a été supprimé avec succès.");

         return $this->redirectToRoute('app_admin_commentaires');
      }


     return $this->render('back_office/admin_commentaires.html.twig', [
       'a' => $a,
       'comment' => $comments
     ]);
   }


   #[Route('/admin/user', name: 'app_admin_user')]
   #[Route('/admin/user/{id}/update', name: 'app_admin_user_update')]
   public function adminUser(EntityManagerInterface $manager, Request $request, UserRepository $repoUser, User $user = null): Response
   {

     $b = $manager->getClassMetadata(User::class)->getFieldNames();

     $users = $repoUser->findAll();

     $formUser = $this->createForm(RegistrationFormType::class, $user, [
       'userUpdateBackOffice' => true
     ]);

     $formUser->handleRequest($request);

     if($formUser->isSubmitted() && $formUser->isValid())
     {
       $manager->persist($user);
       $manager->flush();

       $this->addFlash('success', "Le rôle de l'utilisateur a été modifié avec succès !");

       return $this->redirectToRoute('app_admin_user');
     }

     return $this->render('back_office/admin_user.html.twig', [
       'b' => $b,
       'users' => $users,
       'user' => $user,
       'formUser' => $formUser->createView()

      ]);
   }

   #[Route('/admin/user/{id}/delete', name: 'app_admin_user_delete')]
   public function adminUserDelete(EntityManagerInterface $manager, Request $request, UserRepository $repoUser, User $user = null): Response
   {

     $c = $manager->getClassMetadata(User::class)->getFieldNames();

     $users = $repoUser->findAll();

     if($user)
     {
         $idUser = $user->getId();

         $manager->remove($user);
         $manager->flush();

         $this->addFlash('success', "L'utilisateur' '$idUser' a été supprimé avec succès.");

         return $this->redirectToRoute('app_admin_user');
      }

   }


}
