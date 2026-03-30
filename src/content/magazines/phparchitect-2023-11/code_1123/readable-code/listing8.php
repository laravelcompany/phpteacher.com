interface Employee {
    public function getName(): string;
    public function getSalary(): float;
}

class RegularEmployee implements Employee {
    private $name;
    private $salary;

    public function __construct($name, $salary) {
        $this->name = $name;
        $this->salary = $salary;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSalary(): float {
        return $this->salary;
    }
}

class ContractorEmployee implements Employee {
    private $name;
    private $hourlyRate;
    private $hoursWorked;

    public function __construct(
        $name, $hourlyRate, $hoursWorked
    ) {
        $this->name = $name;
        $this->hourlyRate = $hourlyRate;
        $this->hoursWorked = $hoursWorked;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getSalary(): float {
        return $this->hourlyRate * $this->hoursWorked;
    }
}

class EmployeeRepository {
    private $employees = [];

    public function addEmployee(Employee $employee) {
        $this->employees[] = $employee;
    }

    public function calculateTotalSalary() {
        $total = 0;
        foreach ($this->employees as $employee) {
            $total += $employee->getSalary();
        }
        return $total;
    }
}

$employeeRepository = new EmployeeRepository();
$employeeRepository->addEmployee(
    new RegularEmployee('John', 50000)
);
$employeeRepository->addEmployee(
    new ContractorEmployee('Alice', 30, 160)
);

$salary = $employeeRepository->calculateTotalSalary();
echo "Total salary: $salary";