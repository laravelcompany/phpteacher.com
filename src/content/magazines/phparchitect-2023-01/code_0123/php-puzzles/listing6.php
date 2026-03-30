$jose22 = new \DateTimeImmutable(
                $jose->format('2022-m-d')
          );
$sandra22 = new \DateTimeImmutable(
                $sandra->format('2022-m-d')
            );

$daysDiff22 = abs(
    (int)$jose22->format('z') - (int)$sandra22->format('z')
);

echo "There are " . $daysDiff22 . ' days between ' .
     'their birthdays in a calendar year.';

There are 83 days between their birthdays in a calendar year.