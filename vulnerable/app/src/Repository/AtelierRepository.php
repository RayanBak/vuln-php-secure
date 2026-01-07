<?php

namespace App\Repository;

use App\Entity\Atelier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Atelier>
 *
 * @method Atelier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Atelier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Atelier[]    findAll()
 * @method Atelier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AtelierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Atelier::class);
    }

//    /**
//     * @return Atelier[] Returns an array of Atelier objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Atelier
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Recherche sécurisée d'ateliers par nom ou intervenant
     * Utilise QueryBuilder avec paramètres liés pour prévenir les injections SQL
     * 
     * @param string $query Terme de recherche (limité à 255 caractères)
     * @return array Résultats de la recherche
     */
    public function query(string $query): array
    {
        // Limiter la longueur de la requête pour éviter les abus
        $query = mb_substr(trim($query), 0, 255);
        
        // Retourner un tableau vide si la requête est vide après trim
        if (empty($query)) {
            return [];
        }
        
        // Utiliser QueryBuilder avec paramètres liés pour prévenir SQL Injection
        $qb = $this->createQueryBuilder('a');
        $qb->select('a.intervenant', 'a.nom', 'a.date_debut', 'a.date_fin')
            ->where($qb->expr()->orX(
                $qb->expr()->like('a.nom', ':query'),
                $qb->expr()->like('a.intervenant', ':query')
            ))
            ->setParameter('query', '%' . $query . '%');
        
        $results = $qb->getQuery()->getArrayResult();
        
        // Mapper les résultats pour correspondre au format attendu (date_debut/date_fin)
        return array_map(function($row) {
            return [
                'intervenant' => $row['intervenant'],
                'nom' => $row['nom'],
                'date_debut' => $row['date_debut'] ? $row['date_debut']->format('Y-m-d H:i:s') : null,
                'date_fin' => $row['date_fin'] ? $row['date_fin']->format('Y-m-d H:i:s') : null,
            ];
        }, $results);
    }
}
