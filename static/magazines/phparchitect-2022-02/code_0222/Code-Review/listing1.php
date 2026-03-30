class CustomerCollectionDTO
{
     private $data = [];
     
     public function getSortedArray()
     {
         return uasort($this->data, function($a, $b) {
             $comparison = 0;
             if ($a->count > $b->count) {
                 $comparison = 1;
             } else if ($a->count < $b->count) {
                 $comparison = -1;
             }
             return $comparison;
         });
     }
}