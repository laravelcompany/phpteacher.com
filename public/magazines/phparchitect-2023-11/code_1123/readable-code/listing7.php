<?php
class Employee {
    public $name;
    public $salary;

    public function __construct($name, $salary) {
        $this->name = $name;
        $this->salary = $salary;
    }
}

class Payroll {
    public $employees = [];

    public function addEmployee($name, $salary) {
        $employee = new Employee($name, $salary);
        $this->employees[] = $employee;
    }

    public function calculateTotalSalary() {
        $total = 0;
        foreach ($this->employees as $employee) {
            $total += $employee->salary;
        }
        return $total;
    }
}

$payroll = new Payroll();
$payroll->addEmployee('John', 50000);
$payroll->addEmployee('Alice', 60000);

$totalSalary = $payroll->calculateTotalSalary();
echo "Total salary: $totalSalary";