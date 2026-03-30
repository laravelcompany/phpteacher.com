# app/Command/Demo/TpsController.php handle()
$args = $this->getArgs();
$company = $this->getParam('company');
$manager = $this->getParam('manager');
$employee = $this->getParam('employee');

$this->getPrinter()
    ->display("REPORT {$args[2]}");
$this->getPrinter()
    ->display("Company: {$company}  Mgr: {$manager}");
$this->getPrinter()
    ->display("Employee: {$employee}");