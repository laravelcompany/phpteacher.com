$q = $this->createQueryBuilder('p')
    ->where('p.brandName = :brandName')
    ->setParameter('brandName', $brandName)
    ->andWhere('p.catalogPrice >= :priceLimit')
    ->setParameter('priceLimit', 1000)
    ->getQuery();

$q->getResult();