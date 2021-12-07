<?php

namespace App\DataFixtures;

use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ArticleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // la boucle tourne 10 fois afin de créer 10 articles FICTIFS dans la bdd
        // PHP namespace resolver : extension permettant d'importer les classes (ctrl + alt + i)
        for ($i=1; $i <= 10 ; $i++)
        {

            // Pour insérer des données dans la table SQL Article, nous sommes obligés de passer par sa classe Entity correspondante 'App\Entity\Article', cette classe est le reflet de la table SQL, elle contient des propriétés identiques aux champs/colonnes de la table SQL
            $article = new Article;

            // On execute tous les setters de l'objet afin de remplir les objets et d'ajouter un titre, contenu, image ect pour nos 10 articles
            $article->setTitre("Titre de l'article $i")
                    ->setContenu("<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.")
                    ->setPhoto("https://picsum.photos/id/237/300/600")
                    ->setDate(new \DateTime());

            // Nous faisons appel à l'objet $manager ObjetManager
            // Une classe permet entre autres de manipuler les lignes de la BDD (INSERT, UPDATE, DELETE)
            // persist() : méthode issue de la classe ObjectManager permettant de garder en mémoire les 10 objets $articles et de préparer les requêtes SQL
            $manager->persist($article);

            // $r = $bdd->prepare("INSERT INTO article VALUES ('$article->getTitre()')")
        }
        // flush() : méthode issue de la classe ObjectManager (ORM Doctrine) permettabt véritablement d'executer les requetes SQL en BDD
        $manager->flush();
    }
}
