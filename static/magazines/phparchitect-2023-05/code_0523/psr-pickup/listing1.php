class MyModel
{
    //...other properties
    public DateTime $createdDate;

    public function __construct()
    {
        $this->createdDate = new DateTime();        
    }
    //...methods, etc.
}

$myModel  = new MyModel();
$tomorrow = (new DateTime())->modify('+1 day');
$nextDay  = $myModel->createdDate->modify('+1 day');
if ($nextDay->getTimestamp() ===
                $tomorrow->getTimestamp()) {
    //...    
}